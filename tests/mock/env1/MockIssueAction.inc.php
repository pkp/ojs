<?php

/**
 * @file tests/mock/env1/MockIssueAction.inc.php
 *
 * Copyright (c) 2013-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueAction
 * @brief a mock issue action.
 */

class IssueAction {

	/**
	 * @see IssueAction::subscriptionRequired()
	 */
	function subscriptionRequired(&$issue) {
		return false;
	}

	/**
	 * @see IssueAction::subscribedUser()
	 */
	function subscribedUser(&$journal, $issueId = null, $articleId = null) {
		return false;
	}

	/**
	 * @see IssueAction::subscribedDomain()
	 */
	function subscribedDomain(&$journal, $issueId = null, $articleId = null) {
		return false;
	}
}
?>
