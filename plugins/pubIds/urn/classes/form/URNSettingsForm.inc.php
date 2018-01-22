<?php

/**
 * @file plugins/pubIds/urn/classes/form/URNSettingsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class URNSettingsForm
 * @ingroup plugins_pubIds_urn
 *
 * @brief Form for journal managers to setup URN plugin
 */


import('lib.pkp.classes.form.Form');

class URNSettingsForm extends Form {

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

	/** @var URNPubIdPlugin */
	var $_plugin;

	/**
	 * Get the plugin.
	 * @return URNPubIdPlugin
	 */
	function _getPlugin() {
		return $this->_plugin;
	}

	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin URNPubIdPlugin
	 * @param $contextId integer
	 */
	function __construct($plugin, $contextId) {
		$this->_contextId = $contextId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplatePath() . 'settingsForm.tpl');

		$form = $this;
		$this->addCheck(new FormValidatorCustom($this, 'urnObjects', 'required', 'plugins.pubIds.urn.manager.settings.urnObjectsRequired', function($enableIssueURN) use ($form) {
			return $form->getData('enableIssueURN') || $form->getData('enableSubmissionURN') || $form->getData('enableRepresentationURN');
		}));
		$this->addCheck(new FormValidatorRegExp($this, 'urnPrefix', 'required', 'plugins.pubIds.urn.manager.settings.form.urnPrefixPattern', '/^urn:[a-zA-Z0-9-]*:.*/'));
		$this->addCheck(new FormValidatorCustom($this, 'urnIssueSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnIssueSuffixPatternRequired', function($urnIssueSuffixPattern) use ($form) {
			if ($form->getData('urnSuffix') == 'pattern' && $form->getData('enableIssueURN')) return $urnIssueSuffixPattern != '';
			return true;
		}));
		$this->addCheck(new FormValidatorCustom($this, 'urnSubmissionSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnSubmissionSuffixPatternRequired', function($urnSubmissionSuffixPattern) use ($form) {
			if ($form->getData('urnSuffix') == 'pattern' && $form->getData('enableSubmissionURN')) return $urnSubmissionSuffixPattern != '';
			return true;
		}));
		$this->addCheck(new FormValidatorCustom($this, 'urnRepresentationSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnRepresentationSuffixPatternRequired', function($urnRepresentationSuffixPattern) use ($form) {
			if ($form->getData('urnSuffix') == 'pattern' && $form->getData('enableRepresentationURN')) return $urnRepresentationSuffixPattern != '';
			return true;
		}));
		$this->addCheck(new FormValidatorUrl($this, 'urnResolver', 'required', 'plugins.pubIds.urn.manager.settings.form.urnResolverRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));

		// for URN reset requests
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
		$application = PKPApplication::getApplication();
		$request = $application->getRequest();
		$this->setData('clearPubIdsLinkAction', new LinkAction(
			'reassignURNs',
			new RemoteActionConfirmationModal(
				$request->getSession(),
				__('plugins.pubIds.urn.manager.settings.urnReassign.confirm'),
				__('common.delete'),
				$request->url(null, null, 'manage', null, array('verb' => 'clearPubIds', 'plugin' => $plugin->getName(), 'category' => 'pubIds')),
				'modal_delete'
			),
			__('plugins.pubIds.urn.manager.settings.urnReassign'),
			'delete'
		));
		$this->setData('pluginName', $plugin->getName());
	}


	//
	// Implement template methods from Form
	//
	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$urnNamespaces = array(
			'' => '',
			'urn:nbn:de' => 'urn:nbn:de',
			'urn:nbn:at' => 'urn:nbn:at',
			'urn:nbn:ch' => 'urn:nbn:ch',
			'urn:nbn' => 'urn:nbn',
			'urn' => 'urn'
		);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('urnNamespaces', $urnNamespaces);
		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::initData()
	 */
	function initData() {
		$contextId = $this->_getContextId();
		$plugin = $this->_getPlugin();
		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$this->setData($fieldName, $plugin->getSetting($contextId, $fieldName));
		}
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->_getFormFields()));
	}

	/**
	 * @copydoc Form::validate()
	 */
	function execute() {
		$contextId = $this->_getContextId();
		$plugin = $this->_getPlugin();
		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$plugin->updateSetting($contextId, $fieldName, $this->getData($fieldName), $fieldType);
		}
	}

	//
	// Private helper methods
	//
	function _getFormFields() {
		return array(
			'enableIssueURN' => 'bool',
			'enableSubmissionURN' => 'bool',
			'enableRepresentationURN' => 'bool',
			'urnPrefix' => 'string',
			'urnSuffix' => 'string',
			'urnIssueSuffixPattern' => 'string',
			'urnSubmissionSuffixPattern' => 'string',
			'urnRepresentationSuffixPattern' => 'string',
			'urnCheckNo' => 'bool',
			'urnNamespace' => 'string',
			'urnResolver' => 'string',
		);
	}
}

?>
