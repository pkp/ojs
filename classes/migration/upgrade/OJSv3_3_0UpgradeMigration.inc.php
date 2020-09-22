<?php

/**
 * @file classes/migration/upgrade/OJSv3_3_0UpgradeMigration.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
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

		$this->_settingsAsJSON();
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
			Capsule::table($tableName)->where('setting_type', 'object')->get()->each(function ($row) use ($tableName) {
				$this->_toJSON($row, $tableName, ['setting_name', 'locale'], 'setting_value');
			});
		}

		// Finally, convert values of other tables dependent from DAO::convertToDB
		Capsule::table('review_form_responses')->where('response_type', 'object')->get()->each(function ($row) {
			$this->_toJSON($row, 'review_form_responses', ['review_id'], 'response_value');
		});

		Capsule::table('site')->get()->each(function ($row) {
			$oldInstalledLocales = unserialize($row->{'installed_locales'});
			$oldSupportedLocales = unserialize($row->{'supported_locales'});

			if (is_array($oldInstalledLocales) && $this->_isNumerical($oldInstalledLocales)) $oldInstalledLocales = array_values($oldInstalledLocales);
			if (is_array($oldSupportedLocales) && $this->_isNumerical($oldSupportedLocales)) $oldSupportedLocales = array_values($oldSupportedLocales);

			if ($oldInstalledLocales) {
				$newInstalledLocales = json_encode($oldInstalledLocales, JSON_UNESCAPED_UNICODE);
				Capsule::table('site')->take(1)->update(['installed_locales' => $newInstalledLocales]);
			}

			if ($oldSupportedLocales) {
				$newSupportedLocales = json_encode($oldSupportedLocales, JSON_UNESCAPED_UNICODE);
				Capsule::table('site')->take(1)->update(['supported_locales' => $newSupportedLocales]);
			}
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
		$oldValue = unserialize($row->{$valueToConvert});
		// Reset arrays to avoid keys being mixed up
		if (is_array($oldValue) && $this->_isNumerical($oldValue)) $oldValue = array_values($oldValue);
		if (!$oldValue && !is_array($oldValue)) return; // don't continue if value cannot be unserialized
		$newValue = json_encode($oldValue, JSON_UNESCAPED_UNICODE); // don't convert utf-8 characters to unicode escaped code

		$id = array_key_first((array)$row); // get first/primary key column

		// Remove empty filters
		$searchBy = array_filter($searchBy, function ($item) use ($row) {
			if (empty($row->{$item})) return false;
			return true;
		});

		// Build raw where query
		$whereRaw = '';
		$lastKey = array_key_last($searchBy);
		foreach ($searchBy as $key => $column) {
			$whereRaw .= $column . " = '" . $row->{$column} . "'";
			if ($key !== $lastKey) $whereRaw .= ' AND ';
		}

		Capsule::table($tableName)->where($id, $row->{$id})->whereRaw($whereRaw)->update([$valueToConvert => $newValue]);
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
}
