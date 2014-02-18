<?php

/**
 * @file plugins/generic/usageStats/UsageStatsHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsHandler
 * @ingroup plugins_generic_usageStats
 *
 * @brief Handle requests for the usage stats plugin.
 */

import('classes.handler.Handler');

class UsageStatsHandler extends Handler {
	/**
	 * Constructor
	 **/
	function UsageStatsHandler() {
		parent::Handler();
	}

	/**
	 * Redirect to the current issue view page, using
	 * the view operation instead of current.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function current($args, &$request) {
		$journal = $request->getJournal();
		$queryArray = $request->getQueryArray();

		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issue = $issueDao->getCurrent($journal->getId(), true);
		$request->redirect(null, 'issue', 'view', $issue->getId(), $queryArray);
	}
}

?>
