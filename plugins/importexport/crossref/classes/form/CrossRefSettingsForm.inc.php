<?php

/**
 * @file plugins/importexport/crossref/classes/form/CrossRefSettingsForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossRefSettingsForm
 * @ingroup plugins_importexport_crossref_classes_form
 *
 * @brief Form for journal managers to setup the CrossRef plug-in.
 */


if (!class_exists('DOIExportSettingsForm')) { // Bug #7848
	import('plugins.importexport.crossref.classes.form.DOIExportSettingsForm');
}

class CrossRefSettingsForm extends DOIExportSettingsForm {

	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin CrossRefExportPlugin
	 * @param $journalId integer
	 */
	function CrossRefSettingsForm(&$plugin, $journalId) {
		// Configure the object.
		parent::DOIExportSettingsForm($plugin, $journalId);

		// Add form validation checks.
		$this->addCheck(new FormValidator($this, 'depositorName', 'required', 'plugins.importexport.crossref.settings.form.depositorNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'depositorEmail', 'required', 'plugins.importexport.crossref.settings.form.depositorEmailRequired'));
	}


	//
	// Implement template methods from DOIExportSettingsForm
	//
	/**
	 * @see DOIExportSettingsForm::getFormFields()
	 */
	function getFormFields() {
		return array(
			'depositorName' => 'string',
			'depositorEmail' => 'string',
			'username' => 'string',
			'password' => 'string',
			'automaticRegistration' => 'bool'
			);
	}

	/**
	 * @see DOIExportSettingsForm::isOptional()
	 */
	function isOptional($settingName) {
		return in_array($settingName, array('username', 'password', 'automaticRegistration'));
	}
}

?>
