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
            issues: {
                // Depends directly on ~2 entities: doi_id->dois.doi_id(not found in previous version) journal_id->journals.journal_id
                // Dependent entities: ~8
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('issues', $this->getContextKeyField(), $this->getContextTable(), $this->getContextKeyField());
            }

            publications: {
                // Depends directly on ~4 entities: primary_contact_id->authors.author_id doi_id->dois.doi_id(not found in previous version) section_id->sections.section_id submission_id->submissions.submission_id
                // Dependent entities: ~6
                // Custom field (not found in at least one of the softwares)
                $this->cleanOptionalReference('publications', 'section_id', 'sections', 'section_id');
                // Remaining cleanups are inherited
            }

            publication_galleys: {
                // Depends directly on ~3 entities: doi_id->dois.doi_id(not found in previous version) publication_id->publications.publication_id submission_file_id->submission_files.submission_file_id
                // Dependent entities: ~5
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('publication_galleys', 'publication_id', 'publications', 'publication_id');
                // Custom field (not found in at least one of the softwares)
                $this->deleteOptionalReference('publication_galleys', 'submission_file_id', 'submission_files', 'submission_file_id');
                // Deprecated/moved field (not found on previous software version)
                // $this->deleteOptionalReference('publication_galleys', 'doi_id', 'dois', 'doi_id');
            }

            issue_galleys: {
                // Depends directly on ~2 entities: file_id->issue_files.file_id issue_id->issues.issue_id
                // Dependent entities: ~3
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('issue_galleys', 'issue_id', 'issues', 'issue_id');
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('issue_galleys', 'file_id', 'issue_files', 'file_id');
            }

            sections: {
                // Depends directly on ~2 entities: journal_id->journals.journal_id review_form_id->review_forms.review_form_id
                // Dependent entities: ~3
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('sections', $this->getContextKeyField(), $this->getContextTable(), $this->getContextKeyField());
                // Custom field (not found in at least one of the softwares)
                $this->cleanOptionalReference('sections', 'review_form_id', 'review_forms', 'review_form_id');
            }

            subscription_types: {
                // Depends directly on ~1 entities: journal_id->journals.journal_id
                // Dependent entities: ~2
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('subscription_types', $this->getContextKeyField(), $this->getContextTable(), $this->getContextKeyField());
            }

            issue_files: {
                // Depends directly on ~1 entities: issue_id->issues.issue_id
                // Dependent entities: ~1
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('issue_files', 'issue_id', 'issues', 'issue_id');
            }

            subscriptions: {
                // Depends directly on ~3 entities: journal_id->journals.journal_id type_id->subscription_types.type_id user_id->users.user_id
                // Dependent entities: ~1
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('subscriptions', 'user_id', 'users', 'user_id');
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('subscriptions', 'type_id', 'subscription_types', 'type_id');
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('subscriptions', $this->getContextKeyField(), $this->getContextTable(), $this->getContextKeyField());
            }

            completed_payments: {
                // Depends directly on ~2 entities: context_id->journals.journal_id user_id->users.user_id
                // Dependent entities: ~0
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('completed_payments', 'context_id', $this->getContextTable(), $this->getContextKeyField());
                // Custom field (not found in at least one of the softwares)
                $this->deleteOptionalReference('completed_payments', 'user_id', 'users', 'user_id');
            }

            custom_issue_orders: {
                // Depends directly on ~2 entities: issue_id->issues.issue_id journal_id->journals.journal_id
                // Dependent entities: ~0
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('custom_issue_orders', $this->getContextKeyField(), $this->getContextTable(), $this->getContextKeyField());
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('custom_issue_orders', 'issue_id', 'issues', 'issue_id');
            }

            custom_section_orders: {
                // Depends directly on ~2 entities: issue_id->issues.issue_id section_id->sections.section_id
                // Dependent entities: ~0
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('custom_section_orders', 'section_id', 'sections', 'section_id');
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('custom_section_orders', 'issue_id', 'issues', 'issue_id');
            }

            institutional_subscriptions: {
                // Depends directly on ~2 entities: institution_id->institutions.institution_id(not found in previous version) subscription_id->subscriptions.subscription_id
                // Dependent entities: ~0
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('institutional_subscriptions', 'subscription_id', 'subscriptions', 'subscription_id');
                // Deprecated/moved field (not found on previous software version)
                // $this->deleteRequiredReference('institutional_subscriptions', 'institution_id', 'institutions', 'institution_id');
            }

            issue_galley_settings: {
                // Depends directly on ~1 entities: galley_id->issue_galleys.galley_id
                // Dependent entities: ~0
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('issue_galley_settings', 'galley_id', 'issue_galleys', 'galley_id');
            }

            issue_settings: {
                // Depends directly on ~1 entities: issue_id->issues.issue_id
                // Dependent entities: ~0
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('issue_settings', 'issue_id', 'issues', 'issue_id');
            }

            publication_galley_settings: {
                // Depends directly on ~1 entities: galley_id->publication_galleys.galley_id
                // Dependent entities: ~0
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('publication_galley_settings', 'galley_id', 'publication_galleys', 'galley_id');
            }

            section_settings: {
                // Depends directly on ~1 entities: section_id->sections.section_id
                // Dependent entities: ~0
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('section_settings', 'section_id', 'sections', 'section_id');
            }

            subscription_type_settings: {
                // Depends directly on ~1 entities: type_id->subscription_types.type_id
                // Dependent entities: ~0
                // Custom field (not found in at least one of the softwares)
                $this->deleteRequiredReference('subscription_type_settings', 'type_id', 'subscription_types', 'type_id');
            }
        } catch (\Exception $e) {
            if ($fallbackVersion = $this->setFallbackVersion()) {
                $this->_installer->log("A pre-flight check failed. The software was successfully upgraded to {$fallbackVersion} but could not be upgraded further (to " . $this->_installer->newVersion->getVersionString() . '). Check and correct the error, then try again.');
            }
            throw $e;
        }
    }
}
