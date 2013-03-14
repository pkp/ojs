<?php

/**
 * @file plugins/generic/thesis/ThesisHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ThesisHandler
 * @ingroup plugins_generic_thesis
 *
 * @brief Handle requests for public thesis abstract functions.
 */

import('classes.handler.Handler');

class ThesisHandler extends Handler {
	/**
	 * Constructor
	 */
	function ThesisHandler() {
		parent::Handler();
	}

	/**
	 * Display thesis index page.
	 */
	function index($args, $request) {
		$this->validate();
		$this->setupTemplate();
		$journal =& $request->getJournal();

		if ($journal != null) {
			$journalId = $journal->getId();
		} else {
			$request->redirect(null, 'index');
		}

		$thesisPlugin =& PluginRegistry::getPlugin('generic', THESIS_PLUGIN_NAME);

		if ($thesisPlugin != null) {
			$thesesEnabled = $thesisPlugin->getEnabled();
		}

		if ($thesesEnabled) {
			$searchField = null;
			$searchMatch = null;
			$search = $request->getUserVar('search');

			if (!empty($search)) {
				$searchField = $request->getUserVar('searchField');
				$searchMatch = $request->getUserVar('searchMatch');
			}

			$thesisDao = DAORegistry::getDAO('ThesisDAO');
			$rangeInfo = $this->getRangeInfo($request, 'theses');
			$resultOrder = $thesisPlugin->getSetting($journalId, 'thesisOrder');

			$theses =& $thesisDao->getActiveThesesByJournalId($journalId, $searchField, $search, $searchMatch, null, null, $resultOrder, $rangeInfo);
			$thesisIntroduction = $thesisPlugin->getSetting($journalId, 'thesisIntroduction');

			$templateMgr =& TemplateManager::getManager($request);
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
			$request->redirect(null, 'index');
		}
	}

	/**
	 * Display form to submit a thesis.
	 */
	function submit($args, $request) {
		$this->validate();
		$this->setupTemplate();
		$journal =& $request->getJournal();

		if ($journal != null) {
			$journalId = $journal->getId();
		} else {
			$request->redirect(null, 'index');
		}

		$thesisPlugin =& PluginRegistry::getPlugin('generic', THESIS_PLUGIN_NAME);

		if ($thesisPlugin != null) {
			$thesesEnabled = $thesisPlugin->getEnabled();
		}

		if ($thesesEnabled) {
			$thesisPlugin->import('StudentThesisForm');
			$enableUploadCode = $thesisPlugin->getSetting($journalId, 'enableUploadCode');
			$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
			$journalSettings =& $journalSettingsDao->getSettings($journalId);

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign('journalSettings', $journalSettings);
			$thesisDao = DAORegistry::getDAO('ThesisDAO');

			$thesisForm = new StudentThesisForm(THESIS_PLUGIN_NAME);
			$thesisForm->initData();
			$thesisForm->display();

		} else {
			$request->redirect(null, 'index');
		}
	}

	/**
	 * Display thesis details.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function view($args, $request) {
		$this->validate();
		$this->setupTemplate();
		$journal =& $request->getJournal();

		if ($journal != null) {
			$journalId = $journal->getId();
		} else {
			$request->redirect(null, 'index');
		}

		$thesisPlugin =& PluginRegistry::getPlugin('generic', THESIS_PLUGIN_NAME);

		if ($thesisPlugin != null) {
			$thesesEnabled = $thesisPlugin->getEnabled();
		}

		$thesisId = !isset($args) || empty($args) ? null : (int) $args[0];
		$thesisDao = DAORegistry::getDAO('ThesisDAO');

		if ($thesesEnabled) {
			if (($thesisId != null) && ($thesisDao->getThesisJournalId($thesisId) == $journalId) && $thesisDao->isThesisActive($thesisId)) {
			$thesis =& $thesisDao->getThesis($thesisId);

			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign('journal', $journal);
			$templateMgr->assign('thesis', $thesis);
			$thesisMetaCustomHeaders = $templateMgr->fetch($thesisPlugin->getTemplatePath() . 'metadata.tpl');
			$metaCustomHeaders = $templateMgr->get_template_vars('metaCustomHeaders');
			$templateMgr->assign('metaCustomHeaders', $metaCustomHeaders . "\n" . $thesisMetaCustomHeaders);
			$templateMgr->display($thesisPlugin->getTemplatePath() . 'view.tpl');
			} else {
				$request->redirect(null, 'thesis');
			}
		} else {
			$request->redirect(null, 'index');
		}
	}

	/**
	 * Save submitted thesis.
	 */
	function save($args, $request) {
		$this->validate();
		$this->setupTemplate();
		$journal =& $request->getJournal();

		if ($journal != null) {
			$journalId = $journal->getId();
		} else {
			$request->redirect(null, 'index');
		}

		$thesisPlugin =& PluginRegistry::getPlugin('generic', THESIS_PLUGIN_NAME);

		if ($thesisPlugin != null) {
			$thesesEnabled = $thesisPlugin->getEnabled();
		}

		if ($thesesEnabled) {
			$thesisDao = DAORegistry::getDAO('ThesisDAO');
			$thesisPlugin->import('StudentThesisForm');

			$thesisForm = new StudentThesisForm(THESIS_PLUGIN_NAME);
			$thesisForm->readInputData();

			if ($thesisForm->validate()) {
				$thesisForm->execute();
				$request->redirect(null, 'thesis');
			} else {
				$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
				$journalSettings =& $journalSettingsDao->getSettings($journalId);

				$templateMgr =& TemplateManager::getManager($request);
				$templateMgr->assign('journalSettings', $journalSettings);
				$thesisForm->display();
			}

		} else {
			$request->redirect(null, 'index');
		}
	}
}

?>
