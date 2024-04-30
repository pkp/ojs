<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6093_AddForeignKeys.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6093_AddForeignKeys
 *
 * @brief Describe upgrade/downgrade operations for introducing foreign key definitions to existing database relationships.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class I6093_AddForeignKeys extends \PKP\migration\upgrade\v3_4_0\I6093_AddForeignKeys
{
    protected function getContextTable(): string
    {
        return 'journals';
    }

    protected function getContextKeyField(): string
    {
        return 'journal_id';
    }

    protected function getContextSettingsTable(): string
    {
        return 'journal_settings';
    }

    public function up(): void
    {
        parent::up();

        Schema::table('sections', function (Blueprint $table) {
            $table->foreign('review_form_id', 'sections_review_form_id')->references('review_form_id')->on('review_forms')->onDelete('set null');
            $table->foreign('journal_id', 'sections_journal_id')->references('journal_id')->on('journals')->onDelete('cascade');
        });

        Schema::table('section_settings', function (Blueprint $table) {
            $table->foreign('section_id', 'section_settings_section_id')->references('section_id')->on('sections')->onDelete('cascade');
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->foreign('journal_id', 'issues_journal_id')->references('journal_id')->on('journals')->onDelete('cascade');
        });

        Schema::table('issue_settings', function (Blueprint $table) {
            $table->foreign('issue_id', 'issue_settings_issue_id')->references('issue_id')->on('issues')->onDelete('cascade');
        });

        Schema::table('issue_files', function (Blueprint $table) {
            $table->foreign('issue_id', 'issue_files_issue_id')->references('issue_id')->on('issues')->onDelete('cascade');
        });

        Schema::table('issue_galleys', function (Blueprint $table) {
            $table->foreign('issue_id', 'issue_galleys_issue_id')->references('issue_id')->on('issues')->onDelete('cascade');
            $table->foreign('file_id', 'issue_galleys_file_id')->references('file_id')->on('issue_files')->onDelete('cascade');
            $table->index(['file_id'], 'issue_galleys_file_id');
        });

        Schema::table('issue_galley_settings', function (Blueprint $table) {
            $table->foreign('galley_id', 'issue_galleys_settings_galley_id')->references('galley_id')->on('issue_galleys')->onDelete('cascade');
        });

        Schema::table('custom_issue_orders', function (Blueprint $table) {
            $table->foreign('issue_id', 'custom_issue_orders_issue_id')->references('issue_id')->on('issues')->onDelete('cascade');
            $table->index(['issue_id'], 'custom_issue_orders_issue_id');
            $table->foreign('journal_id', 'custom_issue_orders_journal_id')->references('journal_id')->on('journals')->onDelete('cascade');
            $table->index(['journal_id'], 'custom_issue_orders_journal_id');
        });

        Schema::table('custom_section_orders', function (Blueprint $table) {
            $table->foreign('issue_id', 'custom_section_orders_issue_id')->references('issue_id')->on('issues')->onDelete('cascade');
            $table->index(['issue_id'], 'custom_section_orders_issue_id');
            $table->foreign('section_id', 'custom_section_orders_section_id')->references('section_id')->on('sections')->onDelete('cascade');
            $table->index(['section_id'], 'custom_section_orders_section_id');
        });

        Schema::table('publications', function (Blueprint $table) {
            $table->foreign('section_id', 'publications_section_id')->references('section_id')->on('sections')->onDelete('set null');
            $table->foreign('submission_id', 'publications_submission_id')->references('submission_id')->on('submissions')->onDelete('cascade');
            $table->foreign('primary_contact_id', 'publications_primary_contact_id')->references('author_id')->on('authors')->onDelete('set null');
            $table->index(['primary_contact_id'], 'publications_primary_contact_id');
        });

        // Attempt to drop the previous foreign key, which doesn't have the cascade rule
        if (DB::getDoctrineSchemaManager()->introspectTable('publication_galleys')->hasForeignKey('publication_galleys_submission_file_id_foreign')) {
            Schema::table('publication_galleys', fn (Blueprint $table) => $table->dropForeign('publication_galleys_submission_file_id_foreign'));
        }

        Schema::table('publication_galleys', function (Blueprint $table) {
            $table->foreign('publication_id', 'publication_galleys_publication_id')->references('publication_id')->on('publications')->onDelete('cascade');
            $table->index(['submission_file_id'], 'publication_galleys_submission_file_id');
        });

        Schema::table('publication_galley_settings', function (Blueprint $table) {
            $table->foreign('galley_id', 'publication_galley_settings_galley_id')->references('galley_id')->on('publication_galleys')->onDelete('cascade');
        });

        Schema::table('subscription_types', function (Blueprint $table) {
            $table->foreign('journal_id', 'subscription_types_journal_id')->references('journal_id')->on('journals')->onDelete('cascade');
            $table->index(['journal_id'], 'subscription_types_journal_id');
        });

        Schema::table('subscription_type_settings', function (Blueprint $table) {
            $table->foreign('type_id', 'subscription_type_settings_type_id')->references('type_id')->on('subscription_types')->onDelete('cascade');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreign('journal_id', 'subscriptions_journal_id')->references('journal_id')->on('journals')->onDelete('cascade');
            $table->index(['journal_id'], 'subscriptions_journal_id');
            $table->foreign('user_id', 'subscriptions_user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->index(['user_id'], 'subscriptions_user_id');
            $table->foreign('type_id', 'subscriptions_type_id')->references('type_id')->on('subscription_types')->onDelete('cascade');
            $table->index(['type_id'], 'subscriptions_type_id');
        });

        Schema::table('institutional_subscriptions', function (Blueprint $table) {
            $table->foreign('subscription_id', 'institutional_subscriptions_subscription_id')->references('subscription_id')->on('subscriptions')->onDelete('cascade');
        });

        Schema::table('completed_payments', function (Blueprint $table) {
            $table->foreign('context_id', 'completed_payments_context_id')->references('journal_id')->on('journals')->onDelete('cascade');
            $table->index(['context_id'], 'completed_payments_context_id');

            $table->foreign('user_id', 'completed_payments_user_id')->references('user_id')->on('users')->onDelete('set null');
            $table->index(['user_id'], 'completed_payments_user_id');
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->index(['current_issue_id'], 'journals_issue_id');
        });
    }
}
