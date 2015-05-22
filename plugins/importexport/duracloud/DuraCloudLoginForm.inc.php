<?php

/**
 * @file plugins/importexport/duracloud/DuraCloudLoginForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DuraCloudLoginForm
 * @ingroup plugins_importexport_duracloud
 *
 * @brief Form to allow login to an external DuraCloud service.
 */

import('lib.pkp.classes.form.Form');

class DuraCloudLoginForm extends Form {
	/**
	 * Constructor.
	 */
	function DuraCloudLoginForm(&$plugin) {
		parent::Form($plugin->getTemplatePath() . 'index.tpl');

		// Validation checks for this form
		$this->addCheck(new FormValidatorUrl($this, 'duracloudUrl', 'required', 'plugins.importexport.duracloud.configuration.urlRequired'));
		$this->addCheck(new FormValidator($this, 'duracloudUsername', 'required', 'plugins.importexport.duracloud.configuration.usernameRequired'));
		$this->addCheck(new FormValidator($this, 'duracloudPassword', 'required', 'plugins.importexport.duracloud.configuration.passwordRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display(&$plugin) {
		$templateMgr =& TemplateManager::getManager();
		if ($plugin->isDuraCloudConfigured()) {
			// Provide configuration details
			$templateMgr->assign('isConfigured', true);
			$templateMgr->assign('duracloudUrl', $plugin->getDuraCloudUrl());
			$templateMgr->assign('duracloudUsername', $plugin->getDuraCloudUsername());

			// Get a list of spaces and the currently selected space.
			$dcc =& $plugin->getDuraCloudConnection();
			$ds = new DuraStore($dcc);
			$templateMgr->assign('spaces', $ds->getSpaces());
			$templateMgr->assign('duracloudSpace', $plugin->getDuraCloudSpace());
		}
		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('duracloudUrl', 'duracloudUsername', 'duracloudPassword'));
		return parent::readInputData();
	}

	/**
	 * Extend 
	 * @see Form::validate()
	 */
	function validate() {
		// Check that all required fields are filled.
		if (!parent::validate()) return false;

		// Verify that the credentials work.
		$dcc = new DuraCloudConnection(
			$this->getData('duracloudUrl'),
			$this->getData('duracloudUsername'),
			$this->getData('duracloudPassword')
		);
		$ds = new DuraStore($dcc);
		if ($ds->getSpaces($storeId) === false) {
			// Could not get a list of spaces.
			$this->addError('duracloudUrl', __('plugins.importexport.duracloud.configuration.credentialsInvalid'));
			return false;
		}

		// Success.
		return true;
	}

	/**
	 * Perform a test login and store the details.
	 * @param $plugin DuraCloudImportExportPlugin
	 * @return boolean success
	 */
	function execute(&$plugin) {
		parent::execute();
		$plugin->storeDuraCloudConfiguration(
			$this->getData('duracloudUrl'),
			$this->getData('duracloudUsername'),
			$this->getData('duracloudPassword')
		);
	}
}

?>
