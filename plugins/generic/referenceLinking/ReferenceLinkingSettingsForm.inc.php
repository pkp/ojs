<?php

/**
 * @file plugins/generic/referenceLinking/ReferenceLinkingSettingsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReferenceLinkingSettingsForm
 * @ingroup plugins_generic_referenceLinking
 *
 * @brief Form for journal managers to setup the reference linking plugin
 */


import('lib.pkp.classes.form.Form');

class ReferenceLinkingSettingsForm extends Form {

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

	/** @var ReferenceLinkingPlugin */
	var $_plugin;

	/**
	 * Get the plugin.
	 * @return ReferenceLinkingPlugin
	 */
	function _getPlugin() {
		return $this->_plugin;
	}


	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin ReferenceLinkingPlugin
	 * @param $contextId integer
	 */
	function __construct($plugin, $contextId) {
		$this->_contextId = $contextId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		// Add form validation checks.
		$this->addCheck(new FormValidator($this, 'username', 'required', 'plugins.generic.referenceLinking.settings.form.usernameRequired'));
		$this->addCheck(new FormValidator($this, 'password', 'required', 'plugins.generic.referenceLinking.settings.form.passwordRequired'));
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
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$plugin = $this->_getPlugin();
		$dispatcher = $request->getDispatcher();
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $plugin->getName());
		// DOI plugin settings action link
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		if (isset($pubIdPlugins['doipubidplugin'])) {
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
			$templateMgr->assign('doiPluginSettingsLinkAction', $doiPluginSettingsLinkAction);
		}
		// Settings > Workflow > Submission action link
		import('lib.pkp.classes.linkAction.request.RedirectAction');
		$submissionSettingsLinkAction = new LinkAction(
			'settings',
			new RedirectAction($dispatcher->url(
				$request, ROUTE_PAGE,
				null, 'management', 'settings', 'publication',
				array('uid' => uniqid()), // Force reload
				'submissionStage' // Anchor for tab
			)),
			__('plugins.generic.referenceLinking.settings.form.submissionSettings'),
			null
		);
		$templateMgr->assign('submissionSettingsLinkAction', $submissionSettingsLinkAction);
		// Crossref export/registration plugin action link
		import('lib.pkp.classes.linkAction.request.RedirectAction');
		$crossrefSettingsLinkAction = new LinkAction(
			'settings',
			new RedirectAction($dispatcher->url(
				$request, ROUTE_PAGE,
				null, 'management', 'importexport',
				array('plugin', 'CrossRefExportPlugin')
			)),
			__('plugins.generic.referenceLinking.settings.form.crossrefSettings'),
			null
		);
		$templateMgr->assign('crossrefSettingsLinkAction', $crossrefSettingsLinkAction);
		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute($object = null) {
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
		return in_array($settingName, array('automaticRegistration', 'testMode'));
	}

}

?>
