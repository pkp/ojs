<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I7527_IdentityMetadata.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7527_IdentityMetadata
 *
 * @brief Stamp the journal identity metadata (ISSN, Title) to Publication settings
 */

namespace APP\migration\upgrade\v3_5_0;

use APP\core\Application;
use APP\facades\Repo;
use Illuminate\Support\Facades\DB;
use PKP\install\DowngradeNotSupportedException;
use PKP\migration\Migration;

class I7527_IdentityMetadata extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        $contextDao = Application::getContextDAO();

        $submissions = Repo::submission()->getCollector()->filterByContextIds(DB::table('journals')->pluck('journal_id')->toArray())->getMany();

        foreach ($submissions as $submission) {

            $context = $contextDao->getById($submission->getData('contextId'));
            $publications = $submission->getData('publications');

            foreach ($publications as $publication) {

                $locale = $submission->getData('locale');

                // For journal name try to use the name based on the submission locale, fallback to context primary locale
                if ($context->getName($submission->getData('locale'))) {
                    $publication->setData('contextName', $context->getName($submission->getData('locale')), $locale);
                } else {
                    $publication->setData('contextName', $context->getName($submission->getPrimaryLocale()), $locale);
                }

                // Stamp the ISSNs if available
                if ($context->getData('printIssn')) {
                    $publication->setData('printIssn', $context->getData('printIssn'));
                }
                if ($context->getData('onlineIssn')) {
                    $publication->setData('onlineIssn', $context->getData('onlineIssn'));
                }

                Repo::publication()->edit($publication, []);

            }
        }

        $issues = Repo::issue()->getCollector()->filterByContextIds(DB::table('journals')->pluck('journal_id')->toArray())->getMany();

        foreach ($issues as $issue) {

            $context = $contextDao->getById($issue->getData('journalId'));

            // Stamp all existing context names to issue metadata
            $contextNames = $context->getData('name');
            foreach ($contextNames as $locale => $contextName) {
                $issue->setData('contextName', $context->getName($locale), $locale);
            }

            // Stamp the ISSNs if available
            if ($context->getData('printIssn')) {
                $issue->setData('printIssn', $context->getData('printIssn'));
            }
            if ($context->getData('onlineIssn')) {
                $issue->setData('onlineIssn', $context->getData('onlineIssn'));
            }

            Repo::issue()->edit($issue, []);

        }


    }

    /**
     * Reverse the downgrades
     *
     * @throws DowngradeNotSupportedException
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
