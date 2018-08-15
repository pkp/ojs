<?php

/**
 * @file plugins/importexport/medra/classes/form/MedraSettingsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	var $_contextId;

	/**
	 * Get the context ID.
	 * @return integer
	 */
	function _getContextId() {
		return $this->_contextId;
	}

	/** @var MedraExportPlugin */
	var $_plugin;

	/**
	 * Get the plugin.
	 * @return MedraExportPlugin
	 */
	function _getPlugin() {
		return $this->_plugin;
	}

	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin MedraExportPlugin
	 * @param $contextId integer
	 */
	function __construct($plugin, $contextId) {
		$this->_contextId = $contextId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		// DOI plugin settings action link
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		if (isset($pubIdPlugins['doipubidplugin'])) {
			$application = Application::getApplication();
			$request = $application->getRequest();
			$dispatcher = $application->getDispatcher();
			import('lib.pkp.classes.linkAction.request.AjaxModal');
			$doiPluginSettingsLinkAction = new LinkAction(
					'settings',
					new AjaxModal(
							$dispatcher->url($request, ROUTE_COMPONENT, null, 'grid.settings.plugins.SettingsPluginGridHandler', 'manage', null, array('plugin' => 'doipubidplugin', 'category' => 'pubIds')),
							__('plugins.importexport.common.settings.DOIPluginSettings')
							),
					__('plugins.importexport.common.settings.DOIPluginSettings'),
					null
					);
			$this->setData('doiPluginSettingsLinkAction', $doiPluginSettingsLinkAction);
		}

		// Add form validation checks.
		$this->addCheck(new FormValidator($this, 'registrantName', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.importexport.medra.settings.form.registrantNameRequired'));
		$this->addCheck(new FormValidator($this, 'fromCompany', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.importexport.medra.settings.form.fromCompanyRequired'));
		$this->addCheck(new FormValidator($this, 'fromName', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.importexport.medra.settings.form.fromNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'fromEmail', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.importexport.medra.settings.form.fromEmailRequired'));
		$this->addCheck(new FormValidatorInSet($this, 'exportIssuesAs', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.importexport.medra.settings.form.exportIssuesAs', array(O4DOI_ISSUE_AS_WORK, O4DOI_ISSUE_AS_MANIFESTATION)));
		$this->addCheck(new FormValidatorInSet($this, 'publicationCountry', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.importexport.medra.settings.form.publicationCountry', array_keys($this->_getCountries())));
		// The username is used in HTTP basic authentication and according to RFC2617 it therefore may not contain a colon.
		$this->addCheck(new FormValidatorRegExp($this, 'username', FORM_VALIDATOR_OPTIONAL_VALUE, 'plugins.importexport.medra.settings.form.usernameRequired', '/^[^:]+$/'));
		$this->addCheck(new FormValidatorPost($this));
	}


	//
	// Implement template methods from Form
	//
	/**
	 * @copydoc Form::initData()
	 */
	function initData() {
		$contextId = $this->_getContextId();
		$plugin = $this->_getPlugin();
		foreach($this->getFormFields() as $fieldName => $fieldType) {
			$this->setData($fieldName, $plugin->getSetting($contextId, $fieldName));
		}
	}

	/**
	 * copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);

		// Issue export options.
		$exportIssueOptions = array(
			O4DOI_ISSUE_AS_WORK => __('plugins.importexport.medra.settings.form.work'),
			O4DOI_ISSUE_AS_MANIFESTATION => __('plugins.importexport.medra.settings.form.manifestation'),
		);
		$templateMgr->assign('exportIssueOptions', $exportIssueOptions);

		// Countries.
		$templateMgr->assign('countries', $this->_getCountries());
		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->getFormFields()));
	}

	/**
	 * Execute the form.
	 */
	function execute() {
		$plugin = $this->_getPlugin();
		$contextId = $this->_getContextId();
		foreach($this->getFormFields() as $fieldName => $fieldType) {
			$plugin->updateSetting($contextId, $fieldName, $this->getData($fieldName), $fieldType);
		}
	}


	//
	// Public helper methods
	//
	/**
	 * Get form fields
	 * @return array (field name => field type)
	 */
	function getFormFields() {
		return array(
			'registrantName' => 'string',
			'fromCompany' => 'string',
			'fromName' => 'string',
			'fromEmail' => 'string',
			'publicationCountry' => 'string',
			'exportIssuesAs' => 'int',
			'username' => 'string',
			'password' => 'string',
			'automaticRegistration' => 'bool',
			'testMode' => 'bool'
		);
	}

	/**
	 * Is the form field optional
	 * @param $settingName string
	 * @return boolean
	 */
	function isOptional($settingName) {
		return in_array($settingName, array('username', 'password', 'automaticRegistration', 'testMode'));
	}


	//
	// Private helper methods
	//
	/**
	 * Return a list of countries eligible as publication countries.
	 * @return array
	 */
	function _getCountries() {
		$countryDao = DAORegistry::getDAO('CountryDAO'); /* @var $countryDao CountryDAO */
		$countries = $countryDao->getCountries();
		return $countries;
	}
}


