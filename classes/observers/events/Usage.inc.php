<?php

/**
 * @file classes/observers/events/Usage.inc.php
 *
 * Copyright (c) 2022 Simon Fraser University
 * Copyright (c) 2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Usage
 * @ingroup observers_events
 *
 * @brief Usage event.
 *
 */

namespace APP\observers\events;

use APP\core\Application;
use APP\issue\Issue;
use APP\issue\IssueGalley;
use APP\submission\Submission;
use PKP\context\Context;
use PKP\observers\traits\UsageEvent;
use PKP\submission\Representation;
use PKP\submissionFile\SubmissionFile;

class Usage
{
    use UsageEvent;

    public ?Issue $issue;
    public ?IssueGalley $issueGalley;

    public function __construct(int $assocType, Context $context, Submission $submission = null, Representation $galley = null, SubmissionFile $submissionFile = null, Issue $issue = null, IssueGalley $issueGalley = null)
    {
        $this->issue = $issue;
        $this->issueGalley = $issueGalley;
        $this->traitConstruct($assocType, $context, $submission, $galley, $submissionFile);
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
            return $this->getTraitCanonicalUrl();
        }
    }
}
