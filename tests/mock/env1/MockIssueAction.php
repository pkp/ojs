<?php

/**
 * @file tests/mock/env1/MockIssueAction.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueAction
 * @brief a mock issue action.
 */

class IssueAction
{
    /**
     * @copydoc IssueAction::subscriptionRequired()
     */
    public function subscriptionRequired($issue, $journal)
    {
        return false;
    }

    /**
     * @copydoc IssueAction::subscribedUser()
     *
     * @param null|mixed $issueId
     * @param null|mixed $articleId
     */
    public function subscribedUser($user, $journal, $issueId = null, $articleId = null)
    {
        return false;
    }

    /**
     * @copydoc IssueAction::subscribedDomain()
     *
     * @param null|mixed $issueId
     * @param null|mixed $articleId
     */
    public function subscribedDomain($request, $journal, $issueId = null, $articleId = null)
    {
        return false;
    }
}
