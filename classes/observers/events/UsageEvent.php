<?php

/**
 * @file classes/observers/events/UsageEvent.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Usage
 *
 * @ingroup observers_events
 *
 * @brief Adds issue tracking to the usage event data.
 *
 */

namespace APP\observers\events;

use APP\core\Application;
use APP\issue\Issue;
use APP\issue\IssueGalley;
use APP\submission\Submission;
use Exception;
use PKP\context\Context;
use PKP\submission\Representation;
use PKP\submissionFile\SubmissionFile;

class UsageEvent extends \PKP\observers\events\UsageEvent
{
    public ?Issue $issue;
    public ?IssueGalley $issueGalley;

    public function __construct(
        int $assocType,
        Context $context,
        Submission $submission = null,
        Representation $galley = null,
        SubmissionFile $submissionFile = null,
        Issue $issue = null,
        IssueGalley $issueGalley = null
    ) {
        $this->issue = $issue;
        $this->issueGalley = $issueGalley;
        parent::__construct($assocType, $context, $submission, $galley, $submissionFile);
    }

    /**
     * Get the canonical URL for the usage object
     *
     * @throws Exception
     */
    protected function getCanonicalUrl(): string
    {
        if (in_array($this->assocType, [Application::ASSOC_TYPE_ISSUE, Application::ASSOC_TYPE_ISSUE_GALLEY])) {
            $canonicalUrlPage = $canonicalUrlOp = null;
            $canonicalUrlParams = [];
            switch ($this->assocType) {
                case Application::ASSOC_TYPE_ISSUE_GALLEY:
                    $canonicalUrlOp = 'download';
                    $canonicalUrlParams = [$this->issue->getId(), $this->issueGalley->getId()];
                    break;
                case Application::ASSOC_TYPE_ISSUE:
                    $canonicalUrlOp = 'view';
                    $canonicalUrlParams = [$this->issue->getId()];
                    break;
            }
            $canonicalUrl = $this->getRouterCanonicalUrl($this->request, $canonicalUrlPage, $canonicalUrlOp, $canonicalUrlParams);
            return $canonicalUrl;
        } else {
            return parent::getCanonicalUrl();
        }
    }
}
