<?php

/**
 * @file classes/migration/install/OJSMigration.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OJSMigration
 *
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
        // Journal sections.
        Schema::create('sections', function (Blueprint $table) {
            $table->comment('A list of all sections into which submissions can be organized, forming the table of contents.');
            $table->bigInteger('section_id')->autoIncrement();

            $table->bigInteger('journal_id');
            $table->foreign('journal_id', 'sections_journal_id')->references('journal_id')->on('journals')->onDelete('cascade');
            $table->index(['journal_id'], 'sections_journal_id');

            $table->bigInteger('review_form_id')->nullable();
            $table->foreign('review_form_id', 'sections_review_form_id')->references('review_form_id')->on('review_forms')->onDelete('set null');
            $table->index(['review_form_id'], 'sections_review_form_id');

            $table->float('seq', 8, 2)->default(0);
            $table->smallInteger('editor_restricted')->default(0);
            $table->smallInteger('meta_indexed')->default(0);
            $table->smallInteger('meta_reviewed')->default(1);
            $table->smallInteger('abstracts_not_required')->default(0);
            $table->smallInteger('hide_title')->default(0);
            $table->smallInteger('hide_author')->default(0);
            $table->smallInteger('is_inactive')->default(0);
            $table->bigInteger('abstract_word_count')->nullable();
        });

        // Section-specific settings
        Schema::create('section_settings', function (Blueprint $table) {
            $table->comment('More data about sections, including localized properties like section titles.');
            $table->bigIncrements('section_setting_id');

            $table->bigInteger('section_id');
            $table->foreign('section_id', 'section_settings_section_id')->references('section_id')->on('sections')->onDelete('cascade');
            $table->index(['section_id'], 'section_settings_section_id');

            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->mediumText('setting_value')->nullable();

            $table->unique(['section_id', 'locale', 'setting_name'], 'section_settings_unique');
        });

        // Journal issues.
        Schema::create('issues', function (Blueprint $table) {
            $table->comment('A list of all journal issues, with identifying information like year, number, volume, etc.');
            $table->bigInteger('issue_id')->autoIncrement();

            $table->bigInteger('journal_id');
            $table->foreign('journal_id', 'issues_journal_id')->references('journal_id')->on('journals')->onDelete('cascade');
            $table->index(['journal_id'], 'issues_journal_id');

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
            $table->foreign('doi_id')->references('doi_id')->on('dois')->nullOnDelete();
            $table->index(['doi_id'], 'issues_doi_id');

            $table->index(['url_path'], 'issues_url_path');
        });

        // Locale-specific issue data
        Schema::create('issue_settings', function (Blueprint $table) {
            $table->comment('More data about issues, including localized properties such as issue titles.');
            $table->bigIncrements('issue_setting_id');

            $table->bigInteger('issue_id');
            $table->foreign('issue_id', 'issue_settings_issue_id')->references('issue_id')->on('issues')->onDelete('cascade');
            $table->index(['issue_id'], 'issue_settings_issue_id');

            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->mediumText('setting_value')->nullable();

            $table->unique(['issue_id', 'locale', 'setting_name'], 'issue_settings_unique');
        });
        // Add partial index (DBMS-specific)
        switch (DB::getDriverName()) {
            case 'mysql': DB::unprepared('CREATE INDEX issue_settings_name_value ON issue_settings (setting_name(50), setting_value(150))');
                break;
            case 'pgsql': DB::unprepared("CREATE INDEX issue_settings_name_value ON issue_settings (setting_name, setting_value) WHERE setting_name IN ('medra::registeredDoi', 'datacite::registeredDoi')");
                break;
        }

        Schema::create('issue_files', function (Blueprint $table) {
            $table->comment('Relationships between issues and issue files, such as cover images.');
            $table->bigInteger('file_id')->autoIncrement();

            $table->bigInteger('issue_id');
            $table->foreign('issue_id', 'issue_files_issue_id')->references('issue_id')->on('issues')->onDelete('cascade');
            $table->index(['issue_id'], 'issue_files_issue_id');

            $table->string('file_name', 90);
            $table->string('file_type', 255);
            $table->bigInteger('file_size');
            $table->bigInteger('content_type');
            $table->string('original_file_name', 127)->nullable();
            $table->datetime('date_uploaded');
            $table->datetime('date_modified');
        });

        // Issue galleys.
        Schema::create('issue_galleys', function (Blueprint $table) {
            $table->comment('Issue galleys are representations of the entire issue in a single file, such as a complete issue PDF.');
            $table->bigInteger('galley_id')->autoIncrement();

            $table->string('locale', 14)->nullable();

            $table->bigInteger('issue_id');
            $table->foreign('issue_id', 'issue_galleys_issue_id')->references('issue_id')->on('issues')->onDelete('cascade');
            $table->index(['issue_id'], 'issue_galleys_issue_id');

            $table->bigInteger('file_id');
            $table->foreign('file_id', 'issue_galleys_file_id')->references('file_id')->on('issue_files')->onDelete('cascade');
            $table->index(['file_id'], 'issue_galleys_file_id');

            $table->string('label', 255)->nullable();
            $table->float('seq', 8, 2)->default(0);
            $table->string('url_path', 64)->nullable();

            $table->index(['url_path'], 'issue_galleys_url_path');
        });

        // Issue galley metadata.
        Schema::create('issue_galley_settings', function (Blueprint $table) {
            $table->comment('More data about issue galleys, including localized content such as labels.');
            $table->bigIncrements('issue_galley_setting_id');

            $table->bigInteger('galley_id');
            $table->foreign('galley_id', 'issue_galleys_settings_galley_id')->references('galley_id')->on('issue_galleys')->onDelete('cascade');
            $table->index(['galley_id'], 'issue_galley_settings_galley_id');

            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->mediumText('setting_value')->nullable();
            $table->string('setting_type', 6)->comment('(bool|int|float|string|object)');

            $table->unique(['galley_id', 'locale', 'setting_name'], 'issue_galley_settings_unique');
        });

        Schema::create('custom_issue_orders', function (Blueprint $table) {
            $table->comment('Ordering information for the issue list, when custom issue ordering is specified.');
            $table->bigIncrements('custom_issue_order_id');

            $table->bigInteger('issue_id');
            $table->foreign('issue_id', 'custom_issue_orders_issue_id')->references('issue_id')->on('issues')->onDelete('cascade');
            $table->index(['issue_id'], 'custom_issue_orders_issue_id');

            $table->bigInteger('journal_id');
            $table->foreign('journal_id', 'custom_issue_orders_journal_id')->references('journal_id')->on('journals')->onDelete('cascade');
            $table->index(['journal_id'], 'custom_issue_orders_journal_id');

            $table->float('seq', 8, 2)->default(0);

            $table->unique(['issue_id'], 'custom_issue_orders_unique');
        });

        Schema::create('custom_section_orders', function (Blueprint $table) {
            $table->comment('Ordering information for sections within issues, when issue-specific section ordering is specified.');
            $table->bigIncrements('custom_section_order_id');

            $table->bigInteger('issue_id');
            $table->foreign('issue_id', 'custom_section_orders_issue_id')->references('issue_id')->on('issues')->onDelete('cascade');
            $table->index(['issue_id'], 'custom_section_orders_issue_id');

            $table->bigInteger('section_id');
            $table->foreign('section_id', 'custom_section_orders_section_id')->references('section_id')->on('sections')->onDelete('cascade');
            $table->index(['section_id'], 'custom_section_orders_section_id');

            $table->float('seq', 8, 2)->default(0);

            $table->unique(['issue_id', 'section_id'], 'custom_section_orders_unique');
        });

        // Publications
        Schema::create('publications', function (Blueprint $table) {
            $table->comment('Each publication is one version of a submission.');
            $table->bigInteger('publication_id')->autoIncrement();

            $table->bigInteger('access_status')->default(0)->nullable();
            $table->date('date_published')->nullable();
            $table->datetime('last_modified')->nullable();

            $table->bigInteger('primary_contact_id')->nullable();
            $table->foreign('primary_contact_id', 'publications_primary_contact_id')->references('author_id')->on('authors')->onDelete('set null');
            $table->index(['primary_contact_id'], 'publications_primary_contact_id');

            $table->bigInteger('section_id')->nullable();
            $table->foreign('section_id', 'publications_section_id')->references('section_id')->on('sections')->onDelete('set null');
            $table->index(['section_id'], 'publications_section_id');

            $table->float('seq', 8, 2)->default(0);

            $table->bigInteger('submission_id');
            $table->foreign('submission_id', 'publications_submission_id')->references('submission_id')->on('submissions')->onDelete('cascade');
            $table->index(['submission_id'], 'publications_submission_id');

            $table->smallInteger('status')->default(1); // PKPSubmission::STATUS_QUEUED
            $table->string('url_path', 64)->nullable();
            $table->bigInteger('version')->nullable();

            $table->bigInteger('doi_id')->nullable();
            $table->foreign('doi_id')->references('doi_id')->on('dois')->nullOnDelete();
            $table->index(['doi_id'], 'publications_doi_id');

            $table->index(['url_path'], 'publications_url_path');
        });
        // The following foreign key relationships are for tables defined in SubmissionsMigration
        // but they depend on publications to exist so are created here.
        Schema::table('submissions', function (Blueprint $table) {
            $table->foreign('current_publication_id', 'submissions_publication_id')->references('publication_id')->on('publications')->onDelete('set null');
            $table->index(['current_publication_id'], 'submissions_publication_id');
        });
        Schema::table('publication_settings', function (Blueprint $table) {
            $table->foreign('publication_id', 'publication_settings_publication_id')->references('publication_id')->on('publications')->onDelete('cascade');
            $table->index(['publication_id'], 'publication_settings_publication_id');
        });
        Schema::table('authors', function (Blueprint $table) {
            $table->foreign('publication_id')->references('publication_id')->on('publications')->onDelete('cascade');
            $table->index(['publication_id'], 'authors_publication_id');
        });
        // Publication galleys
        Schema::create('publication_galleys', function (Blueprint $table) {
            $table->comment('Publication galleys are representations of a publication in a specific format, e.g. a PDF.');
            $table->bigInteger('galley_id')->autoIncrement();
            $table->string('locale', 14)->nullable();

            $table->bigInteger('publication_id');
            $table->foreign('publication_id', 'publication_galleys_publication_id')->references('publication_id')->on('publications')->onDelete('cascade');
            $table->index(['publication_id'], 'publication_galleys_publication_id');

            $table->string('label', 255)->nullable();

            $table->bigInteger('submission_file_id')->unsigned()->nullable();
            $table->foreign('submission_file_id')->references('submission_file_id')->on('submission_files');
            $table->index(['submission_file_id'], 'publication_galleys_submission_file_id');

            $table->float('seq', 8, 2)->default(0);
            $table->string('remote_url', 2047)->nullable();
            $table->smallInteger('is_approved')->default(0);
            $table->string('url_path', 64)->nullable();

            $table->bigInteger('doi_id')->nullable();
            $table->foreign('doi_id')->references('doi_id')->on('dois')->nullOnDelete();
            $table->index(['doi_id'], 'publication_galleys_doi_id');

            $table->index(['url_path'], 'publication_galleys_url_path');
        });

        // Galley metadata.
        Schema::create('publication_galley_settings', function (Blueprint $table) {
            $table->comment('More data about publication galleys, including localized content such as labels.');
            $table->bigIncrements('publication_galley_setting_id');

            $table->bigInteger('galley_id');
            $table->foreign('galley_id', 'publication_galley_settings_galley_id')->references('galley_id')->on('publication_galleys')->onDelete('cascade');
            $table->index(['galley_id'], 'publication_galley_settings_galley_id');

            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->mediumText('setting_value')->nullable();

            $table->unique(['galley_id', 'locale', 'setting_name'], 'publication_galley_settings_unique');
        });
        // Add partial index (DBMS-specific)
        switch (DB::getDriverName()) {
            case 'mysql': DB::unprepared('CREATE INDEX publication_galley_settings_name_value ON publication_galley_settings (setting_name(50), setting_value(150))');
                break;
            case 'pgsql': DB::unprepared('CREATE INDEX publication_galley_settings_name_value ON publication_galley_settings (setting_name, setting_value)');
                break;
        }

        // Subscription types.
        Schema::create('subscription_types', function (Blueprint $table) {
            $table->comment('Subscription types represent the kinds of subscriptions that a user or institution may have, such as an annual subscription or a discounted subscription.');
            $table->bigInteger('type_id')->autoIncrement();

            $table->bigInteger('journal_id');
            $table->foreign('journal_id', 'subscription_types_journal_id')->references('journal_id')->on('journals')->onDelete('cascade');
            $table->index(['journal_id'], 'subscription_types_journal_id');

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
            $table->comment('More data about subscription types, including localized properties such as names.');
            $table->bigIncrements('subscription_type_setting_id');

            $table->bigInteger('type_id');
            $table->foreign('type_id', 'subscription_type_settings_type_id')->references('type_id')->on('subscription_types')->onDelete('cascade');
            $table->index(['type_id'], 'subscription_type_settings_type_id');

            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->mediumText('setting_value')->nullable();
            $table->string('setting_type', 6);

            $table->unique(['type_id', 'locale', 'setting_name'], 'subscription_type_settings_unique');
        });

        // Journal subscriptions.
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->comment('A list of subscriptions, both institutional and individual, for journals that use subscription-based publishing.');
            $table->bigInteger('subscription_id')->autoIncrement();

            $table->bigInteger('journal_id');
            $table->foreign('journal_id', 'subscriptions_journal_id')->references('journal_id')->on('journals')->onDelete('cascade');
            $table->index(['journal_id'], 'subscriptions_journal_id');

            $table->bigInteger('user_id');
            $table->foreign('user_id', 'subscriptions_user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->index(['user_id'], 'subscriptions_user_id');

            $table->bigInteger('type_id');
            $table->foreign('type_id', 'subscriptions_type_id')->references('type_id')->on('subscription_types')->onDelete('cascade');
            $table->index(['type_id'], 'subscriptions_type_id');

            $table->date('date_start')->nullable();
            $table->datetime('date_end')->nullable();
            $table->smallInteger('status')->default(1);
            $table->string('membership', 40)->nullable();
            $table->string('reference_number', 40)->nullable();
            $table->text('notes')->nullable();
        });

        // Journal institutional subscriptions.
        Schema::create('institutional_subscriptions', function (Blueprint $table) {
            $table->comment('A list of institutional subscriptions, linking a subscription with an institution.');
            $table->bigInteger('institutional_subscription_id')->autoIncrement();

            $table->bigInteger('subscription_id');
            $table->foreign('subscription_id', 'institutional_subscriptions_subscription_id')->references('subscription_id')->on('subscriptions')->onDelete('cascade');
            $table->index(['subscription_id'], 'institutional_subscriptions_subscription_id');

            $table->bigInteger('institution_id');
            $table->foreign('institution_id')->references('institution_id')->on('institutions')->onDelete('cascade');
            $table->index(['institution_id'], 'institutional_subscriptions_institution_id');

            $table->string('mailing_address', 255)->nullable();
            $table->string('domain', 255)->nullable();

            $table->index(['domain'], 'institutional_subscriptions_domain');
        });

        // Logs queued (unfulfilled) payments.
        Schema::create('queued_payments', function (Blueprint $table) {
            $table->comment('Unfulfilled (queued) payments, i.e. payments that have not yet been completed via an online payment system.');
            $table->bigInteger('queued_payment_id')->autoIncrement();
            $table->datetime('date_created');
            $table->datetime('date_modified');
            $table->date('expiry_date')->nullable();
            $table->text('payment_data')->nullable();
        });

        // Logs completed (fulfilled) payments.
        Schema::create('completed_payments', function (Blueprint $table) {
            $table->comment('A list of completed (fulfilled) payments relating to a payment type such as a subscription payment.');
            $table->bigInteger('completed_payment_id')->autoIncrement();
            $table->datetime('timestamp');
            $table->bigInteger('payment_type');

            $table->bigInteger('context_id');
            $table->foreign('context_id', 'completed_payments_context_id')->references('journal_id')->on('journals')->onDelete('cascade');
            $table->index(['context_id'], 'completed_payments_context_id');

            $table->bigInteger('user_id')->nullable();
            $table->foreign('user_id', 'completed_payments_user_id')->references('user_id')->on('users')->onDelete('set null');
            $table->index(['user_id'], 'completed_payments_user_id');

            $table->bigInteger('assoc_id')->nullable();
            $table->float('amount', 8, 2);
            $table->string('currency_code_alpha', 3)->nullable();
            $table->string('payment_method_plugin_name', 80)->nullable();
        });

        // Add additional foreign key constraints once all tables have been created
        Schema::table('journals', function (Blueprint $table) {
            $table->foreign('current_issue_id')->references('issue_id')->on('issues')->onDelete('set null');
            $table->index(['current_issue_id'], 'journals_issue_id');
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
        Schema::drop('section_settings');
        Schema::drop('sections');
    }
}
