<?php

/**
 * @file plugins/pubIds/doi/classes/form/DOISettingsForm.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOISettingsForm
 * @ingroup plugins_pubIds_doi
 *
 * @brief Form for journal managers to setup DOI plugin
 */


import('lib.pkp.classes.form.Form');

class DOISettingsForm extends Form {

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

	/** @var DOIPubIdPlugin */
	var $_plugin;

	/**
	 * Get the plugin.
	 * @return DOIPubIdPlugin
	 */
	function _getPlugin() {
		return $this->_plugin;
	}


	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin DOIPubIdPlugin
	 * @param $contextId integer
	 */
	function __construct($plugin, $contextId) {
		$this->_contextId = $contextId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		$form = $this;
		$this->addCheck(new FormValidatorRegExp($this, 'doiPrefix', 'required', 'plugins.pubIds.doi.manager.settings.doiPrefixPattern', '/^10\.[0-9]{4,7}$/'));
		$this->addCheck(new FormValidatorCustom($this, 'doiPublicationSuffixPattern', 'required', 'plugins.pubIds.doi.manager.settings.doiPublicationSuffixPatternRequired', function($doiPublicationSuffixPattern) use ($form) {
			if ($form->getData('doiSuffix') == 'pattern' && $form->getData('enablePublicationDoi')) return $doiPublicationSuffixPattern != '';
			return true;
		}));
		$this->addCheck(new FormValidatorCustom($this, 'doiRepresentationSuffixPattern', 'required', 'plugins.pubIds.doi.manager.settings.doiRepresentationSuffixPatternRequired', function($doiRepresentationSuffixPattern) use ($form) {
			if ($form->getData('doiSuffix') == 'pattern' && $form->getData('enableRepresentationDoi')) return $doiRepresentationSuffixPattern != '';
			return true;
		}));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));

		// for DOI reset requests
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
		$request = Application::get()->getRequest();
		$this->setData('clearPubIdsLinkAction', new LinkAction(
			'reassignDOIs',
			new RemoteActionConfirmationModal(
				$request->getSession(),
				__('plugins.pubIds.doi.manager.settings.doiReassign.confirm'),
				__('common.delete'),
				$request->url(null, null, 'manage', null, array('verb' => 'clearPubIds', 'plugin' => $plugin->getName(), 'category' => 'pubIds')),
				'modal_delete'
			),
			__('plugins.pubIds.doi.manager.settings.doiReassign'),
			'delete'
		));
		$this->setData('assignJournalWidePubIdsLinkAction', new LinkAction(
			'assignDOIs',
			new RemoteActionConfirmationModal(
				$request->getSession(),
				__('plugins.pubIds.doi.manager.settings.doiAssignJournalWide.confirm'),
				__('plugins.pubIds.doi.manager.settings.doiAssignJournalWide'),
				$request->url(null, null, 'manage', null, array('verb' => 'assignPubIds', 'plugin' => $plugin->getName(), 'category' => 'pubIds')),
				'modal_confirm'
			),
			__('plugins.pubIds.doi.manager.settings.doiAssignJournalWide'),
			'advance'
		));
		$this->setData('pluginName', $plugin->getName());
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
	 * Execute the form.
	 */
	function execute() {
		$plugin = $this->_getPlugin();
		$contextId = $this->_getContextId();
		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$plugin->updateSetting($contextId, $fieldName, $this->getData($fieldName), $fieldType);
		}
	}


	//
	// Private helper methods
	//
	function _getFormFields() {
		return array(
			'enablePublicationDoi' => 'bool',
			'enableRepresentationDoi' => 'bool',
			'doiPrefix' => 'string',
			'doiSuffix' => 'string',
			'doiPublicationSuffixPattern' => 'string',
			'doiRepresentationSuffixPattern' => 'string',
		);
	}
}


