<?php

/**
 * @file plugins/generic/googleAnalytics/GoogleAnalyticsSettingsForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GoogleAnalyticsSettingsForm
 * @ingroup plugins_generic_googleAnalytics
 *
 * @brief Form for journal managers to modify Google Analytics plugin settings
 */


import('lib.pkp.classes.form.Form');

class GoogleAnalyticsSettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function GoogleAnalyticsSettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidator($this, 'googleAnalyticsSiteId', 'required', 'plugins.generic.googleAnalytics.manager.settings.googleAnalyticsSiteIdRequired'));

		$this->addCheck(new FormValidator($this, 'trackingCode', 'required', 'plugins.generic.googleAnalytics.manager.settings.trackingCodeRequired'));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$this->_data = array(
			'googleAnalyticsSiteId' => $plugin->getSetting($journalId, 'googleAnalyticsSiteId'),
			'trackingCode' => $plugin->getSetting($journalId, 'trackingCode')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('googleAnalyticsSiteId', 'trackingCode'));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'googleAnalyticsSiteId', trim($this->getData('googleAnalyticsSiteId'), "\"\';"), 'string');

		$trackingCode = $this->getData('trackingCode');
		if (($trackingCode != "urchin") && ($trackingCode != "ga")) {
			$trackingCode = "urchin";
		}
		$plugin->updateSetting($journalId, 'trackingCode', $trackingCode, 'string');
	}
}

?>
