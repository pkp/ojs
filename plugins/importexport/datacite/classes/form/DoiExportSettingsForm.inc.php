<?php

/**
 * @file plugins/importexport/.../classes/form/DoiExportSettingsForm.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DoiExportSettingsForm
 * @ingroup plugins_importexport_..._classes_form
 *
 * @brief Form base class for journal managers to setup DOI export plug-ins.
 */


import('lib.pkp.classes.form.Form');

class DoiExportSettingsForm extends Form {

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

	/** @var DataciteExportPlugin */
	var $_plugin;

	/**
	 * Get the plugin.
	 * @return DataciteExportPlugin
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
	function DoiExportSettingsForm(&$plugin, $journalId) {
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
		return $plugin->getSetting($this->getJournalId(), $settingName);
	}

	/**
	 * Return a list of form fields.
	 * @return array
	 */
	function getFormFields() {
		return array();
	}
}

?>
