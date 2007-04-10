<?php

/**
 * ThesisHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Handle requests for public thesis abstract functions. 
 *
 * $Id$
 */

import('core.Handler');

class ThesisHandler extends Handler {

	/**
	 * Display thesis index page.
	 */
	function index() {
		ThesisHandler::setupTemplate();

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');

		if ($thesisPlugin != null) {
			$thesesEnabled = $thesisPlugin->getEnabled();
		}

		if ($thesesEnabled) {
			$thesisDao = &DAORegistry::getDAO('ThesisDAO');
			$rangeInfo = &Handler::getRangeInfo('theses');
			$theses = &$thesisDao->getActiveThesesByJournalId($journalId, $rangeInfo);
			$thesisIntroduction = $thesisPlugin->getSetting($journalId, 'thesisIntroduction');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('theses', $theses);
			$templateMgr->assign('thesisIntroduction', $thesisIntroduction);
			$templateMgr->display($thesisPlugin->getTemplatePath() . 'index.tpl');
		} else {
			Request::redirect(null, 'index');
		}

	}
	
	/**
	 * Display form to submit a thesis.
	 */
	function submit() {
		ThesisHandler::setupTemplate();

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');

		if ($thesisPlugin != null) {
			$thesesEnabled = $thesisPlugin->getEnabled();
		}

		if ($thesesEnabled) {
			$thesisPlugin->import('StudentThesisForm');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'thesis'), 'plugins.generic.thesis.theses'));
			$thesisDao = &DAORegistry::getDAO('ThesisDAO');

			$thesisForm = &new StudentThesisForm();
			$thesisForm->initData();
			$thesisForm->display();

		} else {
				Request::redirect(null, 'index');
		}
	}

	/**
	 * Display thesis details.
	 * @param $args array optional, first parameter is the ID of the thesis to display 
	 */
	function view($args = array()) {
		ThesisHandler::setupTemplate();

		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');

		if ($thesisPlugin != null) {
			$thesesEnabled = $thesisPlugin->getEnabled();
		}

		$thesisId = !isset($args) || empty($args) ? null : (int) $args[0];
		$thesisDao = &DAORegistry::getDAO('ThesisDAO');

		if ($thesesEnabled) {
			if (($thesisId != null) && ($thesisDao->getThesisJournalId($thesisId) == $journalId) && $thesisDao->isThesisActive($thesisId)) {
			$thesis = &$thesisDao->getThesis($thesisId);

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('thesis', $thesis);
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'thesis'), 'plugins.generic.thesis.theses'));
			$templateMgr->display($thesisPlugin->getTemplatePath() . 'view.tpl');
			} else {
				Request::redirect(null, 'thesis');
			}
		} else {
				Request::redirect(null, 'index');
		}
	}

	/**
	 * Save submitted thesis.
	 */
	function save() {
		parent::validate();
		
		$journal = &Request::getJournal();
		$journalId = $journal->getJournalId();

		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');

		if ($thesisPlugin != null) {
			$thesesEnabled = $thesisPlugin->getEnabled();
		}

		if ($thesesEnabled) {

			$thesisDao = &DAORegistry::getDAO('ThesisDAO');
			$thesisPlugin->import('StudentThesisForm');

			$thesisForm = &new StudentThesisForm();
			$thesisForm->readInputData();
			
			if ($thesisForm->validate()) {
				$thesisForm->execute();

				Request::redirect(null, 'thesis');
				
			} else {
				ThesisHandler::setupTemplate();

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->append('pageHierarchy', array(Request::url(null, 'theses'), 'plugins.generic.thesis.theses'));
				$thesisForm->display();
			}
			
		} else {
				Request::redirect(null, 'index');
		}	
	}	

	/**
	 * Setup common template variables.
	 * @param $subclass boolean set to true if caller is below this handler in the hierarchy
	 */
	function setupTemplate($subclass = false) {
		parent::validate();

		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('pageHierachy', array(array(Request::url(null, 'theses'), 'plugins.generic.thesis.theses')));
	}
}

?>
