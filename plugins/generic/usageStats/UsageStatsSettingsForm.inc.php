<?php

/**
 * @file plugins/generic/usageStats/UsageStatsSettingsForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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

		$this->setData('createLogFiles', $plugin->getSetting(CONTEXT_ID_NONE, 'createLogFiles'));
		$this->setData('accessLogFileParseRegex', $plugin->getSetting(CONTEXT_ID_NONE, 'accessLogFileParseRegex'));
		$this->setData('dataPrivacyOption', $plugin->getSetting(CONTEXT_ID_NONE, 'dataPrivacyOption'));
		$this->setData('selectedOptionalColumns', $plugin->getSetting(CONTEXT_ID_NONE, 'optionalColumns'));
		$this->setData('compressArchives', $plugin->getSetting(CONTEXT_ID_NONE, 'compressArchives'));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('createLogFiles','accessLogFileParseRegex', 'dataPrivacyOption', 'optionalColumns', 'compressArchives'));
	}

	/**
	 * @see Form::fetch()
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pluginName', $this->plugin->getName());
		$saltFilepath = Config::getVar('usageStats', 'salt_filepath');
		$templateMgr->assign('saltFilepath', $saltFilepath && file_exists($saltFilepath) && is_writable($saltFilepath));
		$templateMgr->assign('optionalColumnsOptions', $this->getOptionalColumnsList());
		if (!$this->getData('selectedOptionalColumns')) {
			$this->setData('selectedOptionalColumns', array());
		}
		parent::display();
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;

		$plugin->updateSetting(CONTEXT_ID_NONE, 'createLogFiles', $this->getData('createLogFiles'));
		$plugin->updateSetting(CONTEXT_ID_NONE, 'accessLogFileParseRegex', $this->getData('accessLogFileParseRegex'));
		$plugin->updateSetting(CONTEXT_ID_NONE, 'dataPrivacyOption', $this->getData('dataPrivacyOption'));
		$plugin->updateSetting(CONTEXT_ID_NONE, 'compressArchives', $this->getData('compressArchives'));

		$optionalColumns = $this->getData('optionalColumns');
		// Make sure optional columns data makes sense.
		if (in_array(STATISTICS_DIMENSION_CITY, $optionalColumns) && !in_array(STATISTICS_DIMENSION_REGION, $optionalColumns)) {
			$optionalColumns[] = STATISTICS_DIMENSION_REGION;
		}
		$plugin->updateSetting(CONTEXT_ID_NONE, 'optionalColumns', $optionalColumns);
	}

	/**
	 * Get optional columns list.
	 * @return array
	 */
	function getOptionalColumnsList() {
		return array(
			STATISTICS_DIMENSION_CITY => StatisticsHelper::getColumnNames(STATISTICS_DIMENSION_CITY),
			STATISTICS_DIMENSION_REGION => StatisticsHelper::getColumnNames(STATISTICS_DIMENSION_REGION)
		);
	}
}

?>
