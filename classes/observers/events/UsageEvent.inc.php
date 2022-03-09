<?php

/**
 * @file classes/observers/events/UsageEvent.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UsageEvent
 * @ingroup observers_events
 *
 * @brief Usage event.
 *
 */

namespace APP\observers\events;

use APP\core\Application;
use PKP\observers\events\PKPUsageEvent;

class UsageEvent extends PKPUsageEvent
{
    /** Issue ID */
    public ?int $issueId;

    public function __construct(int $assocType, int $assocId, int $contextId, int $submissionId = null, int $representationId = null, string $mimetype = null, int $issueId = null)
    {
        parent::__construct($assocType, $assocId, $contextId, $submissionId, $representationId, $mimetype);

        if (in_array($assocType, [Application::ASSOC_TYPE_ISSUE, Application::ASSOC_TYPE_ISSUE_GALLEY])) {
            $application = Application::get();
            $request = $application->getRequest();
            $canonicalUrlPage = $canonicalUrlOp = $canonicalUrlParams = null;
            switch ($assocType) {
                case Application::ASSOC_TYPE_ISSUE_GALLEY:
                    $canonicalUrlOp = 'download';
                    $canonicalUrlParams = [$issueId, $assocId];
                    break;
                case Application::ASSOC_TYPE_ISSUE:
                    $canonicalUrlOp = 'view';
                    $canonicalUrlParams = [$assocId];
                    break;
            }
            $canonicalUrl = $this->getCanonicalUrl($request, $canonicalUrlPage, $canonicalUrlOp, $canonicalUrlParams);
            $this->canonicalUrl = $canonicalUrl;
        }
        $this->issueId = $issueId;
    }
}
