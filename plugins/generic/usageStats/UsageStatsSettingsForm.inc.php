<?php

/**
 * @file plugins/generic/usageStats/UsageStatsSettingsForm.inc.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
		$this->addCheck(new FormValidatorCustom($this, 'dataPrivacyOption', FORM_VALIDATOR_OPTIONAL_VALUE, 'plugins.generic.usageStats.settings.dataPrivacyOption.requiresSalt', array(&$this, '_dependentFormFieldIsSet'), array(&$this, 'saltFilepath')));
		$this->addCheck(new FormValidatorCustom($this, 'dataPrivacyOption', FORM_VALIDATOR_OPTIONAL_VALUE, 'plugins.generic.usageStats.settings.dataPrivacyOption.excludesRegion', array(&$this, '_dependentFormFieldIsSet'), array(&$this, 'selectedOptionalColumns', STATISTICS_DIMENSION_REGION), true));
		$this->addCheck(new FormValidatorCustom($this, 'dataPrivacyOption', FORM_VALIDATOR_OPTIONAL_VALUE, 'plugins.generic.usageStats.settings.dataPrivacyOption.excludesCity', array(&$this, '_dependentFormFieldIsSet'), array(&$this, 'selectedOptionalColumns', STATISTICS_DIMENSION_CITY), true));
		$this->addCheck(new FormValidatorCustom($this, 'saltFilepath', FORM_VALIDATOR_OPTIONAL_VALUE, 'plugins.generic.usageStats.settings.dataPrivacyOption.saltFilepath.invalid', array(&$plugin, 'validateSaltpath')));
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
		$this->setData('saltFilepath', $plugin->getSaltPath());
		$this->setData('selectedOptionalColumns', $plugin->getSetting(CONTEXT_ID_NONE, 'optionalColumns'));
		$this->setData('compressArchives', $plugin->getSetting(CONTEXT_ID_NONE, 'compressArchives'));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('createLogFiles','accessLogFileParseRegex', 'dataPrivacyOption', 'optionalColumns', 'compressArchives', 'saltFilepath'));
		$this->setData('selectedOptionalColumns', $this->getData('optionalColumns'));
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
		$plugin->updateSetting(CONTEXT_ID_NONE, 'saltFilepath', $this->getData('saltFilepath'));

		$optionalColumns = $this->getData('optionalColumns');
		// Make sure optional columns data makes sense.
		if (in_array(STATISTICS_DIMENSION_CITY, $optionalColumns) && !in_array(STATISTICS_DIMENSION_REGION, $optionalColumns)) {
			$user = Request::getUser();
			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
			$notificationManager->createTrivialNotification(
				$user->getId(), NOTIFICATION_TYPE_WARNING, array('contents' => __('plugins.generic.usageStats.settings.optionalColumns.cityRequiresRegion'))
			);
			$optionalColumns[] = STATISTICS_DIMENSION_REGION;
		}
		$plugin->updateSetting(CONTEXT_ID_NONE, 'optionalColumns', $optionalColumns);
	}

	/**
	 * Get optional columns list.
	 * @return array
	 */
	function getOptionalColumnsList() {
		$plugin =& $this->plugin;
		$reportPlugin = $plugin->getReportPlugin();
		$optionalColumns = $reportPlugin->getOptionalColumns(OJS_METRIC_TYPE_COUNTER);
		$columnsList = array();
		foreach ($optionalColumns as $column) {
			$columnsList[$column] = StatisticsHelper::getColumnNames($column);
		}
		return $columnsList;
	}

	/**
	 * Check for the presence of dependent fields if a field value is set
	 * The Complement call will enforce a dependent value as unset if a field value is set
	 * @param mixed $fieldValue the value of the field being checked
	 * @param object $form a reference to this form
	 * @param string $dependentFieldName the name of the dependent field
	 * @param mixed $expectedValue if provided, the expected value which must be in the dependent field
	 * @return boolean
	 */
	function _dependentFormFieldIsSet($fieldValue, $form, $dependentFieldName, $expectedValue = null) {
		if ($fieldValue) {
			$dependentValue = $form->getData($dependentFieldName);
			if ($dependentValue) {
				if ($expectedValue) {
					// Check the expected value against the dependent value
					if (is_array($dependentValue)) {
						return in_array($expectedValue, $dependentValue, true);
					} else {
						return $dependentValue === $expectedValue;
					}
				} else {
					// Field was set and any dependent value is allowed
					return true;
				}
			} else {
				// Field was set but no dependent value was set
				return false;
			}
		} else {
			// This is false so the complement call will be true when checking a negative dependency
			// e.g., if $fieldValue, $dependentFieldName can't contain $expectedValue
			return false;
		}
	}

}

?>
