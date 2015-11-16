<?php

/**
 * @file plugins/pubIds/urn/classes/form/URNSettingsForm.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
	 * @return DOIPubIdPlugin
	 */
	function &_getPlugin() {
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
	function URNSettingsForm(&$plugin, $contextId) {
		$this->_contextId = $contextId;
		$this->_plugin = $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidatorCustom($this, 'urnObjects', 'required', 'plugins.pubIds.urn.manager.settings.urnObjectsRequired', create_function('$enableIssueURN,$form', 'return $form->getData(\'enableIssueURN\') || $form->getData(\'enableArticleURN\') || $form->getData(\'enableArticleGalleyURN\');'), array($this)));
		$this->addCheck(new FormValidator($this, 'urnPrefix', 'required', 'plugins.pubIds.urn.manager.settings.form.urnPrefixRequired'));
		$this->addCheck(new FormValidatorRegExp($this, 'urnPrefix', 'optional', 'plugins.pubIds.urn.manager.settings.form.urnPrefixPattern', '/^urn:[a-zA-Z0-9-]*:.*/'));
		$this->addCheck(new FormValidatorCustom($this, 'urnIssueSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnIssueSuffixPatternRequired', create_function('$urnIssueSuffixPattern,$form', 'if ($form->getData(\'urnSuffix\') == \'pattern\' && $form->getData(\'enableIssueURN\')) return $urnIssueSuffixPattern != \'\';return true;'), array($this)));
		$this->addCheck(new FormValidatorCustom($this, 'urnArticleSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnArticleSuffixPatternRequired', create_function('$urnArticleSuffixPattern,$form', 'if ($form->getData(\'urnSuffix\') == \'pattern\' && $form->getData(\'enableArticleURN\')) return $urnArticleSuffixPattern != \'\';return true;'), array($this)));
		$this->addCheck(new FormValidatorCustom($this, 'urnArticleGalleySuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnGalleySuffixPatternRequired', create_function('$urnArticleGalleySuffixPattern,$form', 'if ($form->getData(\'urnSuffix\') == \'pattern\' && $form->getData(\'enableArticleGalleyURN\')) return $urnArticleGalleySuffixPattern != \'\';return true;'), array($this)));
		$this->addCheck(new FormValidatorUrl($this, 'urnResolver', 'required', 'plugins.pubIds.urn.manager.settings.form.urnResolverRequired'));
		$this->addCheck(new FormValidatorPost($this));

		// for URN reset requests
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
		$application = PKPApplication::getApplication();
		$request = $application->getRequest();
		$this->setData('clearPubIdsLinkAction', new LinkAction(
			'reassignURNs',
			new RemoteActionConfirmationModal(
				__('plugins.pubIds.urn.manager.settings.urnReassign.confirm'),
				__('common.delete'),
				$request->url(null, null, 'manage', null, array('verb' => 'settings', 'clearPubIds' => true, 'plugin' => $plugin->getName(), 'category' => 'pubIds')),
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
	 * @see Form::fetch()
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
	 * @see Form::initData()
	 */
	function initData() {
		$contextId = $this->_getContextId();
		$plugin = $this->_getPlugin();
		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$this->setData($fieldName, $plugin->getSetting($contextId, $fieldName));
		}
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array_keys($this->_getFormFields()));
	}

	/**
	 * @see Form::validate()
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
			'enableArticleURN' => 'bool',
			'enableArticleGalleyURN' => 'bool',
			'urnPrefix' => 'string',
			'urnSuffix' => 'string',
			'urnIssueSuffixPattern' => 'string',
			'urnArticleSuffixPattern' => 'string',
			'urnArticleGalleySuffixPattern' => 'string',
			'urnCheckNo' => 'bool',
			'urnNamespace' => 'string',
			'urnResolver' => 'string',
		);
	}
}

?>
