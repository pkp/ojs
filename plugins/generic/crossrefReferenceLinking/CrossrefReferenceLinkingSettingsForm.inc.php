<?php

/**
 * @file plugins/generic/crossrefReferenceLinking/CrossrefReferenceLinkingSettingsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossrefReferenceLinkingSettingsForm
 * @ingroup plugins_generic_crossrefReferenceLinking
 *
 * @brief Form for journal managers to setup the reference linking plugin
 */


import('lib.pkp.classes.form.Form');

class CrossrefReferenceLinkingSettingsForm extends Form {

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

	/** @var CrossrefReferenceLinkingPlugin */
	var $_plugin;

	/**
	 * Get the plugin.
	 * @return CrossrefReferenceLinkingPlugin
	 */
	function _getPlugin() {
		return $this->_plugin;
	}


	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin CrossrefReferenceLinkingPlugin
	 * @param $contextId integer
	 */
	function __construct($plugin, $contextId) {
		$this->_contextId = $contextId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		// Add form validation checks.
		$this->addCheck(new FormValidator($this, 'username', 'required', 'plugins.generic.crossrefReferenceLinking.settings.form.usernameRequired'));
		$this->addCheck(new FormValidator($this, 'password', 'required', 'plugins.generic.crossrefReferenceLinking.settings.form.passwordRequired'));
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
		$contextId = $request->getContext()->getId();
		$dispatcher = $request->getDispatcher();
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $plugin->getName());
		if (!$plugin->crossrefCredentials($contextId)) {
			// Crossref export/registration plugin action link
			import('lib.pkp.classes.linkAction.request.RedirectAction');
			$crossrefSettingsLinkAction = new LinkAction(
					'settings',
					new RedirectAction($dispatcher->url(
							$request, ROUTE_PAGE,
							null, 'management', 'importexport',
							array('plugin', 'CrossRefExportPlugin')
							)),
					__('plugins.generic.crossrefReferenceLinking.settings.form.crossrefSettings'),
					null
					);
			$templateMgr->assign('crossrefSettingsLinkAction', $crossrefSettingsLinkAction);
		}
		if (!$plugin->citationsEnabled($contextId)) {
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
				__('plugins.generic.crossrefReferenceLinking.settings.form.submissionSettings'),
				null
			);
			$templateMgr->assign('submissionSettingsLinkAction', $submissionSettingsLinkAction);
		}
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
