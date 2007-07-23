<?php

/**
 * @file PhpMyVisitesSettingsForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 * @class PhpMyVisitesSettingsForm
 *
 * Form for journal managers to modify phpMyVisites plugin settings
 *
 * $Id$
 */

import('form.Form');

class PhpMyVisitesSettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function PhpMyVisitesSettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin = &$plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');
	
		$this->addCheck(new FormValidatorCustom($this, 'phpmvUrl', 'required', 'plugins.generic.phpmv.manager.settings.phpmvUrlRequired', create_function('$phpmvUrl', 'return strpos(trim(strtolower($phpmvUrl)), \'http://\') === 0 ? true : false;')));
		$this->addCheck(new FormValidator($this, 'phpmvSiteId', 'required', 'plugins.generic.phpmv.manager.settings.phpmvSiteIdRequired'));
	}
	
	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin = &$this->plugin;

		$this->_data = array(
			'phpmvUrl' => $plugin->getSetting($journalId, 'phpmvUrl'),
			'phpmvSiteId' => $plugin->getSetting($journalId, 'phpmvSiteId')
		);
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('phpmvUrl', 'phpmvSiteId'));
	}
	
	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin = &$this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'phpmvUrl', rtrim($this->getData('phpmvUrl'), "/"), 'string');
		$plugin->updateSetting($journalId, 'phpmvSiteId', $this->getData('phpmvSiteId'), 'int');
	}
}

?>
