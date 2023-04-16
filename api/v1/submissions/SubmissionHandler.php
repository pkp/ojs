<?php

/**
 * @file api/v1/submissions/SubmissionHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionHandler
 *
 * @ingroup api_v1_submission
 *
 * @brief Handle API requests for submission operations.
 *
 */

namespace APP\API\v1\submissions;

use APP\submission\Collector;

class SubmissionHandler extends \PKP\API\v1\submissions\PKPSubmissionHandler
{
    /** @copydoc PKPSubmissionHandler::getSubmissionCollector() */
    protected function getSubmissionCollector(array $queryParams): Collector
    {
        $collector = parent::getSubmissionCollector($queryParams);

        if (isset($queryParams['issueIds'])) {
            $collector->filterByIssueIds(
                array_map('intval', $this->paramToArray($queryParams['issueIds']))
            );
        }

        if (isset($queryParams['sectionIds'])) {
            $collector->filterBySectionIds(
                array_map('intval', $this->paramToArray($queryParams['sectionIds']))
            );
        }

        return $collector;
    }
}
