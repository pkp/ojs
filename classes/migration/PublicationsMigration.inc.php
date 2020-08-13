<?php

/**
 * @file classes/migration/PublicatoinsMigration.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationsMigration
 * @brief Describe database table structures.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class PublicationsMigration extends Migration {
        /**
         * Run the migrations.
         * @return void
         */
        public function up() {
		// Publications
		Capsule::schema()->create('publications', function (Blueprint $table) {
			$table->bigInteger('publication_id')->autoIncrement();
			$table->bigInteger('access_status')->default(0)->nullable();
			$table->date('date_published')->nullable();
			$table->datetime('last_modified')->nullable();
			$table->string('locale', 14)->nullable();

			$table->bigInteger('primary_contact_id')->nullable();
			$table->foreign('primary_contact_id')->references('user_id')->on('users');

			$table->bigInteger('section_id')->nullable();
			$table->foreign('section_id')->references('section_id')->on('sections');

			$table->float('seq', 8, 2)->default(0);

			$table->bigInteger('submission_id');
			$table->foreign('submission_id')->references('submission_id')->on('submissions');

			$table->tinyInteger('status')->default(1); // STATUS_QUEUED
			$table->string('url_path', 64)->nullable();
			$table->bigInteger('version')->nullable();

			$table->index(['submission_id'], 'publications_submission_id');
			$table->index(['section_id'], 'publications_section_id');
			$table->index(['url_path'], 'publications_url_path');
		});

		// publication metadata
		Capsule::schema()->create('publication_settings', function (Blueprint $table) {
			$table->bigInteger('publication_id');
			$table->foreign('publication_id')->references('publication_id')->on('publications');

			$table->string('locale', 14)->default('');
			$table->string('setting_name', 255);
			$table->text('setting_value')->nullable();

			$table->index(['publication_id'], 'publication_settings_publication_id');
			$table->unique(['publication_id', 'locale', 'setting_name'], 'publication_settings_pkey');
		});
		// Add partial index (DBMS-specific)
		switch (Capsule::connection()->getDriverName()) {
			case 'mysql': Capsule::connection()->unprepared('CREATE INDEX publication_settings_name_value ON publication_settings (setting_name(50), setting_value(150))'); break;
			case 'pgsql': Capsule::connection()->unprepared("CREATE INDEX publication_settings_name_value ON publication_settings (setting_name, setting_value) WHERE setting_name IN ('indexingState', 'medra::registeredDoi', 'datacite::registeredDoi', 'pub-id::publisher-id')"); break;
		}
	}

	/**
	 * Reverse the migration.
	 * @return void
	 */
	public function down() {
		Capsule::schema()->drop('publication_settings');
		Capsule::schema()->drop('publications');
	}
}
