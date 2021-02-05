<?php

/**
 * @file plugins/importexport/crossref/classes/form/CrossRefSettingsForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CrossRefSettingsForm
 * @ingroup plugins_importexport_crossref
 *
 * @brief Form for journal managers to setup CrossRef plugin
 */


import('lib.pkp.classes.form.Form');

class CrossRefSettingsForm extends Form {

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

	/** @var CrossRefExportPlugin */
	var $_plugin;

	/**
	 * Get the plugin.
	 * @return CrossRefExportPlugin
	 */
	function _getPlugin() {
		return $this->_plugin;
	}


	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin CrossRefExportPlugin
	 * @param $contextId integer
	 */
	function __construct($plugin, $contextId) {
		$this->_contextId = $contextId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		// DOI plugin settings action link
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		if (isset($pubIdPlugins['doipubidplugin'])) {
			$application = Application::get();
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
		$this->addCheck(new FormValidator($this, 'depositorName', 'required', 'plugins.importexport.crossref.settings.form.depositorNameRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'depositorEmail', 'required', 'plugins.importexport.crossref.settings.form.depositorEmailRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
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
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->getFormFields()));
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute(...$functionArgs) {
		$plugin = $this->_getPlugin();
		$contextId = $this->_getContextId();
		foreach($this->getFormFields() as $fieldName => $fieldType) {
			$plugin->updateSetting($contextId, $fieldName, $this->getData($fieldName), $fieldType);
		}
		parent::execute(...$functionArgs);
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
			'depositorName' => 'string',
			'depositorEmail' => 'string',
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

}


