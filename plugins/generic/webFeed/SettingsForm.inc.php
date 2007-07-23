<?php

/**
 * @file SettingsForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 * @class SettingsForm
 *
 * Form for journal managers to modify web feeds plugin settings
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
		$this->addCheck(new FormValidatorPost($this));
	}
	
	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin = &$this->plugin;

		$this->setData('displayPage', $plugin->getSetting($journalId, 'displayPage'));
		$this->setData('displayItems', $plugin->getSetting($journalId, 'displayItems'));
		$this->setData('recentItems', $plugin->getSetting($journalId, 'recentItems'));
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('displayPage','displayItems','recentItems'));

		// check that recent items value is a positive integer
		if ((int) $this->getData('recentItems') <= 0) $this->setData('recentItems', '');

		// if recent items is selected, check that we have a value
		if ($this->getData('displayItems') == "recent") {
			$this->addCheck(new FormValidator($this, 'recentItems', 'required', 'plugins.generic.webfeed.settings.recentItemsRequired'));
		}

	}
	
	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin = &$this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'displayPage', $this->getData('displayPage'));
		$plugin->updateSetting($journalId, 'displayItems', $this->getData('displayItems'));
		$plugin->updateSetting($journalId, 'recentItems', $this->getData('recentItems'));
	}
	
}

?>
