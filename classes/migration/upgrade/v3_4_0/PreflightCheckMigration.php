<?php

/**
 * @file classes/migration/upgrade/v3_4_0/PreflightCheckMigration.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PreflightCheckMigration
 *
 * @brief Check for common problems early in the upgrade process.
 */

namespace APP\migration\upgrade\v3_4_0;

use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PreflightCheckMigration extends \PKP\migration\upgrade\v3_4_0\PreflightCheckMigration
{
    public function up(): void
    {
        parent::up();
        try {
            $this->checkDuplicateDoiRegistrationAgencies();
        } catch (Throwable $e) {
            if ($fallbackVersion = $this->setFallbackVersion()) {
                $this->_installer->log("A pre-flight check failed. The software was successfully upgraded to {$fallbackVersion} but could not be upgraded further (to " . $this->_installer->newVersion->getVersionString() . '). Check and correct the error, then try again.');
            }
            throw $e;
        }
    }

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

    protected function buildOrphanedEntityProcessor(): void
    {
        parent::buildOrphanedEntityProcessor();

        $this->addTableProcessor('issues', function (): int {
            $affectedRows = 0;
            // Depends directly on ~2 entities: doi_id->dois.doi_id(not found in previous version) journal_id->journals.journal_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('issues', $this->getContextKeyField(), $this->getContextTable(), $this->getContextKeyField());
            return $affectedRows;
        });

        // Shared processor (there's another handler for this table at pkp-lib)
        $this->addTableProcessor('publications', function (): int {
            $affectedRows = 0;
            // Depends directly on ~4 entities: primary_contact_id->authors.author_id doi_id->dois.doi_id(not found in previous version) section_id->sections.section_id submission_id->submissions.submission_id
            // Custom field (not found in at least one of the softwares)

            // Attempts to recover the field publications.section_id before discarding the entry
            $rows = DB::table('publications AS p')
                ->leftJoin('sections AS s', 's.section_id', '=', 'p.section_id')
                ->join('submissions AS sub', 'sub.submission_id', '=', 'p.submission_id')
                ->whereNull('s.section_id')
                ->select('p.submission_id', 'p.publication_id', 'p.section_id')
                ->selectSub(
                    fn (Builder $q) => $q
                        ->from('sections AS s')
                        ->where('s.is_inactive', '=', 0)
                        ->whereColumn('s.journal_id', '=', 'sub.context_id')
                        ->selectRaw('MIN(s.section_id)'),
                    'new_section_id'
                )
                ->get();
            foreach ($rows as $row) {
                $this->_installer->log("The publication ID ({$row->publication_id}) for the submission ID {$row->submission_id} is assigned to an invalid section ID \"{$row->section_id}\", its section will be updated to {$row->new_section_id}");
                $affectedRows += DB::table('publications')->where('publication_id', '=', $row->publication_id)->update(['section_id' => $row->new_section_id]);
            }
            $affectedRows += $this->deleteOptionalReference('publications', 'section_id', 'sections', 'section_id');
            // Remaining cleanups are inherited
            return $affectedRows;
        });

        $this->addTableProcessor('publication_galleys', function (): int {
            $affectedRows = 0;
            // Depends directly on ~3 entities: doi_id->dois.doi_id(not found in previous version) publication_id->publications.publication_id submission_file_id->submission_files.submission_file_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('publication_galleys', 'publication_id', 'publications', 'publication_id');
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteOptionalReference('publication_galleys', 'submission_file_id', 'submission_files', 'submission_file_id');
            return $affectedRows;
        });

        $this->addTableProcessor('issue_galleys', function (): int {
            $affectedRows = 0;
            // Depends directly on ~2 entities: file_id->issue_files.file_id issue_id->issues.issue_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('issue_galleys', 'issue_id', 'issues', 'issue_id');
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('issue_galleys', 'file_id', 'issue_files', 'file_id');
            return $affectedRows;
        });

        $this->addTableProcessor('sections', function (): int {
            $affectedRows = 0;
            // Depends directly on ~2 entities: journal_id->journals.journal_id review_form_id->review_forms.review_form_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('sections', $this->getContextKeyField(), $this->getContextTable(), $this->getContextKeyField());
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->cleanOptionalReference('sections', 'review_form_id', 'review_forms', 'review_form_id');
            return $affectedRows;
        });

        $this->addTableProcessor('subscription_types', function (): int {
            $affectedRows = 0;
            // Depends directly on ~1 entities: journal_id->journals.journal_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('subscription_types', $this->getContextKeyField(), $this->getContextTable(), $this->getContextKeyField());
            return $affectedRows;
        });

        $this->addTableProcessor('issue_files', function (): int {
            $affectedRows = 0;
            // Depends directly on ~1 entities: issue_id->issues.issue_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('issue_files', 'issue_id', 'issues', 'issue_id');
            return $affectedRows;
        });

        $this->addTableProcessor('subscriptions', function (): int {
            $affectedRows = 0;
            // Depends directly on ~3 entities: journal_id->journals.journal_id type_id->subscription_types.type_id user_id->users.user_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('subscriptions', 'user_id', 'users', 'user_id');
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('subscriptions', 'type_id', 'subscription_types', 'type_id');
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('subscriptions', $this->getContextKeyField(), $this->getContextTable(), $this->getContextKeyField());
            return $affectedRows;
        });

        $this->addTableProcessor('completed_payments', function (): int {
            $affectedRows = 0;
            // Depends directly on ~2 entities: context_id->journals.journal_id user_id->users.user_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('completed_payments', 'context_id', $this->getContextTable(), $this->getContextKeyField());
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteOptionalReference('completed_payments', 'user_id', 'users', 'user_id');
            return $affectedRows;
        });

        $this->addTableProcessor('custom_issue_orders', function (): int {
            $affectedRows = 0;
            // Depends directly on ~2 entities: issue_id->issues.issue_id journal_id->journals.journal_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('custom_issue_orders', $this->getContextKeyField(), $this->getContextTable(), $this->getContextKeyField());
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('custom_issue_orders', 'issue_id', 'issues', 'issue_id');
            return $affectedRows;
        });

        $this->addTableProcessor('custom_section_orders', function (): int {
            $affectedRows = 0;
            // Depends directly on ~2 entities: issue_id->issues.issue_id section_id->sections.section_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('custom_section_orders', 'section_id', 'sections', 'section_id');
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('custom_section_orders', 'issue_id', 'issues', 'issue_id');
            return $affectedRows;
        });

        $this->addTableProcessor('institutional_subscriptions', function (): int {
            $affectedRows = 0;
            // Depends directly on ~2 entities: institution_id->institutions.institution_id(not found in previous version) subscription_id->subscriptions.subscription_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('institutional_subscriptions', 'subscription_id', 'subscriptions', 'subscription_id');
            return $affectedRows;
        });

        $this->addTableProcessor('issue_galley_settings', function (): int {
            $affectedRows = 0;
            // Depends directly on ~1 entities: galley_id->issue_galleys.galley_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('issue_galley_settings', 'galley_id', 'issue_galleys', 'galley_id');
            return $affectedRows;
        });

        $this->addTableProcessor('issue_settings', function (): int {
            $affectedRows = 0;
            // Depends directly on ~1 entities: issue_id->issues.issue_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('issue_settings', 'issue_id', 'issues', 'issue_id');
            return $affectedRows;
        });

        $this->addTableProcessor('publication_galley_settings', function (): int {
            $affectedRows = 0;
            // Depends directly on ~1 entities: galley_id->publication_galleys.galley_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('publication_galley_settings', 'galley_id', 'publication_galleys', 'galley_id');
            return $affectedRows;
        });

        $this->addTableProcessor('section_settings', function (): int {
            $affectedRows = 0;
            // Depends directly on ~1 entities: section_id->sections.section_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('section_settings', 'section_id', 'sections', 'section_id');
            return $affectedRows;
        });

        $this->addTableProcessor('subscription_type_settings', function (): int {
            $affectedRows = 0;
            // Depends directly on ~1 entities: type_id->subscription_types.type_id
            // Custom field (not found in at least one of the softwares)
            $affectedRows += $this->deleteRequiredReference('subscription_type_settings', 'type_id', 'subscription_types', 'type_id');
            return $affectedRows;
        });

        // Support for the issueId setting
        $this->addTableProcessor('publication_settings', function (): int {
            $affectedRows = 0;
            $rows = DB::table('publications AS p')
                ->join('publication_settings AS ps', 'ps.publication_id', '=', 'p.publication_id')
                ->leftJoin('issues AS i', DB::raw('CAST(i.issue_id AS CHAR(20))'), '=', 'ps.setting_value')
                ->where('ps.setting_name', 'issueId')
                ->whereNull('i.issue_id')
                ->get(['p.submission_id', 'p.publication_id', 'ps.setting_value']);
            foreach ($rows as $row) {
                $this->_installer->log("The publication ID ({$row->publication_id}) for the submission ID {$row->submission_id} is assigned to an invalid issue ID \"{$row->setting_value}\", its value will be updated to NULL");
                $affectedRows += DB::table('publication_settings')
                    ->where('publication_id', '=', $row->publication_id)
                    ->where('setting_name', 'issueId')
                    ->where('setting_value', $row->setting_value)
                    ->delete();
            }
            return $affectedRows;
        });
    }

    protected function getEntityRelationships(): array
    {
        return [
            $this->getContextTable() => ['submissions', 'issues', 'user_groups', 'sections', 'categories', 'subscription_types', 'navigation_menu_items', 'genres', 'filters', 'announcement_types', 'subscriptions', 'notifications', 'navigation_menus', 'library_files', 'email_templates', 'user_group_stage', 'subeditor_submission_group', 'plugin_settings', 'notification_subscription_settings', $this->getContextSettingsTable(), 'custom_issue_orders', 'completed_payments'],
            'users' => ['submission_files', 'review_assignments', 'subscriptions', 'notifications', 'event_log', 'email_log', 'user_user_groups', 'user_settings', 'user_interests', 'temporary_files', 'submission_comments', 'subeditor_submission_group', 'stage_assignments', 'sessions', 'query_participants', 'notification_subscription_settings', 'notes', 'email_log_users', 'edit_decisions', 'completed_payments', 'access_keys'],
            'submissions' => ['submission_files', 'publications', 'review_rounds', 'review_assignments', 'submission_search_objects', 'library_files', 'submission_settings', 'submission_comments', 'stage_assignments', 'review_round_files', 'edit_decisions'],
            'submission_files' => ['submission_files', 'publication_galleys', 'submission_file_settings', 'submission_file_revisions', 'review_round_files', 'review_files'],
            // publication_settings dependency added manually
            'issues' => [$this->getContextTable(), 'issue_galleys', 'issue_files', 'issue_settings', 'custom_section_orders', 'custom_issue_orders', 'publication_settings'],
            'user_groups' => ['authors', 'user_user_groups', 'user_group_stage', 'user_group_settings', 'subeditor_submission_group', 'stage_assignments'],
            'publications' => ['submissions', 'publication_galleys', 'authors', 'citations', 'publication_settings', 'publication_categories'],
            'publication_galleys' => ['publication_galley_settings'],
            'review_forms' => ['sections', 'review_form_elements', 'review_assignments', 'review_form_settings'],
            'categories' => ['categories', 'publication_categories', 'category_settings'],
            'issue_galleys' => ['issue_galley_settings'],
            'sections' => ['publications', 'section_settings', 'custom_section_orders'],
            'review_rounds' => ['review_assignments', 'review_round_files', 'edit_decisions'],
            'navigation_menu_item_assignments' => ['navigation_menu_item_assignments', 'navigation_menu_item_assignment_settings'],
            'authors' => ['publications', 'author_settings'],
            'controlled_vocab_entries' => ['user_interests', 'controlled_vocab_entry_settings'],
            'data_object_tombstones' => ['data_object_tombstone_settings', 'data_object_tombstone_oai_set_objects'],
            'files' => ['submission_files', 'submission_file_revisions'],
            'filters' => ['filters', 'filter_settings'],
            'genres' => ['submission_files', 'genre_settings'],
            'announcement_types' => ['announcements', 'announcement_type_settings'],
            'navigation_menu_items' => ['navigation_menu_item_assignments', 'navigation_menu_item_settings'],
            'review_assignments' => ['review_form_responses', 'review_files'],
            'review_form_elements' => ['review_form_responses', 'review_form_element_settings'],
            'subscription_types' => ['subscriptions', 'subscription_type_settings'],
            'announcements' => ['announcement_settings'],
            'queries' => ['query_participants'],
            'navigation_menus' => ['navigation_menu_item_assignments'],
            'notifications' => ['notification_settings'],
            'filter_groups' => ['filters'],
            'event_log' => ['event_log_settings'],
            'email_templates' => ['email_templates_settings'],
            'static_pages' => ['static_page_settings'],
            'email_log' => ['email_log_users'],
            'submission_search_keyword_list' => ['submission_search_object_keywords'],
            'submission_search_objects' => ['submission_search_object_keywords'],
            'controlled_vocabs' => ['controlled_vocab_entries'],
            'library_files' => ['library_file_settings'],
            'subscriptions' => ['institutional_subscriptions'],
            'citations' => ['citation_settings'],
            'issue_files' => ['issue_galleys']
        ];
    }

    protected function dropForeignKeys(): void
    {
        parent::dropForeignKeys();
        if (DB::getDoctrineSchemaManager()->introspectTable('publication_galleys')->hasForeignKey('publication_galleys_submission_file_id_foreign')) {
            Schema::table('publication_galleys', fn (Blueprint $table) => $table->dropForeign('publication_galleys_submission_file_id_foreign'));
        }
    }

    /**
     * Checks if DOIs have been marked registered with more than one registration agency.
     *
     * @throws Exception
     */
    protected function checkDuplicateDoiRegistrationAgencies(): void
    {
        $agencies = ['crossref::status', 'datacite::status', 'medra::status'];

        $submissionIds = DB::table('submission_settings')
            ->whereIn('setting_name', $agencies)
            ->groupBy('submission_id')
            ->havingRaw('COUNT(submission_id) > 1')
            ->select(['submission_id'])
            ->get();

        $galleyIds = DB::table('publication_galley_settings')
            ->whereIn('setting_name', $agencies)
            ->groupBy('galley_id')
            ->havingRaw('COUNT(galley_id) > 1')
            ->select(['galley_id'])
            ->get();

        $issueIds = DB::table('issue_settings')
            ->whereIn('setting_name', $agencies)
            ->groupBy('issue_id')
            ->havingRaw('COUNT(issue_id) > 1')
            ->select(['issue_id'])
            ->get();

        if ($submissionIds->count() > 0 || $galleyIds->count() > 0 || $issueIds->count() > 0) {
            throw new Exception('Some DOIs have been registered with multiple registration agencies. Resolve duplicates before continuing by running `php tools/resolveAgencyDuplicates.php`.');
        }
    }
}
