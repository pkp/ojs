<?php

/**
 * @file PhpAdsNewSettingsForm.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 * @class PhpAdsNewSettingsForm
 *
 * Form for journal managers to modify Article XML Galley plugin settings
 *
 * $Id$
 */

import('form.Form');

class PhpAdsNewSettingsForm extends Form {
	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/** @var $phpAdsNewConnection object */
	var $phpAdsNewConnection;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $phpAdsNewConnection object
	 * @param $journalId int
	 */
	function PhpAdsNewSettingsForm(&$plugin, $phpAdsNewConnection, $journalId) {
		$templateMgr = &TemplateManager::getManager();

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->journalId = $journalId;
		$this->plugin = &$plugin;
		$this->phpAdsNewConnection =& $phpAdsNewConnection;

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$this->setData('ads', $this->phpAdsNewConnection->getAds());
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
			AD_ORIENTATION_LEFT => 'plugins.generic.phpadsnew.orientation.left',
			AD_ORIENTATION_CENTRE => 'plugins.generic.phpadsnew.orientation.centre',
			AD_ORIENTATION_RIGHT => 'plugins.generic.phpadsnew.orientation.right'
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
