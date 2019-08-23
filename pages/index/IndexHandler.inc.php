<?php

/**
 * @file pages/index/IndexHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IndexHandler
 * @ingroup pages_index
 *
 * @brief Handle site index requests.
 */

import('lib.pkp.pages.index.PKPIndexHandler');

class IndexHandler extends PKPIndexHandler {
	//
	// Public handler operations
	//
	/**
	 * If no journal is selected, display list of journals.
	 * Otherwise, display the index page for the selected journal.
	 * @param $args array
	 * @param $request Request
	 */
	function index($args, $request) {
		$this->validate(null, $request);
		$journal = $request->getJournal();

		if (!$journal) {
			$journal = $this->getTargetContext($request, $journalsCount);
			if ($journal) {
				// There's a target context but no journal in the current request. Redirect.
				$request->redirect($journal->getPath());
			}
			if ($journalsCount === 0 && Validation::isSiteAdmin()) {
				// No contexts created, and this is the admin.
				$request->redirect(null, 'admin', 'contexts');
			}
		}

		$this->setupTemplate($request);
		$router = $request->getRouter();
		$templateMgr = TemplateManager::getManager($request);
		if ($journal) {
			// Assign header and content for home page
			$templateMgr->assign(array(
				'additionalHomeContent' => $journal->getLocalizedData('additionalHomeContent'),
				'homepageImage' => $journal->getLocalizedData('homepageImage'),
				'homepageImageAltText' => $journal->getLocalizedData('homepageImageAltText'),
				'journalDescription' => $journal->getLocalizedData('description'),
			));

			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getCurrent($journal->getId(), true);
			if (isset($issue) && $journal->getData('publishingMode') != PUBLISHING_MODE_NONE) {
				import('pages.issue.IssueHandler');
				// The current issue TOC/cover page should be displayed below the custom home page.
				IssueHandler::_setupIssueTemplate($request, $issue);
			}

			$this->_setupAnnouncements($journal, $templateMgr);

			$templateMgr->display('frontend/pages/indexJournal.tpl');
		} else {
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$site = $request->getSite();

			if ($site->getRedirect() && ($journal = $journalDao->getById($site->getRedirect())) != null) {
				$request->redirect($journal->getPath());
			}

			$templateMgr->assign(array(
				'pageTitleTranslated' => $site->getLocalizedTitle(),
				'about' => $site->getLocalizedAbout(),
				'journalFilesPath' => $request->getBaseUrl() . '/' . Config::getVar('files', 'public_files_dir') . '/journals/',
				'journals' => $journalDao->getAll(true),
				'site' => $site,
			));
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
			$templateMgr->display('frontend/pages/indexSite.tpl');
		}
	}
}


