<?php

/**
 * @file InformationHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.information
 * @class InformationHandler
 *
 * Display journal information.
 *
 * $Id$
 */

class InformationHandler extends Handler {

	/**
	 * Display the information page for the journal..
	 */
	function index($args) {
		parent::validate();
		InformationHandler::setupTemplate();
		$journal = Request::getJournal();

		if ($journal == null) {
			Request::redirect('index');
			return;
		}

		switch(isset($args[0])?$args[0]:null) {
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
				$content = $journal->getLocalizedSetting('competingInterestGuidelines');
				$pageTitle = $pageCrumbTitle = 'navigation.competingInterestGuidelines';
				break;
			case 'sampleCopyrightWording':
				$content = Locale::translate('manager.setup.authorCopyrightNotice.sample');
				$pageTitle = $pageCrumbTitle = 'manager.setup.copyrightNotice';
				break;
			default:
				Request::redirect($journal->getPath());
				return;
		}

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageCrumbTitle', $pageCrumbTitle);
		$templateMgr->assign('pageTitle', $pageTitle);
		$templateMgr->assign('content', $content);
		$templateMgr->display('information/information.tpl');
	}

	function readers() {
		InformationHandler::index(array('readers'));
	}

	function authors() {
		InformationHandler::index(array('authors'));
	}

	function librarians() {
		InformationHandler::index(array('librarians'));
	}

	function competingInterestGuidelines() {
		InformationHandler::index(array('competingInterestGuidelines'));
	}

	function sampleCopyrightWording() {
		InformationHandler::index(array('sampleCopyrightWording'));
	}

	/**
	 * Initialize the template.
	 */
	function setupTemplate() {
		$journal =& Request::getJournal();
		$templateMgr =& TemplateManager::getManager();
		if (!$journal || !$journal->getSetting('restrictSiteAccess')) {
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		}
	}
}

?>
