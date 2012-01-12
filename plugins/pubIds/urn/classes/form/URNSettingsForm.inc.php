<?php

/**
 * @file plugins/pubIds/urn/URNSettingsForm.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class URNSettingsForm
 * @ingroup plugins_pubIds_urn
 *
 * @brief Form for journal managers to setup URN plugin
 */


import('lib.pkp.classes.form.Form');

class URNSettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function URNSettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidatorCustom($this, 'enableIssueURN', 'required', 'plugins.pubIds.urn.manager.settings.form.journalContentRequired', create_function('$enableIssueURN,$form', 'return $form->getData(\'enableIssueURN\') || $form->getData(\'enableArticleURN\') || $form->getData(\'enableGalleyURN\') || $form->getData(\'enableSuppFileURN\');'), array(&$this)));
		$this->addCheck(new FormValidator($this, 'urnPrefix', 'required', 'plugins.pubIds.urn.manager.settings.form.urnPrefixRequired'));
		$this->addCheck(new FormValidatorRegExp($this, 'urnPrefix', 'optional', 'plugins.pubIds.urn.manager.settings.form.urnPrefixPattern', '/^urn:[a-zA-Z0-9-]*:.*/'));
		$this->addCheck(new FormValidatorCustom($this, 'urnIssueSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnIssueSuffixPatternRequired', create_function('$urnIssueSuffixPattern,$form', 'if ($form->getData(\'urnSuffix\') == \'pattern\' && $form->getData(\'enableIssueURN\')) return $urnIssueSuffixPattern != \'\';return true;'), array(&$this)));
		$this->addCheck(new FormValidatorCustom($this, 'urnArticleSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnArticleSuffixPatternRequired', create_function('$urnArticleSuffixPattern,$form', 'if ($form->getData(\'urnSuffix\') == \'pattern\' && $form->getData(\'enableArticleURN\')) return $urnArticleSuffixPattern != \'\';return true;'), array(&$this)));
		$this->addCheck(new FormValidatorCustom($this, 'urnGalleySuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnGalleySuffixPatternRequired', create_function('$urnGalleySuffixPattern,$form', 'if ($form->getData(\'urnSuffix\') == \'pattern\' && $form->getData(\'enableGalleyURN\')) return $urnGalleySuffixPattern != \'\';return true;'), array(&$this)));
		$this->addCheck(new FormValidatorCustom($this, 'urnSuppFileSuffixPattern', 'required', 'plugins.pubIds.urn.manager.settings.form.urnSuppFileSuffixPatternRequired', create_function('$urnSuppFileSuffixPattern,$form', 'if ($form->getData(\'urnSuffix\') == \'pattern\' && $form->getData(\'enableSuppFileURN\')) return $urnSuppFileSuffixPattern != \'\';return true;'), array(&$this)));
		$this->addCheck(new FormValidator($this, 'namespace', 'required', 'plugins.pubIds.urn.manager.settings.form.namespaceRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$namespaces = array(
			'urn:nbn:de' => 'urn:nbn:de',
			'urn:nbn:at' => 'urn:nbn:at',
			'urn:nbn:ch' => 'urn:nbn:ch',
			'urn:nbn' => 'urn:nbn',
			'urn' => 'urn'
		);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('namespaces', $namespaces);
		parent::display();
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;


		$this->_data = array(
			'enableIssueURN' => $plugin->getSetting($journalId, 'enableIssueURN'),
			'enableArticleURN' => $plugin->getSetting($journalId, 'enableArticleURN'),
			'enableGalleyURN' => $plugin->getSetting($journalId, 'enableGalleyURN'),
			'enableSuppFileURN' => $plugin->getSetting($journalId, 'enableSuppFileURN'),
			'urnPrefix' => $plugin->getSetting($journalId, 'urnPrefix'),
			'urnSuffix' => $plugin->getSetting($journalId, 'urnSuffix'),
			'urnIssueSuffixPattern' => $plugin->getSetting($journalId, 'urnIssueSuffixPattern'),
			'urnArticleSuffixPattern' => $plugin->getSetting($journalId, 'urnArticleSuffixPattern'),
			'urnGalleySuffixPattern' => $plugin->getSetting($journalId, 'urnGalleySuffixPattern'),
			'urnSuppFileSuffixPattern' => $plugin->getSetting($journalId, 'urnSuppFileSuffixPattern'),
			'checkNo' => $plugin->getSetting($journalId, 'checkNo'),
			'namespace' => $plugin->getSetting($journalId, 'namespace')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('enableIssueURN', 'enableArticleURN', 'enableGalleyURN', 'enableSuppFileURN', 'urnPrefix', 'urnSuffix', 'urnIssueSuffixPattern', 'urnArticleSuffixPattern', 'urnGalleySuffixPattern', 'urnSuppFileSuffixPattern', 'checkNo', 'namespace'));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'enableIssueURN', $this->getData('enableIssueURN'), 'bool');
		$plugin->updateSetting($journalId, 'enableArticleURN', $this->getData('enableArticleURN'), 'bool');
		$plugin->updateSetting($journalId, 'enableGalleyURN', $this->getData('enableGalleyURN'), 'bool');
		$plugin->updateSetting($journalId, 'enableSuppFileURN', $this->getData('enableSuppFileURN'), 'bool');
		$plugin->updateSetting($journalId, 'urnPrefix', $this->getData('urnPrefix'), 'string');
		$plugin->updateSetting($journalId, 'urnSuffix', $this->getData('urnSuffix'), 'string');
		$plugin->updateSetting($journalId, 'urnIssueSuffixPattern', $this->getData('urnIssueSuffixPattern'), 'string');
		$plugin->updateSetting($journalId, 'urnArticleSuffixPattern', $this->getData('urnArticleSuffixPattern'), 'string');
		$plugin->updateSetting($journalId, 'urnGalleySuffixPattern', $this->getData('urnGalleySuffixPattern'), 'string');
		$plugin->updateSetting($journalId, 'urnSuppFileSuffixPattern', $this->getData('urnSuppFileSuffixPattern'), 'string');
		$plugin->updateSetting($journalId, 'checkNo', $this->getData('checkNo'), 'bool');
		$plugin->updateSetting($journalId, 'namespace', $this->getData('namespace'), 'string');
	}
}

?>
