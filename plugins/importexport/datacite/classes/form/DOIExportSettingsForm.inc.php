<?php

/**
 * @file plugins/importexport/.../classes/form/DOIExportSettingsForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOIExportSettingsForm
 * @ingroup plugins_importexport_..._classes_form
 *
 * @brief Form base class for journal managers to setup DOI export plug-ins.
 */


import('lib.pkp.classes.form.Form');

class DOIExportSettingsForm extends Form {

	//
	// Protected properties
	//
	/** @var integer */
	var $_journalId;

	/**
	 * Get the journal ID.
	 * @return integer
	 */
	function getJournalId() {
		return $this->_journalId;
	}

	/** @var DoiExportPlugin */
	var $_plugin;

	/**
	 * Get the plugin.
	 * @return DoiExportPlugin
	 */
	function &getPlugIn() {
		return $this->_plugin;
	}


	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin DoiExportPlugin
	 * @param $journalId integer
	 */
	function DOIExportSettingsForm(&$plugin, $journalId) {
		// Configure the object.
		parent::Form($plugin->getTemplatePath() . 'settings.tpl');
		$this->_journalId = $journalId;
		$this->_plugin =& $plugin;

		// Add form validation checks.
		$this->addCheck(new FormValidatorPost($this));
	}


	//
	// Implement template methods from Form
	//
	/**
	 * @see Form::getData()
	 */
	function getData($key) {
		$value = parent::getData($key);
		return $value;
	}

	/**
	 * @see Form::initData()
	 */
	function initData() {
		foreach ($this->getFormFields() as $settingName => $settingType) {
			$this->setData($settingName, $this->getSetting($settingName));
		}
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->getFormFields()));
	}

	/**
	 * @see Form::execute()
	 */
	function execute() {
		$plugin =& $this->getPlugIn();
		foreach($this->getFormFields() as $settingName => $settingType) {
			$plugin->updateSetting($this->getJournalId(), $settingName, $this->getData($settingName), $settingType);
		}
	}


	//
	// Protected template methods
	//
	/**
	 * Get a plugin setting.
	 * @param $settingName
	 * @return mixed The setting value.
	 */
	function getSetting($settingName) {
		$plugin =& $this->getPlugIn();
		$settingValue = $plugin->getSetting($this->getJournalId(), $settingName);
		return $settingValue;
	}

	/**
	 * Return a list of form fields.
	 * @return array
	 */
	function getFormFields() {
		return array();
	}

	/**
	 * Check whether a given setting is optional.
	 * @param $settingName string
	 * @return boolean
	 */
	function isOptional($settingName) {
		return in_array($settingName, array('username', 'password'));
	}
}
?>
