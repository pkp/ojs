<?php

/**
 * @file OpenAdsSettingsForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.openAds
 * @class OpenAdsSettingsForm
 *
 * Form for journal managers to modify Article XML Galley plugin settings
 *
 * $Id$
 */

import('form.Form');

class OpenAdsSettingsForm extends Form {
	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/** @var $openAdsConnection object */
	var $openAdsConnection;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $openAdsConnection object
	 * @param $journalId int
	 */
	function OpenAdsSettingsForm(&$plugin, $openAdsConnection, $journalId) {
		$templateMgr = &TemplateManager::getManager();

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->journalId = $journalId;
		$this->plugin = &$plugin;
		$this->openAdsConnection =& $openAdsConnection;

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$this->setData('ads', $this->openAdsConnection->getAds());
		$this->setData('headerAdId', $plugin->getSetting($journalId, 'headerAdId'));
		$this->setData('headerAdOrientation', $plugin->getSetting($journalId, 'headerAdOrientation'));
		$this->setData('sidebarAdId', $plugin->getSetting($journalId, 'sidebarAdId'));
		$this->setData('contentAdId', $plugin->getSetting($journalId, 'contentAdId'));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();

		$templateMgr->assign('orientationOptions', array(
			AD_ORIENTATION_LEFT => 'plugins.generic.openads.orientation.left',
			AD_ORIENTATION_CENTRE => 'plugins.generic.openads.orientation.centre',
			AD_ORIENTATION_RIGHT => 'plugins.generic.openads.orientation.right'
		));

		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('headerAdId', 'headerAdOrientation', 'contentAdId', 'sidebarAdId'));
	}

	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin = &$this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'headerAdId', $this->getData('headerAdId'));
		$plugin->updateSetting($journalId, 'headerAdOrientation', $this->getData('headerAdOrientation'));

		$plugin->updateSetting($journalId, 'contentAdId', $this->getData('contentAdId'));

		$plugin->updateSetting($journalId, 'sidebarAdId', $this->getData('sidebarAdId'));

		return true;
	}
}

?>
