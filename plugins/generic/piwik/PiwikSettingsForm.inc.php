<?php

/**
 * @file plugins/generic/piwik/PiwikSettingsForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PiwikSettingsForm
 * @ingroup plugins_generic_piwik
 *
 * @brief Form for journal managers to modify piwik plugin settings
 */


import('form.Form');

class PiwikSettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function PiwikSettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin = &$plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidatorCustom($this, 'piwikUrl', 'required', 'plugins.generic.piwik.manager.settings.piwikUrlRequired', create_function('$piwikUrl', 'return strpos(trim(strtolower_codesafe($piwikUrl)), \'http://\') === 0 ? true : false;')));
		$this->addCheck(new FormValidator($this, 'piwikSiteId', 'required', 'plugins.generic.piwik.manager.settings.piwikSiteIdRequired'));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin = &$this->plugin;

		$this->_data = array(
			'piwikUrl' => $plugin->getSetting($journalId, 'piwikUrl'),
			'piwikSiteId' => $plugin->getSetting($journalId, 'piwikSiteId')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('piwikUrl', 'piwikSiteId'));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin = &$this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'piwikUrl', rtrim($this->getData('piwikUrl'), "/"), 'string');
		$plugin->updateSetting($journalId, 'piwikSiteId', $this->getData('piwikSiteId'), 'int');
	}
}

?>
