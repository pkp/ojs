<?php

/**
 * @file pages/information/InformationHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InformationHandler
 * @ingroup pages_information
 *
 * @brief Display journal information.
 */

import('classes.handler.Handler');

class InformationHandler extends Handler {
	/**
	 * Constructor
	 **/
	function InformationHandler() {
		parent::Handler();
	}

	/**
	 * Display the information page for the journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$journal = $request->getJournal();
		if (!$journal) $request->redirect('index');

		$this->validate();
		$this->setupTemplate($request, $journal);

		switch(array_shift($args)) {
			case 'readers':
				$content = $journal->getLocalizedSetting('readerInformation');
				$pageTitle = 'navigation.infoForReaders.long';
				$pageCrumbTitle = 'navigation.infoForReaders';
				break;
			case 'authors':
				$content = $journal->getLocalizedSetting('authorInformation');
				$pageTitle = 'navigation.infoForAuthors.long';
				$pageCrumbTitle = 'navigation.infoForAuthors';
				break;
			case 'librarians':
				$content = $journal->getLocalizedSetting('librarianInformation');
				$pageTitle = 'navigation.infoForLibrarians.long';
				$pageCrumbTitle = 'navigation.infoForLibrarians';
				break;
			case 'competingInterestGuidelines':
				$content = $journal->getLocalizedSetting('competingInterestsPolicy');
				$pageTitle = $pageCrumbTitle = 'navigation.competingInterestGuidelines';
				break;
			case 'sampleCopyrightWording':
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);
				$content = __('manager.setup.authorCopyrightNotice.sample');
				$pageTitle = $pageCrumbTitle = 'manager.setup.copyrightNotice';
				break;
			default:
				$request->redirect($journal->getPath());
		}

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pageCrumbTitle', $pageCrumbTitle);
		$templateMgr->assign('pageTitle', $pageTitle);
		$templateMgr->assign('content', $content);
		$templateMgr->display('information/information.tpl');
	}

	function readers($args, $request) {
		$this->index(array('readers'), $request);
	}

	function authors($args, $request) {
		$this->index(array('authors'), $request);
	}

	function librarians($args, $request) {
		$this->index(array('librarians'), $request);
	}

	function competingInterestGuidelines($args, $request) {
		$this->index(array('competingInterestGuidelines'), $request);
	}

	function sampleCopyrightWording($args, $request) {
		$this->index(array('sampleCopyrightWording'), $request);
	}

	/**
	 * Initialize the template.
	 * @param $request PKPRequest
	 * @param $journal Journal
	 */
	function setupTemplate($request, $journal) {
		parent::setupTemplate($request);
		if (!$journal->getSetting('restrictSiteAccess')) {
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		}
	}
}

?>
