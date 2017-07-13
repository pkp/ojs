<?php

/**
 * @file classes/services/IssueService.php
*
* Copyright (c) 2014-2017 Simon Fraser University
* Copyright (c) 2000-2017 John Willinsky
* Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
*
* @class IssueService
* @ingroup services
*
* @brief Helper class that encapsulates issue business logic
*/

namespace PKP\Services;

class IssueService {

	/**
	 * Determine if a user can access galleys for a specific issue 
	 * 
	 * @param \Journal $journal
	 * @param \Issue $issue
	 * 
	 * @return boolean
	 */
	public function userHasAccessToGalleys(\Journal $journal, \Issue $issue) {
		import('classes.issue.IssueAction');
		$issueAction = new \IssueAction();

		$subscriptionRequired = $issueAction->subscriptionRequired($issue);
		$subscribedUser = $issueAction->subscribedUser($journal);
		$subscribedDomain = $issueAction->subscribedDomain($journal);

		return !$subscriptionRequired || $issue->getAccessStatus() == ISSUE_ACCESS_OPEN || $subscribedUser || $subscribedDomain;
	}
}