<?php

/**
 * SettingsForm.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Form for journal managers to modify Thesis Abstract plugin settings
 *
 * $Id$
 */

import('form.Form');

class SettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function SettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin = &$plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');
	
		$this->addCheck(new FormValidator($this, 'thesisName', 'required', 'plugins.generic.thesis.settings.thesisNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'thesisEmail', 'required', 'plugins.generic.thesis.settings.thesisEmailRequired'));
	}
	
	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin = &$this->plugin;

		$this->_data = array(
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
		$this->readUserVars(array('thesisName', 'thesisEmail', 'thesisPhone', 'thesisFax', 'thesisMailingAddress', 'thesisIntroduction'));
	}
	
	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin = &$this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'thesisName', $this->getData('thesisName'));
		$plugin->updateSetting($journalId, 'thesisEmail', $this->getData('thesisEmail'));
		$plugin->updateSetting($journalId, 'thesisPhone', $this->getData('thesisPhone'));
		$plugin->updateSetting($journalId, 'thesisFax', $this->getData('thesisFax'));
		$plugin->updateSetting($journalId, 'thesisMailingAddress', $this->getData('thesisMailingAddress'));
		$plugin->updateSetting($journalId, 'thesisIntroduction', $this->getData('thesisIntroduction'));
	}
	
}

?>
