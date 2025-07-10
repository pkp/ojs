<?php

/**
 * @file classes/search/SubmissionSearchResult.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief A submission search result.
 */

namespace APP\search;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\IssueAction;

class SubmissionSearchResult extends \PKP\search\SubmissionSearchResult
{
    /**
     * @see Illuminate\Database\Eloquent\HasCollection.
     */
    public function newCollection(array $models = [])
    {
        $collection = parent::newCollection($models);

        $issueAction = new IssueAction();
        $issueCache = [];
        $issueAvailabilityCache = [];

        $request = Application::get()->getRequest();
        $user = $request->getUser();

        foreach ($collection as $result) {
            $submissionId = $result['submission']->getId();
            $issueId = $result['currentPublication']->getData('issueId');
            $issue = $issueId ? $issueCache[$issueId] ?? ($issueCache[$issueId] = Repo::issue()->get($issueId)) : null;

            if ($issue) {
                $issueAvailabilityCache[$issueId] ??= !$issueAction->subscriptionRequired($issue, $result['context'])
                    || $issueAction->subscribedUser($user, $result['context'], $issueId, $submissionId)
                    || $issueAction->subscribedDomain($request, $result['context'], $issueId, $submissionId);
            }
            yield [
                ...$result,
                'issue' => $issue,
                'issueAvailable' => $issue ? $issueAvailabilityCache[$issueId] : true,
            ];
        }
    }
}
