<?php

/**
 * PhpAdsNewSettingsForm.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
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
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$templateMgr = &TemplateManager::getManager();

		$this->setData('ads', $this->phpAdsNewConnection->getAds());
		$this->setData('headerAdId', $plugin->getSetting($journalId, 'headerAdId'));
		$this->setData('sidebarAdId', $plugin->getSetting($journalId, 'sidebarAdId'));
		$this->setData('contentAdId', $plugin->getSetting($journalId, 'contentAdId'));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('headerAdId', 'contentAdId', 'sidebarAdId'));
	}

	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin = &$this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'headerAdHtml', $this->phpAdsNewConnection->getAdHtml($this->getData('headerAdId')));
		$plugin->updateSetting($journalId, 'headerAdId', $this->getData('headerAdId'));

		$plugin->updateSetting($journalId, 'contentAdHtml', $this->phpAdsNewConnection->getAdHtml($this->getData('contentAdId')));
		$plugin->updateSetting($journalId, 'contentAdId', $this->getData('contentAdId'));

		$plugin->updateSetting($journalId, 'sidebarAdHtml', $this->phpAdsNewConnection->getAdHtml($this->getData('sidebarAdId')));
		$plugin->updateSetting($journalId, 'sidebarAdId', $this->getData('sidebarAdId'));

		return true;
	}
}

?>
