<?php

/**
 * @file api/v1/submissions/SubmissionHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionHandler
 * @ingroup api_v1_submission
 *
 * @brief Handle API requests for submission operations.
 *
 */

use APP\submission\Collector;

import('lib.pkp.api.v1.submissions.PKPSubmissionHandler');

class SubmissionHandler extends PKPSubmissionHandler
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
