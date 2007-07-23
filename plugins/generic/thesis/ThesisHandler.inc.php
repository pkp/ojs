<?php

/**
 * @file ThesisHandler.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 * @class ThesisHandler
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

		if ($journal != null) {
			$journalId = $journal->getJournalId();
		} else {
			Request::redirect(null, 'index');
		}

		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');

		if ($thesisPlugin != null) {
			$thesesEnabled = $thesisPlugin->getEnabled();
		}

		if ($thesesEnabled) {
			$searchField = null;
			$searchMatch = null;
			$search = Request::getUserVar('search');

			if (isset($search)) {
				$searchField = Request::getUserVar('searchField');
				$searchMatch = Request::getUserVar('searchMatch');
			}			

			$thesisDao = &DAORegistry::getDAO('ThesisDAO');
			$rangeInfo = &Handler::getRangeInfo('theses');
			$resultOrder = $thesisPlugin->getSetting($journalId, 'thesisOrder');
			
			$theses = &$thesisDao->getActiveThesesByJournalId($journalId, $searchField, $search, $searchMatch, null, null, $resultOrder, $rangeInfo);
			$thesisIntroduction = $thesisPlugin->getSetting($journalId, 'thesisIntroduction');

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->assign('theses', $theses);
			$templateMgr->assign('thesisIntroduction', $thesisIntroduction);
			$templateMgr->assign('searchField', $searchField);
			$templateMgr->assign('searchMatch', $searchMatch);
			$templateMgr->assign('search', $search);

			$fieldOptions = Array(
				THESIS_FIELD_FIRSTNAME => 'plugins.generic.thesis.studentFirstName',
				THESIS_FIELD_LASTNAME => 'plugins.generic.thesis.studentLastName',
				THESIS_FIELD_DEPARTMENT => 'plugins.generic.thesis.department',
				THESIS_FIELD_UNIVERSITY => 'plugins.generic.thesis.university',
				THESIS_FIELD_TITLE => 'plugins.generic.thesis.title',
				THESIS_FIELD_ABSTRACT => 'plugins.generic.thesis.abstract',
				THESIS_FIELD_SUBJECT => 'plugins.generic.thesis.keyword'
			);
			$templateMgr->assign('fieldOptions', $fieldOptions);

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

		if ($journal != null) {
			$journalId = $journal->getJournalId();
		} else {
			Request::redirect(null, 'index');
		}

		$thesisPlugin = &PluginRegistry::getPlugin('generic', 'ThesisPlugin');

		if ($thesisPlugin != null) {
			$thesesEnabled = $thesisPlugin->getEnabled();
		}

		if ($thesesEnabled) {
			$thesisPlugin->import('StudentThesisForm');
			$enableUploadCode = $thesisPlugin->getSetting($journalId, 'enableUploadCode');
			$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
			$journalSettings = &$journalSettingsDao->getJournalSettings($journalId);

			$templateMgr = &TemplateManager::getManager();
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'thesis'), 'plugins.generic.thesis.theses'));
			$templateMgr->assign('journalSettings', $journalSettings);
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

		if ($journal != null) {
			$journalId = $journal->getJournalId();
		} else {
			Request::redirect(null, 'index');
		}

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
			$templateMgr->assign('journal', $journal);
			$templateMgr->assign('thesis', $thesis);
			$templateMgr->append('pageHierarchy', array(Request::url(null, 'thesis'), 'plugins.generic.thesis.theses'));
			$thesisMetaCustomHeaders = $templateMgr->fetch($thesisPlugin->getTemplatePath() . 'metadata.tpl');
			$metaCustomHeaders = $templateMgr->get_template_vars('metaCustomHeaders');
			$templateMgr->assign('metaCustomHeaders', $metaCustomHeaders . "\n" . $thesisMetaCustomHeaders);
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

		if ($journal != null) {
			$journalId = $journal->getJournalId();
		} else {
			Request::redirect(null, 'index');
		}

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

				$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
				$journalSettings = &$journalSettingsDao->getJournalSettings($journalId);

				$templateMgr = &TemplateManager::getManager();
				$templateMgr->assign('journalSettings', $journalSettings);
				$thesisForm->display();
			}
			
		} else {
				Request::redirect(null, 'index');
		}	
	}	

	/**
	 * Captcha support.
	 */
	function viewCaptcha($args) {
		$captchaId = (int) array_shift($args);
		import('captcha.CaptchaManager');
		$captchaManager =& new CaptchaManager();
		if ($captchaManager->isEnabled()) {
			$captchaDao =& DAORegistry::getDAO('CaptchaDAO');
			$captcha =& $captchaDao->getCaptcha($captchaId);
			if ($captcha) {
				$captchaManager->generateImage($captcha);
				exit();
			}
		}
		Request::redirect(null, 'thesis');
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
