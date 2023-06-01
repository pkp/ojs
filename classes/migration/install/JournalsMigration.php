<?php

/**
 * @file classes/migration/install/JournalsMigration.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class JournalsMigration
 *
 * @brief Describe database table structures.
 */

namespace APP\migration\install;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class JournalsMigration extends \PKP\migration\Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Journals and basic journal settings.
        Schema::create('journals', function (Blueprint $table) {
            $table->comment('A list of all journals in the installation of OJS.');
            $table->bigInteger('journal_id')->autoIncrement();
            $table->string('path', 32);
            $table->float('seq', 8, 2)->default(0)->comment('Used to order lists of journals');
            $table->string('primary_locale', 14);
            $table->smallInteger('enabled')->default(1)->comment('Controls whether or not the journal is considered "live" and will appear on the website. (Note that disabled journals may still be accessible, but only if the user knows the URL.)');
            $table->unique(['path'], 'journals_path');
            $table->bigInteger('current_issue_id')->nullable()->default(null);
        });

        // Journal settings.
        Schema::create('journal_settings', function (Blueprint $table) {
            $table->comment('More data about journals, including localized properties like policies.');
            $table->bigIncrements('journal_setting_id');

            $table->bigInteger('journal_id');
            $table->foreign('journal_id', 'journal_settings_journal_id')->references('journal_id')->on('journals')->onDelete('cascade');
            $table->index(['journal_id'], 'journal_settings_journal_id');

            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->mediumText('setting_value')->nullable();

            $table->unique(['journal_id', 'locale', 'setting_name'], 'journal_settings_unique');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::drop('journal_settings');
        Schema::drop('journals');
    }
}
