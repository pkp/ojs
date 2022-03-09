<?php

/**
 * @file classes/services/StatsPublicationService.inc.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsPublicationService
 * @ingroup services
 *
 * @brief Helper class that encapsulates publication statistics business logic
 */

namespace APP\services;

use APP\core\Application;
use APP\facades\Repo;
use APP\submission\Submission;

class StatsPublicationService extends \PKP\services\PKPStatsPublicationService
{
    /**
     * A helper method to get the submissionIds param when a issueIds
     * param is also passed.
     *
     * If the issueIds and submissionIds params were both passed in the
     * request, then we only return ids that match both conditions.
     *
     * @param array $issueIds issue Ids
     * @param ?array $submissionIds List of allowed submission IDs
     *
     * @return array submission IDs
     */
    public function getSubmissionIdsByIssue(array $issueIds, ?array $submissionIds): array
    {
        $issueIdsSubmissionIds = Repo::submission()->getIds(
            Repo::submission()
                ->getCollector()
                ->filterByContextIds([Application::get()->getRequest()->getContext()->getId()])
                ->filterByStatus([Submission::STATUS_PUBLISHED])
                ->filterByIssueIds($issueIds)
        )->toArray();

        if ($submissionIds !== null && !empty($submissionIds)) {
            $submissionIds = array_intersect($submissionIds, $issueIdsSubmissionIds);
        } else {
            $submissionIds = $issueIdsSubmissionIds;
        }
        return $submissionIds;
    }
}
