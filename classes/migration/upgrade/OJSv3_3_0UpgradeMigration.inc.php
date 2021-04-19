<?php

/**
 * @file classes/migration/upgrade/OJSv3_3_0UpgradeMigration.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionsMigration
 * @brief Describe database table structures.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class OJSv3_3_0UpgradeMigration extends Migration {
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up() {
		Capsule::schema()->table('journal_settings', function (Blueprint $table) {
			// pkp/pkp-lib#6096 DB field type TEXT is cutting off long content
			$table->mediumText('setting_value')->nullable()->change();
		});
		Capsule::schema()->table('sections', function (Blueprint $table) {
			$table->smallInteger('is_inactive')->default(0);
		});
		Capsule::schema()->table('review_forms', function (Blueprint $table) {
			$table->bigInteger('assoc_type')->nullable(false)->change();
			$table->bigInteger('assoc_id')->nullable(false)->change();
		});

		$this->_settingsAsJSON();
		$this->_migrateSubmissionFiles();

		// Delete the old MODS34 filters
		Capsule::statement("DELETE FROM filters WHERE class_name='plugins.metadata.mods34.filter.Mods34SchemaArticleAdapter'");
		Capsule::statement("DELETE FROM filter_groups WHERE symbolic IN ('article=>mods34', 'mods34=>article')");
		// Delete mEDRA dependencies
		Capsule::statement("DELETE FROM filters WHERE class_name IN ('plugins.importexport.medra.filter.IssueMedraXmlFilter', 'plugins.importexport.medra.filter.ArticleMedraXmlFilter', 'plugins.importexport.medra.filter.GalleyMedraXmlFilter')");
		Capsule::statement("DELETE FROM filter_groups WHERE symbolic IN ('issue=>medra-xml', 'article=>medra-xml', 'galley=>medra-xml')");
		Capsule::statement("DELETE FROM scheduled_tasks WHERE class_name='plugins.importexport.medra.MedraInfoSender'");
		Capsule::statement("DELETE FROM versions WHERE product_type='plugins.importexport' AND product='medra'");

		// pkp/pkp-lib#6807 Make sure all submission/issue last modification dates are set
		Capsule::statement('UPDATE issues SET last_modified = date_published WHERE last_modified IS NULL');
		Capsule::statement('UPDATE submissions SET last_modified = NOW() WHERE last_modified IS NULL');
	}

	/**
	 * Reverse the downgrades
	 * @return void
	 */
	public function down() {
		Capsule::schema()->table('journal_settings', function (Blueprint $table) {
			// pkp/pkp-lib#6096 DB field type TEXT is cutting off long content
			$table->text('setting_value')->nullable()->change();
		});
	}

	/**
	 * @return void
	 * @bried reset serialized arrays and convert array and objects to JSON for serialization, see pkp/pkp-lib#5772
	 */
	private function _settingsAsJSON() {

		// Convert settings where type can be retrieved from schema.json
		$schemaDAOs = ['SiteDAO', 'AnnouncementDAO', 'AuthorDAO', 'ArticleGalleyDAO', 'JournalDAO', 'EmailTemplateDAO', 'PublicationDAO', 'SubmissionDAO'];
		$processedTables = [];
		foreach ($schemaDAOs as $daoName) {
			$dao = DAORegistry::getDAO($daoName);
			$schemaService = Services::get('schema');

			if (is_a($dao, 'SchemaDAO')) {
				$schema = $schemaService->get($dao->schemaName);
				$tableName = $dao->settingsTableName;
			} else if ($daoName === 'SiteDAO') {
				$schema = $schemaService->get(SCHEMA_SITE);
				$tableName = 'site_settings';
			} else {
				continue; // if parent class changes, the table is processed with other settings tables
			}

			$processedTables[] = $tableName;
			foreach ($schema->properties as $propName => $propSchema) {
				if (empty($propSchema->readOnly)) {
					if ($propSchema->type === 'array' || $propSchema->type === 'object') {
						Capsule::table($tableName)->where('setting_name', $propName)->get()->each(function ($row) use ($tableName) {
							$this->_toJSON($row, $tableName, ['setting_name', 'locale'], 'setting_value');
						});
					}
				}
			}
		}

		// Convert settings where only setting_type column is available
		$tables = Capsule::connection()->getDoctrineSchemaManager()->listTableNames();
		foreach ($tables as $tableName) {
			if (substr($tableName, -9) !== '_settings' || in_array($tableName, $processedTables)) continue;
			if ($tableName === 'plugin_settings') {
				Capsule::table($tableName)->where('setting_type', 'object')->get()->each(function ($row) use ($tableName) {
					$this->_toJSON($row, $tableName, ['plugin_name', 'context_id', 'setting_name'], 'setting_value');
				});
			} else {
				Capsule::table($tableName)->where('setting_type', 'object')->get()->each(function ($row) use ($tableName) {
					$this->_toJSON($row, $tableName, ['setting_name', 'locale'], 'setting_value');
				});
			}
		}

		// Finally, convert values of other tables dependent from DAO::convertToDB
		Capsule::table('review_form_responses')->where('response_type', 'object')->get()->each(function ($row) {
			$this->_toJSON($row, 'review_form_responses', ['review_id'], 'response_value');
		});

		Capsule::table('site')->get()->each(function ($row) {
			$localeToConvert = function($localeType) use($row) {
				$serializedValue = $row->{$localeType};
				if (@unserialize($serializedValue) === false) return;
				$oldLocaleValue = unserialize($serializedValue);

				if (is_array($oldLocaleValue) && $this->_isNumerical($oldLocaleValue)) $oldLocaleValue = array_values($oldLocaleValue);

				$newLocaleValue = json_encode($oldLocaleValue, JSON_UNESCAPED_UNICODE);
				Capsule::table('site')->take(1)->update([$localeType => $newLocaleValue]);
			};

			$localeToConvert('installed_locales');
			$localeToConvert('supported_locales');
		});
	}

	/**
	 * @param $row stdClass row representation
	 * @param $tableName string name of a settings table
	 * @param $searchBy array additional parameters to the where clause that should be combined with AND operator
	 * @param $valueToConvert string column name for values to convert to JSON
	 * @return void
	 */
	private function _toJSON($row, $tableName, $searchBy, $valueToConvert)
	{
		// Check if value can be unserialized
		$serializedOldValue = $row->{$valueToConvert};
		if (@unserialize($serializedOldValue) === false) return;
		$oldValue = unserialize($serializedOldValue);

		// Reset arrays to avoid keys being mixed up
		if (is_array($oldValue) && $this->_isNumerical($oldValue)) $oldValue = array_values($oldValue);
		$newValue = json_encode($oldValue, JSON_UNESCAPED_UNICODE); // don't convert utf-8 characters to unicode escaped code

		$id = array_key_first((array)$row); // get first/primary key column

		// Remove empty filters
		$searchBy = array_filter($searchBy, function ($item) use ($row) {
			if (empty($row->{$item})) return false;
			return true;
		});

		$queryBuilder = Capsule::table($tableName)->where($id, $row->{$id});
		foreach ($searchBy as $key => $column) {
			$queryBuilder = $queryBuilder->where($column, $row->{$column});
		}
		$queryBuilder->update([$valueToConvert => $newValue]);
	}

	/**
	 * @param $array array to check
	 * @return bool
	 * @brief checks unserialized array; returns true if array keys are integers
	 * otherwise if keys are mixed and sequence starts from any positive integer it will be serialized as JSON object instead of an array
	 * See pkp/pkp-lib#5690 for more details
	 */
	private function _isNumerical($array) {
		foreach ($array as $item => $value) {
			if (!is_integer($item)) return false; // is an associative array;
		}

		return true;
	}

	/**
	 * Complete submission file migrations specific to OJS
	 *
	 * The main submission file migration is done in
	 * PKPv3_3_0UpgradeMigration and that migration must
	 * be run before this one.
	 */
	private function _migrateSubmissionFiles() {
		Capsule::schema()->table('publication_galleys', function (Blueprint $table) {
			$table->renameColumn('file_id', 'submission_file_id');
		});
		Capsule::statement('UPDATE publication_galleys SET submission_file_id = NULL WHERE submission_file_id = 0');

		// pkp/pkp-lib#6616 Delete publication_galleys entries that correspond to nonexistent submission_files
		$orphanedIds = Capsule::table('publication_galleys AS pg')
			->leftJoin('submission_files AS sf', 'pg.submission_file_id', '=', 'sf.submission_file_id')
			->whereNull('sf.submission_file_id')
			->whereNotNull('pg.submission_file_id')
			->pluck('pg.submission_file_id', 'pg.galley_id');
		foreach ($orphanedIds as $galleyId => $submissionFileId) {
			error_log("Removing orphaned publication_galleys entry ID $galleyId with submission_file_id $submissionFileId");
			Capsule::table('publication_galleys')->where('galley_id', '=', $galleyId)->delete();
		}

		Capsule::schema()->table('publication_galleys', function (Blueprint $table) {
			$table->bigInteger('submission_file_id')->nullable()->unsigned()->change();
			$table->foreign('submission_file_id')->references('submission_file_id')->on('submission_files');
		});
	}
}
