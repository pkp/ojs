<?php

/**
 * @file plugins/importexport/datacite/classes/form/DataciteSettingsForm.inc.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataciteSettingsForm
 * @ingroup plugins_importexport_datacite_classes_form
 *
 * @brief Form for journal managers to setup the DataCite plug-in.
 */


if (!class_exists('DOIExportSettingsForm')) { // Bug #7848
	import('plugins.importexport.datacite.classes.form.DOIExportSettingsForm');
}

class DataciteSettingsForm extends DOIExportSettingsForm {

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
		parent::DOIExportSettingsForm($plugin, $journalId);

		// Add form validation checks.
		// The username is used in HTTP basic authentication and according to RFC2617 it therefore may not contain a colon.
		$this->addCheck(new FormValidatorRegExp($this, 'username', FORM_VALIDATOR_OPTIONAL_VALUE, 'plugins.importexport.datacite.settings.form.usernameRequired', '/^[^:]+$/'));
		$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'plugins.importexport.datacite.settings.form.usernameRequired', create_function('$username,$form', 'if ($form->getData(\'automaticRegistration\') && empty($username)) { return false; } return true;'), array(&$this)));
		$this->addCheck(new FormValidatorCustom($this, 'password', 'required', 'plugins.importexport.datacite.settings.form.passwordRequired', create_function('$password,$form', 'if ($form->getData(\'automaticRegistration\') && empty($password)) { return false; } return true;'), array(&$this)));
	}

	/**
	 * @see Form::display()
	 */
	function display($request) {
		$templateMgr =& TemplateManager::getManager($request);
		$plugin = $this->_plugin;
		$templateMgr->assign('unregisteredURL', $request->url(null, null, 'importexport', array('plugin', $plugin->getName(), 'all')));
		parent::display($request);
	}

	//
	// Implement template methods from DOIExportSettingsForm
	//
	/**
	 * @see DOIExportSettingsForm::getFormFields()
	 */
	function getFormFields() {
		return array(
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
