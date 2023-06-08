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

use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Facades\DB;

class PreflightCheckMigration extends \PKP\migration\upgrade\v3_4_0\PreflightCheckMigration
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
        try {
            // Clean orphaned sections entries by journal_id
            $orphanedIds = DB::table('sections AS s')->leftJoin('journals AS j', 's.journal_id', '=', 'j.journal_id')->whereNull('j.journal_id')->distinct()->pluck('s.journal_id');
            foreach ($orphanedIds as $journalId) {
                DB::table('sections')->where('journal_id', '=', $journalId)->delete();
            }

            // Clean orphaned sections entries by review_form_id
            $orphanedIds = DB::table('sections AS s')->leftJoin('review_forms AS rf', 's.review_form_id', '=', 'rf.review_form_id')->whereNull('rf.review_form_id')->whereNotNull('s.review_form_id')->distinct()->pluck('s.review_form_id');
            foreach ($orphanedIds as $reviewFormId) {
                DB::table('sections')->where('review_form_id', '=', $reviewFormId)->update(['review_form_id' => null]);
            }

            // Clean orphaned section_settings entries
            $orphanedIds = DB::table('section_settings AS ss')->leftJoin('sections AS s', 'ss.section_id', '=', 's.section_id')->whereNull('s.section_id')->distinct()->pluck('ss.section_id');
            foreach ($orphanedIds as $sectionId) {
                DB::table('section_settings')->where('section_id', '=', $sectionId)->delete();
            }

            // Clean orphaned issues entries by journal_id
            $orphanedIds = DB::table('issues AS i')->leftJoin('journals AS j', 'i.journal_id', '=', 'j.journal_id')->whereNull('j.journal_id')->distinct()->pluck('i.journal_id');
            foreach ($orphanedIds as $journalId) {
                DB::table('issues')->where('journal_id', '=', $journalId)->delete();
            }

            // Clean orphaned issue_settings entries
            $orphanedIds = DB::table('issue_settings AS is')->leftJoin('issues AS i', 'is.issue_id', '=', 'i.issue_id')->whereNull('i.issue_id')->distinct()->pluck('is.issue_id');
            foreach ($orphanedIds as $issueId) {
                DB::table('issue_settings')->where('issue_id', '=', $issueId)->delete();
            }

            // Clean orphaned issue_files entries by issue_id
            $orphanedIds = DB::table('issue_files AS i_f')->leftJoin('issues AS i', 'i.issue_id', '=', 'i_f.issue_id')->whereNull('i.issue_id')->distinct()->pluck('i_f.issue_id');
            foreach ($orphanedIds as $issueId) {
                DB::table('issue_files')->where('issue_id', '=', $issueId)->delete();
            }

            // Clean orphaned issue_galleys entries by issue_id
            $orphanedIds = DB::table('issue_galleys AS ig')->leftJoin('issues AS i', 'i.issue_id', '=', 'ig.issue_id')->whereNull('i.issue_id')->distinct()->pluck('ig.issue_id');
            foreach ($orphanedIds as $issueId) {
                DB::table('issue_galleys')->where('issue_id', '=', $issueId)->delete();
            }

            // Clean orphaned issue_galleys entries by file_id
            $orphanedIds = DB::table('issue_galleys AS ig')->leftJoin('issue_files AS i_f', 'i_f.file_id', '=', 'ig.file_id')->whereNull('i_f.file_id')->distinct()->pluck('ig.file_id');
            foreach ($orphanedIds as $fileId) {
                DB::table('issue_galleys')->where('file_id', '=', $fileId)->delete();
            }

            // Clean orphaned issue_galley_settings entries
            $orphanedIds = DB::table('issue_galley_settings AS igs')->leftJoin('issue_galleys AS ig', 'igs.galley_id', '=', 'ig.galley_id')->whereNull('ig.galley_id')->distinct()->pluck('igs.galley_id');
            foreach ($orphanedIds as $issueGalleyId) {
                DB::table('issue_galley_settings')->where('galley_id', '=', $issueGalleyId)->delete();
            }

            // Clean orphaned custom_issue_orders entries by issue_id
            $orphanedIds = DB::table('custom_issue_orders AS cio')->leftJoin('issues AS i', 'i.issue_id', '=', 'cio.issue_id')->whereNull('i.issue_id')->distinct()->pluck('cio.issue_id');
            foreach ($orphanedIds as $issueId) {
                DB::table('custom_issue_orders')->where('issue_id', '=', $issueId)->delete();
            }

            // Clean orphaned custom_issue_orders entries by journal_id
            $orphanedIds = DB::table('custom_issue_orders AS cio')->leftJoin('journals AS j', 'j.journal_id', '=', 'cio.journal_id')->whereNull('j.journal_id')->distinct()->pluck('cio.journal_id');
            foreach ($orphanedIds as $journalId) {
                DB::table('custom_issue_orders')->where('journal_id', '=', $journalId)->delete();
            }

            // Clean orphaned custom_section_orders entries by issue_id
            $orphanedIds = DB::table('custom_section_orders AS cso')->leftJoin('issues AS i', 'i.issue_id', '=', 'cso.issue_id')->whereNull('i.issue_id')->distinct()->pluck('cso.issue_id');
            foreach ($orphanedIds as $issueId) {
                DB::table('custom_section_orders')->where('issue_id', '=', $issueId)->delete();
            }

            // Clean orphaned custom_section_orders entries by section_id
            $orphanedIds = DB::table('custom_section_orders AS cso')->leftJoin('sections AS s', 's.section_id', '=', 'cso.section_id')->whereNull('s.section_id')->distinct()->pluck('cso.section_id');
            foreach ($orphanedIds as $sectionId) {
                DB::table('custom_section_orders')->where('section_id', '=', $sectionId)->delete();
            }

            // Clean orphaned publications entries by primary_contact_id
            switch (true) {
                case DB::connection() instanceof MySqlConnection:
                    DB::statement('UPDATE publications p LEFT JOIN authors a ON (p.primary_contact_id = a.author_id) SET p.primary_contact_id = NULL WHERE a.author_id IS NULL');
                    break;
                case DB::connection() instanceof PostgresConnection:
                    DB::statement('UPDATE publications SET primary_contact_id = NULL WHERE publication_id IN (SELECT p.publication_id FROM publications p LEFT JOIN authors a ON (p.primary_contact_id = a.author_id) WHERE a.author_id IS NULL AND p.primary_contact_id IS NOT NULL)');
                    break;
                default: throw new \Exception('Unknown database connection type!');
            }

            // Clean orphaned publication_galleys entries by publication_id
            $orphanedIds = DB::table('publication_galleys AS pg')->leftJoin('publications AS p', 'pg.publication_id', '=', 'p.publication_id')->whereNull('p.publication_id')->distinct()->pluck('pg.publication_id');
            foreach ($orphanedIds as $publicationId) {
                DB::table('publication_galleys')->where('publication_id', '=', $publicationId)->delete();
            }

            // Clean orphaned publication_galley_settings entries
            $orphanedIds = DB::table('publication_galley_settings AS pgs')->leftJoin('publication_galleys AS pg', 'pgs.galley_id', '=', 'pg.galley_id')->whereNull('pg.galley_id')->distinct()->pluck('pgs.galley_id');
            foreach ($orphanedIds as $galleyId) {
                DB::table('publication_galley_settings')->where('galley_id', '=', $galleyId)->delete();
            }

            // Clean orphaned subscription_types entries by journal_id
            $orphanedIds = DB::table('subscription_types AS st')->leftJoin('journals AS j', 'j.journal_id', '=', 'st.journal_id')->whereNull('j.journal_id')->distinct()->pluck('st.journal_id');
            foreach ($orphanedIds as $journalId) {
                DB::table('subscription_types')->where('journal_id', '=', $journalId)->delete();
            }

            // Clean orphaned subscription_type_settings entries
            $orphanedIds = DB::table('subscription_type_settings AS sts')->leftJoin('subscription_types AS st', 'sts.type_id', '=', 'st.type_id')->whereNull('st.type_id')->distinct()->pluck('sts.type_id');
            foreach ($orphanedIds as $typeId) {
                DB::table('subscription_type_settings')->where('type_id', '=', $typeId)->delete();
            }

            // Clean orphaned subscriptions entries by journal_id
            $orphanedIds = DB::table('subscriptions AS s')->leftJoin('journals AS j', 'j.journal_id', '=', 's.journal_id')->whereNull('j.journal_id')->distinct()->pluck('s.journal_id');
            foreach ($orphanedIds as $journalId) {
                DB::table('subscriptions')->where('journal_id', '=', $journalId)->delete();
            }

            // Clean orphaned subscriptions entries by user_id
            $orphanedIds = DB::table('subscriptions AS s')->leftJoin('users AS u', 'u.user_id', '=', 's.user_id')->whereNull('u.user_id')->distinct()->pluck('s.user_id');
            foreach ($orphanedIds as $userId) {
                DB::table('subscriptions')->where('user_id', '=', $userId)->delete();
            }

            // Clean orphaned subscriptions entries by type_id
            $orphanedIds = DB::table('subscriptions AS s')->leftJoin('subscription_types AS st', 'st.type_id', '=', 's.type_id')->whereNull('st.type_id')->distinct()->pluck('s.type_id');
            foreach ($orphanedIds as $typeId) {
                DB::table('subscriptions')->where('type_id', '=', $typeId)->delete();
            }

            // Clean orphaned institutional_subscriptions entries by subscription_id
            $orphanedIds = DB::table('institutional_subscriptions AS i_s')->leftJoin('subscriptions AS s', 'i_s.subscription_id', '=', 's.subscription_id')->whereNull('s.subscription_id')->distinct()->pluck('i_s.subscription_id');
            foreach ($orphanedIds as $subscriptionId) {
                DB::table('institutional_subscriptions')->where('subscription_id', '=', $subscriptionId)->delete();
            }

            // Clean orphaned completed_payments entries by context_id
            $orphanedIds = DB::table('completed_payments AS cp')->leftJoin('journals AS j', 'j.journal_id', '=', 'cp.context_id')->whereNull('j.journal_id')->distinct()->pluck('cp.context_id');
            foreach ($orphanedIds as $journalId) {
                DB::table('completed_payments')->where('context_id', '=', $journalId)->delete();
            }

            // Clean orphaned completed_payments entries by user_id
            $orphanedIds = DB::table('completed_payments AS cp')->leftJoin('users AS u', 'u.user_id', '=', 'cp.user_id')->whereNull('u.user_id')->distinct()->pluck('cp.user_id');
            foreach ($orphanedIds as $userId) {
                DB::table('completed_payments')->where('user_id', '=', $userId)->delete();
            }
        } catch (\Exception $e) {
            if ($fallbackVersion = $this->setFallbackVersion()) {
                $this->_installer->log("A pre-flight check failed. The software was successfully upgraded to {$fallbackVersion} but could not be upgraded further (to " . $this->_installer->newVersion->getVersionString() . '). Check and correct the error, then try again.');
            }
            throw $e;
        }
    }
}
