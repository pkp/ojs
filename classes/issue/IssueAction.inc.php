<?php

/**
 * IssueAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package issue
 *
 * IssueAction class.
 *
 * $Id$
 */

class IssueAction {

	/**
	 * Constructor.
	 */
	function IssueAction() {
	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * Smarty usage: {print_issue_id articleId="$articleId"}
	 *
	 * Custom Smarty function for printing the issue id
	 * @return string
	 */
	function smartyPrintIssueId($params, &$smarty) {
		if (isset($params) && !empty($params)) {
			if (isset($params['articleId'])) {
				$issueDao = &DAORegistry::getDAO('IssueDAO');
				$issue = &$issueDao->getIssueByArticleId($params['articleId']);
				if ($issue != null) {
					$vol = Locale::Translate('issue.vol');
					$no = Locale::Translate('issue.no');
					return "$vol " . $issue->getVolume() . ", $no " . $issue->getNumber() . ' (' . $issue->getYear() . ')';
				}
			}
		}
	}

	/**
	 * Checks if subscription is required for viewing the issue
	 * @param $issue
	 * @return bool
	 */
	function subscriptionRequired($issue) {
		$journal = &Request::getJournal();
		return ($journal->getSetting('enableSubscriptions') && ($issue->getAccessStatus() == SUBSCRIPTION && strtotime($issue->getOpenAccessDate()) > time()));
	}

	/**
	 * Checks if user has subscription
	 * @param $domain
	 * @param $ip
	 * @return bool
	 */
	function subscribedUser() {
		$user = &Request::getUser();
		$journal = &Request::getJournal();
		$subscriptionDao = &DAORegistry::getDAO('SubscriptionDAO');
		return $subscriptionDao->isValidSubscription(null, Request::getRemoteAddr(), $user->getUserId(), $journal->getJournalId());
	}

}

?>
