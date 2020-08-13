<?php

/**
 * @file classes/migration/IssuesMigration.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssuesMigration
 * @brief Describe database table structures.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class IssuesMigration extends Migration {
        /**
         * Run the migrations.
         * @return void
         */
        public function up() {
		// Journal issues.
		Capsule::schema()->create('issues', function (Blueprint $table) {
			$table->bigInteger('issue_id')->autoIncrement();

			$table->bigInteger('journal_id');
			$table->foreign('journal_id')->references('journal_id')->on('journals');

			$table->smallInteger('volume')->nullable();
			$table->string('number', 40)->nullable();
			$table->smallInteger('year')->nullable();
			$table->tinyInteger('published')->default(0);
			$table->tinyInteger('current')->default(0);
			$table->datetime('date_published')->nullable();
			$table->datetime('date_notified')->nullable();
			$table->datetime('last_modified')->nullable();
			$table->tinyInteger('access_status')->default(1);
			$table->datetime('open_access_date')->nullable();
			$table->tinyInteger('show_volume')->default(0);
			$table->tinyInteger('show_number')->default(0);
			$table->tinyInteger('show_year')->default(0);
			$table->tinyInteger('show_title')->default(0);
			$table->string('style_file_name', 90)->nullable();
			$table->string('original_style_file_name', 255)->nullable();
			$table->string('url_path', 64)->nullable();

			$table->index(['journal_id'], 'issues_journal_id');
			$table->index(['url_path'], 'issues_url_path');
		});

		// Locale-specific issue data
		Capsule::schema()->create('issue_settings', function (Blueprint $table) {
			$table->bigInteger('issue_id');
			$table->foreign('issue_id')->references('issue_id')->on('issues');

			$table->string('locale', 14)->default('');
			$table->string('setting_name', 255);
			$table->text('setting_value')->nullable();
			$table->string('setting_type', 6);

			$table->index(['issue_id'], 'issue_settings_issue_id');
			$table->unique(['issue_id', 'locale', 'setting_name'], 'issue_settings_pkey');
		});
		// Add partial index (DBMS-specific)
		switch (Capsule::connection()->getDriverName()) {
			case 'mysql': Capsule::connection()->unprepared('CREATE INDEX issue_settings_name_value ON issue_settings (setting_name(50), setting_value(150))'); break;
			case 'pgsql': Capsule::connection()->unprepared("CREATE INDEX issue_settings_name_value ON issue_settings (setting_name, setting_value) WHERE setting_name IN ('medra::registeredDoi', 'datacite::registeredDoi')"); break;
		}

		// Issue files.
		Capsule::schema()->create('issue_files', function (Blueprint $table) {
			$table->bigInteger('file_id')->autoIncrement();

			$table->bigInteger('issue_id');
			$table->foreign('issue_id')->references('issue_id')->on('issues');

			$table->string('file_name', 90);
			$table->string('file_type', 255);
			$table->bigInteger('file_size');
			$table->bigInteger('content_type');
			$table->string('original_file_name', 127)->nullable();
			$table->datetime('date_uploaded');
			$table->datetime('date_modified');

			$table->index(['issue_id'], 'issue_files_issue_id');
		});

		// Issue galleys.
		Capsule::schema()->create('issue_galleys', function (Blueprint $table) {
			$table->bigInteger('galley_id')->autoIncrement();
			$table->string('locale', 14)->nullable();

			$table->bigInteger('issue_id');
			$table->foreign('issue_id')->references('issue_id')->on('issues');

			$table->bigInteger('file_id');
			$table->foreign('file_id')->references('file_id')->on('issue_files');

			$table->string('label', 32)->nullable();
			$table->float('seq', 8, 2)->default(0);
			$table->string('url_path', 64)->nullable();

			$table->index(['issue_id'], 'issue_galleys_issue_id');
			$table->index(['url_path'], 'issue_galleys_url_path');
		});

		// Issue galley metadata.
		Capsule::schema()->create('issue_galley_settings', function (Blueprint $table) {
			$table->bigInteger('galley_id');
			$table->foreign('galley_id')->references('galley_id')->on('issue_galleys');

			$table->string('locale', 14)->default('');
			$table->string('setting_name', 255);
			$table->text('setting_value')->nullable();
			$table->string('setting_type', 6)->comment('(bool|int|float|string|object)');

			$table->index(['galley_id'], 'issue_galley_settings_galley_id');
			$table->unique(['galley_id', 'locale', 'setting_name'], 'issue_galley_settings_pkey');
		});

		// Custom sequencing information for journal issues, when available
		Capsule::schema()->create('custom_issue_orders', function (Blueprint $table) {
			$table->bigInteger('issue_id');
			$table->foreign('issue_id')->references('issue_id')->on('issues');

			$table->bigInteger('journal_id');
			$table->foreign('journal_id')->references('journal_id')->on('journals');

			$table->float('seq', 8, 2)->default(0);

			$table->unique(['issue_id'], 'custom_issue_orders_pkey');
		});
	}

	/**
	 * Reverse the migration.
	 * @return void
	 */
	public function down() {
		Capsule::schema()->drop('custom_issue_orders');
		Capsule::schema()->drop('issue_galley_settings');
		Capsule::schema()->drop('issue_files');
		Capsule::schema()->drop('issue_settings');
		Capsule::schema()->drop('issues');
	}
}
