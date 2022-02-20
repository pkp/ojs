<?php

/**
 * @file classes/migration/install/OJSMigration.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OJSMigration
 * @brief Describe database table structures.
 */

namespace APP\migration\install;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OJSMigration extends \PKP\migration\Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Journals and basic journal settings.
        Schema::create('journals', function (Blueprint $table) {
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
            $table->bigInteger('journal_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->mediumText('setting_value')->nullable();
            $table->string('setting_type', 6)->nullable();
            $table->index(['journal_id'], 'journal_settings_journal_id');
            $table->unique(['journal_id', 'locale', 'setting_name'], 'journal_settings_pkey');
        });

        // DOI foreign key references Journal, so it needs to be added AFTER the journal has been created
        Schema::table('dois', function (Blueprint $table) {
            $table->foreign('context_id')->references('journal_id')->on('journals');
        });

        // Journal sections.
        Schema::create('sections', function (Blueprint $table) {
            $table->bigInteger('section_id')->autoIncrement();
            $table->bigInteger('journal_id');
            $table->bigInteger('review_form_id')->nullable();
            $table->float('seq', 8, 2)->default(0);
            $table->smallInteger('editor_restricted')->default(0);
            $table->smallInteger('meta_indexed')->default(0);
            $table->smallInteger('meta_reviewed')->default(1);
            $table->smallInteger('abstracts_not_required')->default(0);
            $table->smallInteger('hide_title')->default(0);
            $table->smallInteger('hide_author')->default(0);
            $table->smallInteger('is_inactive')->default(0);
            $table->bigInteger('abstract_word_count')->nullable();
            $table->index(['journal_id'], 'sections_journal_id');
        });

        // Section-specific settings
        Schema::create('section_settings', function (Blueprint $table) {
            $table->bigInteger('section_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->text('setting_value')->nullable();
            $table->string('setting_type', 6)->comment('(bool|int|float|string|object)');
            $table->index(['section_id'], 'section_settings_section_id');
            $table->unique(['section_id', 'locale', 'setting_name'], 'section_settings_pkey');
        });

        // Journal issues.
        Schema::create('issues', function (Blueprint $table) {
            $table->bigInteger('issue_id')->autoIncrement();
            $table->bigInteger('journal_id');
            $table->smallInteger('volume')->nullable();
            $table->string('number', 40)->nullable();
            $table->smallInteger('year')->nullable();
            $table->smallInteger('published')->default(0);
            $table->datetime('date_published')->nullable();
            $table->datetime('date_notified')->nullable();
            $table->datetime('last_modified')->nullable();
            $table->smallInteger('access_status')->default(1);
            $table->datetime('open_access_date')->nullable();
            $table->smallInteger('show_volume')->default(0);
            $table->smallInteger('show_number')->default(0);
            $table->smallInteger('show_year')->default(0);
            $table->smallInteger('show_title')->default(0);
            $table->string('style_file_name', 90)->nullable();
            $table->string('original_style_file_name', 255)->nullable();
            $table->string('url_path', 64)->nullable();
            $table->bigInteger('doi_id')->nullable();
            $table->index(['journal_id'], 'issues_journal_id');
            $table->index(['url_path'], 'issues_url_path');
            $table->foreign('doi_id')->references('doi_id')->on('dois')->nullOnDelete();
        });

        // Locale-specific issue data
        Schema::create('issue_settings', function (Blueprint $table) {
            $table->bigInteger('issue_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->text('setting_value')->nullable();
            $table->string('setting_type', 6)->nullable();
            $table->index(['issue_id'], 'issue_settings_issue_id');
            $table->unique(['issue_id', 'locale', 'setting_name'], 'issue_settings_pkey');
        });
        // Add partial index (DBMS-specific)
        switch (DB::getDriverName()) {
            case 'mysql': DB::unprepared('CREATE INDEX issue_settings_name_value ON issue_settings (setting_name(50), setting_value(150))'); break;
            case 'pgsql': DB::unprepared("CREATE INDEX issue_settings_name_value ON issue_settings (setting_name, setting_value) WHERE setting_name IN ('medra::registeredDoi', 'datacite::registeredDoi')"); break;
        }

        // Issue galleys.
        Schema::create('issue_galleys', function (Blueprint $table) {
            $table->bigInteger('galley_id')->autoIncrement();
            $table->string('locale', 14)->nullable();
            $table->bigInteger('issue_id');
            $table->bigInteger('file_id');
            $table->string('label', 255)->nullable();
            $table->float('seq', 8, 2)->default(0);
            $table->string('url_path', 64)->nullable();
            $table->index(['issue_id'], 'issue_galleys_issue_id');
            $table->index(['url_path'], 'issue_galleys_url_path');
        });

        // Issue galley metadata.
        Schema::create('issue_galley_settings', function (Blueprint $table) {
            $table->bigInteger('galley_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->text('setting_value')->nullable();
            $table->string('setting_type', 6)->comment('(bool|int|float|string|object)');
            $table->index(['galley_id'], 'issue_galley_settings_galley_id');
            $table->unique(['galley_id', 'locale', 'setting_name'], 'issue_galley_settings_pkey');
        });

        Schema::create('issue_files', function (Blueprint $table) {
            $table->bigInteger('file_id')->autoIncrement();
            $table->bigInteger('issue_id');
            $table->string('file_name', 90);
            $table->string('file_type', 255);
            $table->bigInteger('file_size');
            $table->bigInteger('content_type');
            $table->string('original_file_name', 127)->nullable();
            $table->datetime('date_uploaded');
            $table->datetime('date_modified');
            $table->index(['issue_id'], 'issue_files_issue_id');
        });

        // Custom sequencing information for journal issues, when available
        Schema::create('custom_issue_orders', function (Blueprint $table) {
            $table->bigInteger('issue_id');
            $table->bigInteger('journal_id');
            $table->float('seq', 8, 2)->default(0);
            $table->unique(['issue_id'], 'custom_issue_orders_pkey');
        });

        // Custom sequencing information for journal sections by issue, when available.
        Schema::create('custom_section_orders', function (Blueprint $table) {
            $table->bigInteger('issue_id');
            $table->bigInteger('section_id');
            $table->float('seq', 8, 2)->default(0);
            $table->unique(['issue_id', 'section_id'], 'custom_section_orders_pkey');
        });

        // Publications
        Schema::create('publications', function (Blueprint $table) {
            $table->bigInteger('publication_id')->autoIncrement();
            $table->bigInteger('access_status')->default(0)->nullable();
            $table->date('date_published')->nullable();
            $table->datetime('last_modified')->nullable();
            $table->bigInteger('primary_contact_id')->nullable();
            $table->bigInteger('section_id')->nullable();
            $table->float('seq', 8, 2)->default(0);
            $table->bigInteger('submission_id');
            $table->smallInteger('status')->default(1); // PKPSubmission::STATUS_QUEUED
            $table->string('url_path', 64)->nullable();
            $table->bigInteger('version')->nullable();
            $table->bigInteger('doi_id')->nullable();
            $table->index(['submission_id'], 'publications_submission_id');
            $table->index(['section_id'], 'publications_section_id');
            $table->index(['url_path'], 'publications_url_path');
            $table->foreign('doi_id')->references('doi_id')->on('dois')->nullOnDelete();
        });

        // Publication galleys
        Schema::create('publication_galleys', function (Blueprint $table) {
            $table->bigInteger('galley_id')->autoIncrement();
            $table->string('locale', 14)->nullable();
            $table->bigInteger('publication_id');
            $table->string('label', 255)->nullable();
            $table->bigInteger('submission_file_id')->unsigned()->nullable();
            $table->float('seq', 8, 2)->default(0);
            $table->string('remote_url', 2047)->nullable();
            $table->smallInteger('is_approved')->default(0);
            $table->string('url_path', 64)->nullable();
            $table->bigInteger('doi_id')->nullable();
            $table->index(['publication_id'], 'publication_galleys_publication_id');
            $table->index(['url_path'], 'publication_galleys_url_path');
            $table->foreign('submission_file_id')->references('submission_file_id')->on('submission_files');
            $table->foreign('doi_id')->references('doi_id')->on('dois')->nullOnDelete();
        });

        // Galley metadata.
        Schema::create('publication_galley_settings', function (Blueprint $table) {
            $table->bigInteger('galley_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->text('setting_value')->nullable();
            $table->index(['galley_id'], 'publication_galley_settings_galley_id');
            $table->unique(['galley_id', 'locale', 'setting_name'], 'publication_galley_settings_pkey');
        });
        // Add partial index (DBMS-specific)
        switch (DB::getDriverName()) {
            case 'mysql': DB::unprepared('CREATE INDEX publication_galley_settings_name_value ON publication_galley_settings (setting_name(50), setting_value(150))'); break;
            case 'pgsql': DB::unprepared('CREATE INDEX publication_galley_settings_name_value ON publication_galley_settings (setting_name, setting_value)'); break;
        }

        // Subscription types.
        Schema::create('subscription_types', function (Blueprint $table) {
            $table->bigInteger('type_id')->autoIncrement();
            $table->bigInteger('journal_id');
            $table->float('cost', 8, 2);
            $table->string('currency_code_alpha', 3);
            $table->smallInteger('duration')->nullable();
            $table->smallInteger('format');
            $table->smallInteger('institutional')->default(0);
            $table->smallInteger('membership')->default(0);
            $table->smallInteger('disable_public_display');
            $table->float('seq', 8, 2);
        });

        // Locale-specific subscription type data
        Schema::create('subscription_type_settings', function (Blueprint $table) {
            $table->bigInteger('type_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->text('setting_value')->nullable();
            $table->string('setting_type', 6);
            $table->index(['type_id'], 'subscription_type_settings_type_id');
            $table->unique(['type_id', 'locale', 'setting_name'], 'subscription_type_settings_pkey');
        });

        // Journal subscriptions.
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->bigInteger('subscription_id')->autoIncrement();
            $table->bigInteger('journal_id');
            $table->bigInteger('user_id');
            $table->bigInteger('type_id');
            $table->date('date_start')->nullable();
            $table->datetime('date_end')->nullable();
            $table->smallInteger('status')->default(1);
            $table->string('membership', 40)->nullable();
            $table->string('reference_number', 40)->nullable();
            $table->text('notes')->nullable();
        });

        // Journal institutional subscriptions.
        Schema::create('institutional_subscriptions', function (Blueprint $table) {
            $table->bigInteger('institutional_subscription_id')->autoIncrement();
            $table->bigInteger('subscription_id');
            $table->string('institution_name', 255);
            $table->string('mailing_address', 255)->nullable();
            $table->string('domain', 255)->nullable();
            $table->index(['subscription_id'], 'institutional_subscriptions_subscription_id');
            $table->index(['domain'], 'institutional_subscriptions_domain');
        });

        // Journal institutional subscription IPs and IP ranges.
        Schema::create('institutional_subscription_ip', function (Blueprint $table) {
            $table->bigInteger('institutional_subscription_ip_id')->autoIncrement();
            $table->bigInteger('subscription_id');
            $table->string('ip_string', 40);
            $table->bigInteger('ip_start');
            $table->bigInteger('ip_end')->nullable();
            $table->index(['subscription_id'], 'institutional_subscription_ip_subscription_id');
            $table->index(['ip_start'], 'institutional_subscription_ip_start');
            $table->index(['ip_end'], 'institutional_subscription_ip_end');
        });

        // Logs queued (unfulfilled) payments.
        Schema::create('queued_payments', function (Blueprint $table) {
            $table->bigInteger('queued_payment_id')->autoIncrement();
            $table->datetime('date_created');
            $table->datetime('date_modified');
            $table->date('expiry_date')->nullable();
            $table->text('payment_data')->nullable();
        });

        // Logs completed (fulfilled) payments.
        Schema::create('completed_payments', function (Blueprint $table) {
            $table->bigInteger('completed_payment_id')->autoIncrement();
            $table->datetime('timestamp');
            $table->bigInteger('payment_type');
            $table->bigInteger('context_id');
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('assoc_id')->nullable();
            $table->float('amount', 8, 2);
            $table->string('currency_code_alpha', 3)->nullable();
            $table->string('payment_method_plugin_name', 80)->nullable();
        });

        // Add additional foreign key constraints once all tables have been created
        Schema::table('journals', function (Blueprint $table) {
            $table->foreign('current_issue_id')->references('issue_id')->on('issues');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::drop('completed_payments');
        Schema::drop('queued_payments');
        Schema::drop('institutional_subscription_ip');
        Schema::drop('institutional_subscriptions');
        Schema::drop('subscriptions');
        Schema::drop('subscription_type_settings');
        Schema::drop('subscription_types');
        Schema::drop('publication_galley_settings');
        Schema::drop('publication_galleys');
        Schema::drop('publications');
        Schema::drop('custom_section_orders');
        Schema::drop('custom_issue_orders');
        Schema::drop('issue_files');
        Schema::drop('issue_galley_settings');
        Schema::drop('issue_galleys');
        Schema::drop('issue_settings');
        Schema::drop('issues');
        Schema::drop('doi_settings');
        Schema::drop('dois');
        Schema::drop('section_settings');
        Schema::drop('sections');
        Schema::drop('journal_settings');
        Schema::drop('journals');
    }
}
