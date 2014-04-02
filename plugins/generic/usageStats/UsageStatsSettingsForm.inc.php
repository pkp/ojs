<?php

/**
 * @file plugins/generic/usageStats/UsageStatsSettingsForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsSettingsForm
 * @ingroup plugins_generic_usageStats
 *
 * @brief Form for journal managers to modify usage statistics plugin settings.
 */

import('lib.pkp.classes.form.Form');

class UsageStatsSettingsForm extends Form {

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 */
	function UsageStatsSettingsForm(&$plugin) {
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'usageStatsSettingsForm.tpl');
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$plugin =& $this->plugin;

		$this->setData('createLogFiles', $plugin->getSetting(CONTEXT_SITE, 'createLogFiles'));
		$this->setData('accessLogFileParseRegex', $plugin->getSetting(0, 'accessLogFileParseRegex'));
		$this->setData('minTimeBetweenRequests', $plugin->getSetting(0, 'minTimeBetweenRequests'));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('createLogFiles','accessLogFileParseRegex', 'minTimeBetweenRequests'));
	}

	/**
	 * @see Form::fetch()
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->plugin->getName());
		parent::display();
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;

		$plugin->updateSetting(0, 'createLogFiles', $this->getData('createLogFiles'));
		$plugin->updateSetting(0, 'accessLogFileParseRegex', $this->getData('accessLogFileParseRegex'));
		$plugin->updateSetting(0, 'minTimeBetweenRequests', (int)$this->getData('minTimeBetweenRequests'));
	}

}

?>
