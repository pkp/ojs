<?php

/**
 * @file classes/observers/events/Usage.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
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
        $this->constructUsageEvent($assocType, $context, $submission, $galley, $submissionFile);

        if (in_array($assocType, [Application::ASSOC_TYPE_ISSUE, Application::ASSOC_TYPE_ISSUE_GALLEY])) {
            $canonicalUrlPage = $canonicalUrlOp = null;
            $canonicalUrlParams = [];
            switch ($assocType) {
                case Application::ASSOC_TYPE_ISSUE_GALLEY:
                    $canonicalUrlOp = 'download';
                    $canonicalUrlParams = [$issue->getId(), $issueGalley->getId()];
                    break;
                case Application::ASSOC_TYPE_ISSUE:
                    $canonicalUrlOp = 'view';
                    $canonicalUrlParams = [$issue->getId()];
                    break;
            }
            $canonicalUrl = $this->getCanonicalUrl($this->request, $canonicalUrlPage, $canonicalUrlOp, $canonicalUrlParams);
            $this->canonicalUrl = $canonicalUrl;
        }
        $this->issue = $issue;
        $this->issueGalley = $issueGalley;
    }
}
