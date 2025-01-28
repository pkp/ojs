<?php

/**
 * @file plugins/importexport/csv/classes/processors/IssueProcessor.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the issue data into the database.
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Processors;

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedDaos;
use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedEntities;

class IssueProcessor
{
    /**
	 * Processes data for the Issue. If there's no issue registered, a new one will be created and attached
	 * to the submission.
	 *
	 * @param int $journalId
	 * @param object $data
	 *
	 * @return \Issue
	 */
	public static function process($journalId, $data)
    {
        $issue = CachedEntities::getCachedIssue($data, $journalId);

        if(is_null($issue)) {
            $issueDao = CachedDaos::getIssueDao();
            $sanitizedIssueDescription = \PKPString::stripUnsafeHtml($data->issueDescription);

			/** @var \Issue $issue */
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
            $issue->setDatePublished(\Core::getCurrentDate());
            $issue->setTitle($data->issueTitle, $data->locale);
            $issue->setDescription($sanitizedIssueDescription, $data->locale);
            $issue->stampModified();

            // Assume open access, no price.
            $issue->setAccessStatus(ISSUE_ACCESS_OPEN);
            $issueDao->insertObject($issue);
        }

        return $issue;
	}
}
