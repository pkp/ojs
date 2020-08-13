<?php

/**
 * @file classes/migration/JournalsMigration.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class JournalsMigration
 * @brief Describe database table structures.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class JournalsMigration extends Migration {
        /**
         * Run the migrations.
         * @return void
         */
        public function up() {
		// Journals and basic journal settings.
		Capsule::schema()->create('journals', function (Blueprint $table) {
			$table->bigInteger('journal_id')->autoIncrement();
			$table->string('path', 32);
			$table->float('seq', 8, 2)->default(0)->comment('Used to order lists of journals');
			$table->string('primary_locale', 14);
			$table->tinyInteger('enabled')->default(1)->comment('Controls whether or not the journal is considered "live" and will appear on the website. (Note that disabled journals may still be accessible, but only if the user knows the URL.)');

			$table->unique(['path'], 'journals_path');
		});

		// Journal settings.
		Capsule::schema()->create('journal_settings', function (Blueprint $table) {
			$table->bigInteger('journal_id');
			$table->foreign('journal_id')->references('journal_id')->on('journals');

			$table->string('locale', 14)->default('');
			$table->string('setting_name', 255);
			$table->mediumText('setting_value')->nullable();
			$table->string('setting_type', 6)->nullable();

			$table->index(['journal_id'], 'journal_settings_journal_id');
			$table->unique(['journal_id', 'locale', 'setting_name'], 'journal_settings_pkey');
		});

		// Journal sections.
		Capsule::schema()->create('sections', function (Blueprint $table) {
			$table->bigInteger('section_id')->autoIncrement();

			$table->bigInteger('journal_id');
			$table->foreign('journal_id')->references('journal_id')->on('journals');

			$table->bigInteger('review_form_id')->nullable();
			$table->float('seq', 8, 2)->default(0);
			$table->tinyInteger('editor_restricted')->default(0);
			$table->tinyInteger('meta_indexed')->default(0);
			$table->tinyInteger('meta_reviewed')->default(1);
			$table->tinyInteger('abstracts_not_required')->default(0);
			$table->tinyInteger('hide_title')->default(0);
			$table->tinyInteger('hide_author')->default(0);
			$table->bigInteger('abstract_word_count')->nullable();

			$table->index(['journal_id'], 'sections_journal_id');
		});

		// Section-specific settings
		Capsule::schema()->create('section_settings', function (Blueprint $table) {
			$table->bigInteger('section_id');
			$table->foreign('section_id')->references('section_id')->on('sections');

			$table->string('locale', 14)->default('');
			$table->string('setting_name', 255);
			$table->text('setting_value')->nullable();
			$table->string('setting_type', 6)->comment('(bool|int|float|string|object)');
			$table->index(['section_id'], 'section_settings_section_id');
			$table->unique(['section_id', 'locale', 'setting_name'], 'section_settings_pkey');
		});

	}

	/**
	 * Reverse the migration.
	 * @return void
	 */
	public function down() {
		Capsule::schema()->drop('section_settings');
		Capsule::schema()->drop('sections');
		Capsule::schema()->drop('journal_settings');
		Capsule::schema()->drop('journals');
	}
}
