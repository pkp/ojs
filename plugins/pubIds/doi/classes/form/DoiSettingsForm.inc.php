<?php

/**
 * @file plugins/pubIds/doi/DoiSettingsForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DoiSettingsForm
 * @ingroup plugins_pubIds_doi
 *
 * @brief Form for journal managers to setup DOI plugin
 */


import('lib.pkp.classes.form.Form');

class DoiSettingsForm extends Form {

	//
	// Private properties
	//
	/** @var integer */
	var $_journalId;

	/**
	 * Get the journal ID.
	 * @return integer
	 */
	function _getJournalId() {
		return $this->_journalId;
	}

	/** @var DoiPubIdPlugin */
	var $_plugin;

	/**
	 * Get the plugin.
	 * @return DoiPubIdPlugin
	 */
	function &_getPlugin() {
		return $this->_plugin;
	}


	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $plugin DoiPubIdPlugin
	 * @param $journalId integer
	 */
	function DoiSettingsForm(&$plugin, $journalId) {
		$this->_journalId = $journalId;
		$this->_plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidatorCustom($this, 'doiObjects', 'required', 'plugins.pubIds.doi.manager.settings.doiObjectsRequired', create_function('$enableIssueDoi,$form', 'return $form->getData(\'enableIssueDoi\') || $form->getData(\'enableArticleDoi\') || $form->getData(\'enableGalleyDoi\') || $form->getData(\'enableSuppFileDoi\');'), array(&$this)));
		$this->addCheck(new FormValidatorRegExp($this, 'doiPrefix', 'required', 'plugins.pubIds.doi.manager.settings.doiPrefixPattern', '/^10\.[0-9][0-9][0-9][0-9][0-9]?$/'));
		$this->addCheck(new FormValidatorPost($this));
	}


	//
	// Implement template methods from Form
	//
	/**
	 * @see Form::initData()
	 */
	function initData() {
		$journalId = $this->_getJournalId();
		$plugin =& $this->_getPlugin();
		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$this->setData($fieldName, $plugin->getSetting($journalId, $fieldName));
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
	function validate() {
		if ($this->getData('doiSuffix') == 'pattern') {
			// When individual DOI patterns are enabled then we have
			// to check for every object that we want to generate
			// DOIs for whether a pattern has really been entered.
			foreach(array('Issue', 'Article', 'Galley', 'SuppFile') as $objectType) {
				if ($this->getData("enable${objectType}Doi")) {
					$this->addCheck(
						new FormValidator(
							$this, "doi${objectType}SuffixPattern", 'required',
							// NB: We cannot use translation parameters here...
							// FormValidator won't let us. So we use one key per
							// object type.
							"plugins.pubIds.doi.manager.settings.doi${objectType}SuffixPatternRequired"
						)
					);
				}
			}
		}

		return parent::validate();
	}

	/**
	 * @see Form::execute()
	 */
	function execute() {
		$plugin =& $this->_getPlugin();
		$journalId = $this->_getJournalId();
		foreach($this->_getFormFields() as $fieldName => $fieldType) {
			$plugin->updateSetting($journalId, $fieldName, $this->getData($fieldName), $fieldType);
		}
	}


	//
	// Private helper methods
	//
	function _getFormFields() {
		return array(
			'enableIssueDoi' => 'bool',
			'enableArticleDoi' => 'bool',
			'enableGalleyDoi' => 'bool',
			'enableSuppFileDoi' => 'bool',
			'doiPrefix' => 'string',
			'doiSuffix' => 'string',
			'doiIssueSuffixPattern' => 'string',
			'doiArticleSuffixPattern' => 'string',
			'doiGalleySuffixPattern' => 'string',
			'doiSuppFileSuffixPattern' => 'string'
		);
	}
}

?>
