<?php

/**
 * @file pages/information/InformationHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
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
	 * @see PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$context = $request->getContext();
		if (!$context || !$context->getSetting('restrictSiteAccess')) {
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		}

		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Display the information page for the journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$this->setupTemplate($request);
		$this->validate(null, $request);
		$journal = $request->getJournal();

		switch(array_shift($args)) {
			case 'readers':
				$content = $journal->getLocalizedData('readerInformation');
				$pageTitle = 'navigation.infoForReaders.long';
				break;
			case 'authors':
				$content = $journal->getLocalizedData('authorInformation');
				$pageTitle = 'navigation.infoForAuthors.long';
				break;
			case 'librarians':
				$content = $journal->getLocalizedData('librarianInformation');
				$pageTitle = 'navigation.infoForLibrarians.long';
				break;
			case 'competingInterestGuidelines':
				$content = $journal->getLocalizedData('competingInterestsPolicy');
				$pageTitle = 'navigation.competingInterestGuidelines';
				break;
			case 'sampleCopyrightWording':
				AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);
				$content = __('manager.setup.copyrightNotice.sample');
				$pageTitle = 'manager.setup.copyrightNotice';
				break;
			default:
				return $request->redirect($journal->getPath());
		}

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pageTitle', $pageTitle);
		$templateMgr->assign('content', $content);
		$templateMgr->display('frontend/pages/information.tpl');
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
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);
		if (!$request->getJournal()->getData('restrictSiteAccess')) {
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		}
	}
}


