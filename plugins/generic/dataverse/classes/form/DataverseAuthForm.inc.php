<?php

/**
 * @file plugins/generic/dataverse/classes/form/DataverseAuthForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataverseAuthForm
 * @ingroup plugins_generic_dataverse
 *
 * @brief Plugin settings: connect to a Dataverse Network 
 */
define('DATAVERSE_PLUGIN_PASSWORD_SLUG', '********');

import('lib.pkp.classes.form.Form');

class DataverseAuthForm extends Form {

	/** @var $_plugin DataversePlugin */
	var $_plugin;

	/** @var $_journalId int */
	var $_journalId;

	/**
	 * Constructor. 
	 * @param $plugin DataversePlugin
	 * @param $journalId int
	 * @see Form::Form()
	 */
	function DataverseAuthForm(&$plugin, $journalId) {
		$this->_plugin =& $plugin;
		$this->_journalId = $journalId;

		parent::Form($plugin->getTemplatePath() . 'dataverseAuthForm.tpl');
		$this->addCheck(new FormValidatorUrl($this, 'dvnUri', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.dataverse.settings.dvnUriRequired'));
		$this->addCheck(new FormValidator($this, 'username', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.dataverse.settings.usernameRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'dvnUri', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.dataverse.settings.dvnUriNotValid', array(&$this, '_getServiceDocument')));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * @see Form::initData()
	 */
	function initData() {
		$plugin =& $this->_plugin;

		// Initialize from plugin settings
		$this->setData('dvnUri', $plugin->getSetting($this->_journalId, 'dvnUri'));		 
		$this->setData('username', $plugin->getSetting($this->_journalId, 'username'));				 
		
		// If password has already been set, echo back slug
		$password = $plugin->getSetting($this->_journalId, 'password');
		if (!empty($password)) {
			$password === DATAVERSE_PLUGIN_PASSWORD_SLUG ? 
							$this->setData('password', '') : // Leave unset if slug stored for API token
							$this->setData('password', DATAVERSE_PLUGIN_PASSWORD_SLUG);
		}
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('dvnUri', 'username', 'password'));
		$request =& PKPApplication::getRequest();
		$password = $request->getUserVar('password');
		if ($password === DATAVERSE_PLUGIN_PASSWORD_SLUG) {
			$plugin =& $this->_plugin;
			$password = $plugin->getSetting($this->_journalId, 'password');
		}
		if (!$password) {
			// Password not required when API token provided, but SWORDAPPClient 
			// requires a non-null password. 
			$password = DATAVERSE_PLUGIN_PASSWORD_SLUG;
		}
		$this->setData('password', $password);
		$this->setData('dvnUri', preg_replace("/\/+$/", '', $this->getData('dvnUri')));
	}

	/**
	 * @see Form::execute()
	 */
	function execute() {
		$plugin =& $this->_plugin;
		$plugin->updateSetting($this->_journalId, 'dvnUri', $this->getData('dvnUri'), 'string');
		$plugin->updateSetting($this->_journalId, 'username', $this->getData('username'), 'string');
		$plugin->updateSetting($this->_journalId, 'password', $this->getData('password'), 'string'); 
		$plugin->updateSetting($this->_journalId, 'apiVersion', $this->getData('apiVersion'), 'string');
	}
	
	/**
	 * Form validator: verify service document can be retrieved from specified 
	 * Dataverse with given username & password.
	 * @return boolean 
	 */
	function _getServiceDocument() {
		// Dataverse SWORD API version. Assume v1 if not set.
		$this->setData('apiVersion', 
						$apiVersion = $this->_plugin->getSetting($this->_journalId, 'apiVersion') ?
						$this->_plugin->getSetting($this->_journalId, 'apiVersion') : '1');
		
		// Fetch service document
		$sdRequest = preg_match('/\/dvn$/', $this->getData('dvnUri')) ? '' : '/dvn';
		$sdRequest .= '/api/data-deposit/v'. $this->getData('apiVersion') . '/swordv2/service-document';

		$client = $this->_plugin->_initSwordClient();
		$sd = $client->servicedocument(
						$this->getData('dvnUri') . $sdRequest,
						$this->getData('username'),
						$this->getData('password'),
						''); // on behalf of
		
		// Recover from errors where user has entered 'http' instead of 'https'
		if (isset($sd) && $sd->sac_status != DATAVERSE_PLUGIN_HTTP_STATUS_OK && preg_match('/^http\:/', $this->getData('dvnUri'))) {
			$this->setData('dvnUri', preg_replace('/^http\:/', 'https:', $this->getData('dvnUri')));
			$sd = $client->servicedocument(
							$this->getData('dvnUri') . $sdRequest,
							$this->getData('username'), 
							$this->getData('password'), 
							''); // on behalf of
		}
		
		// Check service doc for deprecation warnings & update API.
		if (isset($sd) && $sd->sac_status == DATAVERSE_PLUGIN_HTTP_STATUS_OK) {
			$newVersion = $this->_plugin->checkAPIVersion($sd);
			if ($newVersion) $this->setData('apiVersion', $newVersion);
		}
		
		return (isset($sd) && $sd->sac_status == DATAVERSE_PLUGIN_HTTP_STATUS_OK);
		
	}
}
