<?php

/**
 * @file ThesisSettingsForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.thesis
 * @class ThesisSettingsForm
 *
 * Form for journal managers to modify Thesis Abstract plugin settings
 *
 * $Id$
 */

import('form.Form');

class ThesisSettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/** @var validOrder array keys are valid thesis order values */
	var $validOrder;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function ThesisSettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin = &$plugin;

		$this->validOrder = array (
			THESIS_ORDER_SUBMISSION_DATE_ASC => Locale::translate('plugins.generic.thesis.settings.order.submissionDateAsc'),
			THESIS_ORDER_SUBMISSION_DATE_DESC => Locale::translate('plugins.generic.thesis.settings.order.submissionDateDesc'),
			THESIS_ORDER_APPROVAL_DATE_ASC => Locale::translate('plugins.generic.thesis.settings.order.approvalDateAsc'),
			THESIS_ORDER_APPROVAL_DATE_DESC => Locale::translate('plugins.generic.thesis.settings.order.approvalDateDesc'),
			THESIS_ORDER_LASTNAME_ASC => Locale::translate('plugins.generic.thesis.settings.order.lastNameAsc'),
			THESIS_ORDER_LASTNAME_DESC => Locale::translate('plugins.generic.thesis.settings.order.lastNameDesc'),
			THESIS_ORDER_TITLE_ASC => Locale::translate('plugins.generic.thesis.settings.order.titleAsc'),
			THESIS_ORDER_TITLE_DESC => Locale::translate('plugins.generic.thesis.settings.order.titleDesc')
		);

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidator($this, 'thesisName', 'required', 'plugins.generic.thesis.settings.thesisNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'thesisEmail', 'required', 'plugins.generic.thesis.settings.thesisEmailRequired'));

		$this->addCheck(new FormValidatorInSet($this, 'thesisOrder', 'required', 'plugins.generic.thesis.settings.thesisOrderValid', array_keys($this->validOrder)));

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('validOrder', $this->validOrder);
		parent::display();
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin = &$this->plugin;

		$this->_data = array(
			'enableUploadCode' => $plugin->getSetting($journalId, 'enableUploadCode'),
			'uploadCode' => $plugin->getSetting($journalId, 'uploadCode'),
			'validOrder' => $this->validOrder,
			'thesisOrder' => $plugin->getSetting($journalId, 'thesisOrder'),
			'thesisName' => $plugin->getSetting($journalId, 'thesisName'),
			'thesisEmail' => $plugin->getSetting($journalId, 'thesisEmail'),
			'thesisPhone' => $plugin->getSetting($journalId, 'thesisPhone'),
			'thesisFax' => $plugin->getSetting($journalId, 'thesisFax'),
			'thesisMailingAddress' => $plugin->getSetting($journalId, 'thesisMailingAddress'),
			'thesisIntroduction' => $plugin->getSetting($journalId, 'thesisIntroduction')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('enableUploadCode', 'uploadCode', 'thesisOrder', 'thesisName', 'thesisEmail', 'thesisPhone', 'thesisFax', 'thesisMailingAddress', 'thesisIntroduction'));

		if (!empty($this->_data['enableUploadCode'])) {
			$this->addCheck(new FormValidator($this, 'uploadCode', 'required', 'plugins.generic.thesis.settings.uploadCodeRequired'));
		}
	}

	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin = &$this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'enableUploadCode', $this->getData('enableUploadCode'), 'bool');
		$plugin->updateSetting($journalId, 'uploadCode', $this->getData('uploadCode'), 'string');
		$plugin->updateSetting($journalId, 'thesisOrder', $this->getData('thesisOrder'), 'int');
		$plugin->updateSetting($journalId, 'thesisName', $this->getData('thesisName'), 'string');
		$plugin->updateSetting($journalId, 'thesisEmail', $this->getData('thesisEmail'), 'string');
		$plugin->updateSetting($journalId, 'thesisPhone', $this->getData('thesisPhone'), 'string');
		$plugin->updateSetting($journalId, 'thesisFax', $this->getData('thesisFax'), 'string');
		$plugin->updateSetting($journalId, 'thesisMailingAddress', $this->getData('thesisMailingAddress'), 'string');
		$plugin->updateSetting($journalId, 'thesisIntroduction', $this->getData('thesisIntroduction'), 'string');
	}

}

?>
