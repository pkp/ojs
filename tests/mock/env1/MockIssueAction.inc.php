<?php

/**
 * @file tests/mock/env1/MockIssueAction.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueAction
 * @brief a mock issue action.
 */

class IssueAction {

	/**
	 * @copydoc IssueAction::subscriptionRequired()
	 */
	function subscriptionRequired($issue, $journal) {
		return false;
	}

	/**
	 * @copydoc IssueAction::subscribedUser()
	 */
	function subscribedUser($user, $journal, $issueId = null, $articleId = null) {
		return false;
	}

	/**
	 * @copydoc IssueAction::subscribedDomain()
	 */
	function subscribedDomain($request, $journal, $issueId = null, $articleId = null) {
		return false;
	}
}

