<?php

/**
 * @file plugins/pubIds/doi/DOISettingsForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
	var $_journalId;

	/**
	 * Get the journal ID.
	 * @return integer
	 */
	function _getJournalId() {
		return $this->_journalId;
	}

	/** @var DOIPubIdPlugin */
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
	 * @param $plugin DOIPubIdPlugin
	 * @param $journalId integer
	 */
	function DOISettingsForm(&$plugin, $journalId) {
		$this->_journalId = $journalId;
		$this->_plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidatorCustom($this, 'doiObjects', 'required', 'plugins.pubIds.doi.manager.settings.doiObjectsRequired', create_function('$enableIssueDoi,$form', 'return $form->getData(\'enableIssueDoi\') || $form->getData(\'enableArticleDoi\') || $form->getData(\'enableGalleyDoi\') || $form->getData(\'enableSuppFileDoi\');'), array(&$this)));
		$this->addCheck(new FormValidatorRegExp($this, 'doiPrefix', 'required', 'plugins.pubIds.doi.manager.settings.doiPrefixPattern', '/^10\.[0-9]{4,7}$/'));
		$this->addCheck(new FormValidatorCustom($this, 'doiIssueSuffixPattern', 'required', 'plugins.pubIds.doi.manager.settings.doiIssueSuffixPatternRequired', create_function('$doiIssueSuffixPattern,$form', 'if ($form->getData(\'doiSuffix\') == \'pattern\' && $form->getData(\'enableIssueDoi\')) return $doiIssueSuffixPattern != \'\';return true;'), array(&$this)));
		$this->addCheck(new FormValidatorCustom($this, 'doiArticleSuffixPattern', 'required', 'plugins.pubIds.doi.manager.settings.doiArticleSuffixPatternRequired', create_function('$doiArticleSuffixPattern,$form', 'if ($form->getData(\'doiSuffix\') == \'pattern\' && $form->getData(\'enableArticleDoi\')) return $doiArticleSuffixPattern != \'\';return true;'), array(&$this)));
		$this->addCheck(new FormValidatorCustom($this, 'doiGalleySuffixPattern', 'required', 'plugins.pubIds.doi.manager.settings.doiGalleySuffixPatternRequired', create_function('$doiGalleySuffixPattern,$form', 'if ($form->getData(\'doiSuffix\') == \'pattern\' && $form->getData(\'enableGalleyDoi\')) return $doiGalleySuffixPattern != \'\';return true;'), array(&$this)));
		$this->addCheck(new FormValidatorCustom($this, 'doiSuppFileSuffixPattern', 'required', 'plugins.pubIds.doi.manager.settings.doiSuppFileSuffixPatternRequired', create_function('$doiSuppFileSuffixPattern,$form', 'if ($form->getData(\'doiSuffix\') == \'pattern\' && $form->getData(\'enableSuppFileDoi\')) return $doiSuppFileSuffixPattern != \'\';return true;'), array(&$this)));
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
