<?php

/**
 * @file plugins/importexport/medra/classes/form/MedraSettingsForm.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MedraSettingsForm
 * @ingroup plugins_importexport_medra_classes_form
 *
 * @brief Form for journal managers to setup the mEDRA plug-in.
 */


import('lib.pkp.classes.form.Form');

class MedraSettingsForm extends Form {

	//
	// Private properties
	//
	/** @var integer */
	var $_journalId;

	/**
	 * Get the journal ID.
	 * @return integer
	 */
	function _getJournalId() {
		return $this->_journalId;
	}

	/** @var MedraExportPlugin */
	var $_plugin;

	/**
	 * Get the plugin.
	 * @return MedraExportPlugin
	 */
	function &_getPlugIn() {
		return $this->_plugin;
	}


	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin MedraExportPlugin
	 * @param $journalId integer
	 */
	function MedraSettingsForm(&$plugin, $journalId) {
		// Configure the object.
		parent::Form($plugin->getTemplatePath() . 'settings.tpl');
		$this->_journalId = $journalId;
		$this->_plugin =& $plugin;

		// Add form validation checks.
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorInSet($this, 'exportIssuesAs', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.importexport.medra.settings.form.exportIssuesAs', array(O4DOI_ISSUE_AS_WORK, O4DOI_ISSUE_AS_MANIFESTATION)));
		$this->addCheck(new FormValidatorInSet($this, 'publicationCountry', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.importexport.medra.settings.form.publicationCountry', array_keys($this->_getCountries())));
	}


	//
	// Implement template methods from Form
	//
	/**
	 * @see Form::display()
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();

		// Issue export options.
		$exportIssueOptions = array(
			O4DOI_ISSUE_AS_WORK => __('plugins.importexport.medra.settings.form.work'),
			O4DOI_ISSUE_AS_MANIFESTATION => __('plugins.importexport.medra.settings.form.manifestation'),
		);
		$templateMgr->assign('exportIssueOptions', $exportIssueOptions);

		// Countries.
		$templateMgr->assign_by_ref('countries', $this->_getCountries());
		parent::display();
	}

	/**
	 * @see Form::initData()
	 */
	function initData() {
		$plugin =& $this->_getPlugIn();
		foreach ($this->_getFormFields() as $settingName => $settingType) {
			$this->setData($settingName, $plugin->getSetting($this->_getJournalId(), $settingName));
		}
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->_getFormFields()));
	}

	/**
	 * @see Form::execute()
	 */
	function execute() {
		$plugin =& $this->_getPlugIn();
		foreach($this->_getFormFields() as $settingName => $settingType) {
			$plugin->updateSetting($this->_getJournalId(), $settingName, $this->getData($settingName), $settingType);
		}
	}


	//
	// Private helper methods
	//
	/**
	 * Return a list of countries eligible as publication countries.
	 * @return array
	 */
	function &_getCountries() {
		$countryDao =& DAORegistry::getDAO('CountryDAO'); /* @var $countryDao CountryDAO */
		$countries =& $countryDao->getCountries();
		return $countries;
	}

	/**
	 * Return a list of form fields.
	 * @return array
	 */
	function _getFormFields() {
		return array(
			'exportIssuesAs' => 'int',
			'publicationCountry' => 'string'
		);
	}
}

?>
