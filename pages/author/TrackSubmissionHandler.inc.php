<?php

/**
 * TrackSubmissionHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.author
 *
 * Handle requests for submission tracking. 
 *
 * $Id$
 */

class TrackSubmissionHandler extends AuthorHandler {
	
	/**
	 * Display list of an author's submissions.
	 */
	function track() {
		parent::validate();
		parent::setupTemplate(true);
		
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('submissions', $articleDao->getArticlesByUserId($user->getUserId(), $journal->getJournalId()));
		$templateMgr->display('author/submissions.tpl');
	}
	
}
?>
