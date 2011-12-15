<?php

/**
 * @file plugins/importexport/datacite/classes/form/DataciteSettingsForm.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataciteSettingsForm
 * @ingroup plugins_importexport_datacite_classes_form
 *
 * @brief Form for journal managers to setup the DataCite plug-in.
 */


import('plugins.importexport.datacite.classes.form.DoiExportSettingsForm');

class DataciteSettingsForm extends DoiExportSettingsForm {

	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin DataciteExportPlugin
	 * @param $journalId integer
	 */
	function DataciteSettingsForm(&$plugin, $journalId) {
		// Configure the object.
		parent::DoiExportSettingsForm($plugin, $journalId);

		// Add form validation checks.
		// The symbol is used in HTTP basic authentication and according to RFC2617 it therefore may not contain a colon.
		$this->addCheck(new FormValidatorRegExp($this, 'symbol', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.importexport.datacite.settings.form.symbolRequired', '/^[^:]+$/'));
		$this->addCheck(new FormValidator($this, 'password', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.importexport.datacite.settings.form.passwordRequired'));
	}


	//
	// Implement template methods from Form
	//
	/**
	 * @see Form::getData()
	 */
	function getData($key) {
		$value = parent::getData($key);
		if ($key == 'password') {
			// Unfortunately there is no password vault in OJS that
			// would allow us to save encrypted passwords.
			// We scramble the PW a bit to keep it from the eyes of
			// non-malevolent DB admins. But we leave it up to the user
			// whether they want to save it at all. PW-Security will
			// depend on DB and DB dump security.
			$value = base64_encode($value);
		}
		return $value;
	}


	//
	// Implement template methods from DoiExportSettingsForm
	//
	/**
	 * @see DoiExportSettingsForm::getSetting()
	 */
	function getSetting($settingName) {
		$settingValue = parent::getSetting($settingName);
		if ($settingName == 'password') {
			// See comment in self::getData() about PW scrambling.
			$settingValue = base64_decode($settingValue);
		}
		return $settingValue;
	}

	/**
	 * @see DoiExportSettingsForm::getFormFields()
	 */
	function getFormFields() {
		return array(
			'symbol' => 'string',
			'password' => 'string'
		);
	}
}

?>
