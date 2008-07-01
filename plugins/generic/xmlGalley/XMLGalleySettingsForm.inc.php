<?php

/**
 * @file XMLGalleySettingsForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class XMLGalleySettingsForm
 * @ingroup plugins_generic_xmlGalley
 *
 * @brief Form for journal managers to modify Article XML Galley plugin settings
 */

// $Id$


import('form.Form');

class XMLGalleySettingsForm extends Form {
	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function XMLGalleySettingsForm(&$plugin, $journalId) {
		$templateMgr = &TemplateManager::getManager();

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->journalId = $journalId;
		$this->plugin = &$plugin;

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$templateMgr = &TemplateManager::getManager();

		// set form variables for available XSLT renderers
		$xsltPHP5 = ( version_compare(PHP_VERSION,'5','>=') && extension_loaded('xsl') && extension_loaded('dom') );
		$xsltPHP4 = ( version_compare(PHP_VERSION,'5','<') && extension_loaded('xslt') );

		// populate form variables with saved plugin settings
		$this->setData('xsltPHP5', $xsltPHP5);
		$this->setData('xsltPHP4', $xsltPHP4);

		if ( !Request::getUserVar('save') ) {
			$this->setData('XSLTrenderer', $plugin->getSetting($journalId, 'XSLTrenderer'));
			$this->setData('externalXSLT', $plugin->getSetting($journalId, 'externalXSLT'));
			$this->setData('XSLstylesheet', $plugin->getSetting($journalId, 'XSLstylesheet'));
			$this->setData('nlmPDF', $plugin->getSetting($journalId, 'nlmPDF'));
			$this->setData('externalFOP', $plugin->getSetting($journalId, 'externalFOP'));
		}
		$this->setData('customXSL', $plugin->getSetting($journalId, 'customXSL'));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('XSLTrenderer', 'XSLstylesheet', 'externalXSLT', 'customXSL', 'nlmPDF', 'externalFOP'));

		// ensure that external XSLT or XSL are not blank
		if ($this->getData('XSLTrenderer') == "external") {
			$this->addCheck(new FormValidator($this, 'externalXSLT', 'required', 'plugins.generic.xmlGalley.settings.externalXSLTRequired'));
		}

		// if PDF rendering is enabled, then check that an external FO processor is set
		if ($this->getData('nlmPDF') == "1") {
			$this->addCheck(new FormValidator($this, 'externalFOP', 'required', 'plugins.generic.xmlGalley.settings.xslFOPRequired'));
		}

		// if the custom stylesheet button is enabled, then check that an XSL is uploaded
		if ($this->getData('XSLstylesheet') == "custom") {
			$this->addCheck(new FormValidator($this, 'customXSL', 'required', 'plugins.generic.xmlGalley.settings.customXSLRequired'));
		}

	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin = &$this->plugin;
		$journalId = $this->journalId;

		// get existing settings to see if any are changing that will affect the cache
		$flushCache = false;
 		foreach ($this->_data as $setting => $value) {
			if ($plugin->getSetting($journalId, $setting) != $value) $flushCache = true;
 		}

		// if there are changes, flush the XSLT cache
		if ($flushCache == true) {
			$cacheManager =& CacheManager::getManager();
			$cacheManager->flush('xsltGalley');
		}

		$plugin->updateSetting($journalId, 'nlmPDF', $this->getData('nlmPDF'));
		$plugin->updateSetting($journalId, 'externalFOP', $this->getData('externalFOP'));
		$plugin->updateSetting($journalId, 'XSLTrenderer', $this->getData('XSLTrenderer'));
		$plugin->updateSetting($journalId, 'XSLstylesheet', $this->getData('XSLstylesheet'));
		$plugin->updateSetting($journalId, 'externalXSLT', $this->getData('externalXSLT'));
		$plugin->updateSetting($journalId, 'customXSL', $this->getData('customXSL'));
	}
}

?>
