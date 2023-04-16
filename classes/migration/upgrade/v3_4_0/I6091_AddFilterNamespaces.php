<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6091_AddFilterNamespaces.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6091_AddFilterNamespaces
 *
 * @brief Describe upgrade/downgrade operations for introducing namespaces to the built-in set of filters.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Support\Facades\DB;

class I6091_AddFilterNamespaces extends \PKP\migration\Migration
{
    public const FILTER_RENAME_MAP = [
        // Application filters
        'plugins.importexport.doaj.filter.DOAJXmlFilter' => 'APP\plugins\importexport\doaj\filter\DOAJXmlFilter',
        'plugins.generic.datacite.filter.DataciteXmlFilter' => 'APP\plugins\generic\datacite\filter\DataciteXmlFilter',
        'plugins.importexport.native.filter.ArticleNativeXmlFilter' => 'APP\plugins\importexport\native\filter\ArticleNativeXmlFilter',
        'plugins.importexport.native.filter.NativeXmlArticleFilter' => 'APP\plugins\importexport\native\filter\NativeXmlArticleFilter',
        'plugins.importexport.native.filter.IssueNativeXmlFilter' => 'APP\plugins\importexport\native\filter\IssueNativeXmlFilter',
        'plugins.importexport.native.filter.NativeXmlIssueFilter' => 'APP\plugins\importexport\native\filter\NativeXmlIssueFilter',
        'plugins.importexport.native.filter.IssueGalleyNativeXmlFilter' => 'APP\plugins\importexport\native\filter\IssueGalleyNativeXmlFilter',
        'plugins.importexport.native.filter.NativeXmlIssueGalleyFilter' => 'APP\plugins\importexport\native\filter\NativeXmlIssueGalleyFilter',
        'plugins.importexport.native.filter.AuthorNativeXmlFilter' => 'APP\plugins\importexport\native\filter\AuthorNativeXmlFilter',
        'plugins.importexport.native.filter.NativeXmlAuthorFilter' => 'APP\plugins\importexport\native\filter\NativeXmlAuthorFilter',
        'plugins.importexport.native.filter.NativeXmlArticleFileFilter' => 'APP\plugins\importexport\native\filter\NativeXmlArticleFileFilter',
        'plugins.importexport.native.filter.ArticleGalleyNativeXmlFilter' => 'APP\plugins\importexport\native\filter\ArticleGalleyNativeXmlFilter',
        'plugins.importexport.native.filter.NativeXmlArticleGalleyFilter' => 'APP\plugins\importexport\native\filter\NativeXmlArticleGalleyFilter',
        'plugins.importexport.native.filter.PublicationNativeXmlFilter' => 'APP\plugins\importexport\native\filter\PublicationNativeXmlFilter',
        'plugins.importexport.native.filter.NativeXmlPublicationFilter' => 'APP\plugins\importexport\native\filter\NativeXmlPublicationFilter',
        'plugins.importexport.doaj.filter.DOAJJsonFilter' => 'APP\plugins\importexport\doaj\filter\DOAJJsonFilter',
        'plugins.importexport.pubmed.filter.ArticlePubMedXmlFilter' => 'APP\plugins\importexport\pubmed\filter\ArticlePubMedXmlFilter',
        'plugins.metadata.dc11.filter.Dc11SchemaArticleAdapter' => 'APP\plugins\metadata\dc11\filter\Dc11SchemaArticleAdapter',
        'plugins.generic.crossref.filter.IssueCrossrefXmlFilter' => 'APP\plugins\generic\crossref\filter\IssueCrossrefXmlFilter',
        'plugins.generic.crossref.filter.ArticleCrossrefXmlFilter' => 'APP\plugins\generic\crossref\filter\ArticleCrossrefXmlFilter',

        // pkp-lib filters
        'lib.pkp.plugins.importexport.users.filter.PKPUserUserXmlFilter' => 'PKP\plugins\importexport\users\filter\PKPUserUserXmlFilter',
        'lib.pkp.plugins.importexport.users.filter.UserXmlPKPUserFilter' => 'PKP\plugins\importexport\users\filter\UserXmlPKPUserFilter',
        'lib.pkp.plugins.importexport.users.filter.UserGroupNativeXmlFilter' => 'PKP\plugins\importexport\users\filter\UserGroupNativeXmlFilter',
        'lib.pkp.plugins.importexport.users.filter.NativeXmlUserGroupFilter' => 'PKP\plugins\importexport\users\filter\NativeXmlUserGroupFilter',
        'lib.pkp.plugins.importexport.native.filter.SubmissionFileNativeXmlFilter' => 'PKP\plugins\importexport\native\filter\SubmissionFileNativeXmlFilter',
    ];

    public const TASK_RENAME_MAP = [
        'lib.pkp.classes.task.ReviewReminder' => 'PKP\task\ReviewReminder',
        'lib.pkp.classes.task.StatisticsReport' => 'PKP\task\StatisticsReport',
        'classes.tasks.SubscriptionExpiryReminder' => 'APP\tasks\SubscriptionExpiryReminder',
        'lib.pkp.classes.task.DepositDois' => 'PKP\task\DepositDois',
        'lib.pkp.classes.task.RemoveUnvalidatedExpiredUsers' => 'PKP\task\RemoveUnvalidatedExpiredUsers',
        'lib.pkp.classes.task.EditorialReminders' => 'PKP\task\EditorialReminders',
        'lib.pkp.classes.task.UpdateIPGeoDB' => 'PKP\task\UpdateIPGeoDB',
        'classes.tasks.UsageStatsLoader' => 'APP\tasks\UsageStatsLoader',
        'plugins.importexport.doaj.DOAJInfoSender' => 'APP\plugins\importexport\doaj\DOAJInfoSender',
    ];

    /**
     * Run the migration.
     */
    public function up(): void
    {
        foreach (self::FILTER_RENAME_MAP as $oldName => $newName) {
            DB::statement('UPDATE filters SET class_name = ? WHERE class_name = ?', [$newName, $oldName]);
        }
        foreach (self::TASK_RENAME_MAP as $oldName => $newName) {
            DB::statement('UPDATE scheduled_tasks SET class_name = ? WHERE class_name = ?', [$newName, $oldName]);
        }
        DB::statement('UPDATE filter_groups SET output_type=? WHERE output_type = ?', ['metadata::APP\plugins\metadata\dc11\schema\Dc11Schema(ARTICLE)', 'metadata::plugins.metadata.dc11.schema.Dc11Schema(ARTICLE)']);
    }

    /**
     * Reverse the downgrades
     */
    public function down(): void
    {
        foreach (self::FILTER_RENAME_MAP as $oldName => $newName) {
            DB::statement('UPDATE filters SET class_name = ? WHERE class_name = ?', [$oldName, $newName]);
        }
        foreach (self::TASK_RENAME_MAP as $oldName => $newName) {
            DB::statement('UPDATE scheduled_tasks SET class_name = ? WHERE class_name = ?', [$oldName, $newName]);
        }
        DB::statement('UPDATE filter_groups SET output_type=? WHERE output_type = ?', ['metadata::plugins.metadata.dc11.schema.Dc11Schema(ARTICLE)', 'metadata::APP\plugins\metadata\dc11\schema\Dc11Schema(ARTICLE)']);
    }
}
