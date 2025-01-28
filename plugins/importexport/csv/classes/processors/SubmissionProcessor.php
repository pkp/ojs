<?php

/**
 * @file plugins/importexport/csv/classes/processors/SubmissionProcessor.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the submission data into the database.
 */

namespace APP\plugins\importexport\csv\classes\processors;

use APP\facades\Repo;
use APP\submission\Submission;

class SubmissionProcessor
{
    /**
     * Processes initial data for the Submission
     */
    public static function process(int $journalId, object $data): Submission
    {
        $submissionDao = Repo::submission()->dao;

        $submission = $submissionDao->newDataObject();
        $submission->setData('contextId', $journalId);
        $submission->stampLastActivity();
        $submission->stampModified();
        $submission->setData('status', Submission::STATUS_PUBLISHED);
        $submission->setData('locale', $data->locale);
        $submission->setData('stageId', WORKFLOW_STAGE_ID_PRODUCTION);
        $submission->setData('submissionProgress', '0');
        $submission->setData('abstract', $data->articleAbstract, $data->locale);
        $submissionDao->insert($submission);

        return $submission;
    }

    /**
     * Updates the current publication ID for the submission
     */
    public static function updateCurrentPublicationId(Submission $submission, int $publicationId): void
    {
        $submission->setData('currentPublicationId', $publicationId);
        Repo::submission()->dao->update($submission);
    }
}
