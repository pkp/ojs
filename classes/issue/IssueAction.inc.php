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
}

?>
