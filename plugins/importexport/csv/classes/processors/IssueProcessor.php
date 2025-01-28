<?php

/**
 * @file plugins/importexport/csv/classes/processors/IssueProcessor.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the issue data into the database.
 */

namespace APP\plugins\importexport\csv\classes\processors;

use APP\facades\Repo;
use APP\issue\Issue;
use APP\plugins\importexport\csv\classes\cachedAttributes\CachedEntities;
use PKP\core\Core;
use PKP\core\PKPString;

class IssueProcessor
{
    /**
     * Processes data for the Issue. If there's no issue registered, a new one will be created and attached to the submission.
     */
    public static function process(int $journalId, object $data): Issue
    {
        $issue = CachedEntities::getCachedIssue($data, $journalId);

        if (is_null($issue)) {
            $issueDao = Repo::issue()->dao;
            $sanitizedIssueDescription = PKPString::stripUnsafeHtml($data->issueDescription);

            $issue = $issueDao->newDataObject();
            $issue->setJournalId($journalId);
            $issue->setVolume($data->issueVolume);
            $issue->setNumber($data->issueNumber);
            $issue->setYear($data->issueYear);
            $issue->setShowVolume($data->issueVolume);
            $issue->setShowNumber($data->issueNumber);
            $issue->setShowYear($data->issueYear);
            $issue->setShowTitle(1);
            $issue->setPublished(true);
            $issue->setDatePublished(Core::getCurrentDate());
            $issue->setTitle($data->issueTitle, $data->locale);
            $issue->setDescription($sanitizedIssueDescription, $data->locale);
            $issue->stampModified();

            // Assume open access, no price.
            $issue->setAccessStatus(Issue::ISSUE_ACCESS_OPEN);
            $issueDao->insert($issue);
        }

        return $issue;
    }
}
