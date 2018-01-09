<?php

/**
 * @file plugins/generic/googleAnalytics/GoogleAnalyticsSettingsForm.inc.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GoogleAnalyticsSettingsForm
 * @ingroup plugins_generic_googleAnalytics
 *
 * @brief Form for journal managers to modify Google Analytics plugin settings
 */
define('GOOGLE_ANALYTICS_SITE_ENABLE', 1);
define('GOOGLE_ANALYTICS_SITE_DISABLE', -1);
define('GOOGLE_ANALYTICS_SITE_UNCHANGED', 0);

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
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * @see Form::display()
	 */
	function display($request = null, $template = null) {
		if (Validation::isSiteAdmin()) {
			$plugin =& $this->plugin;
			$templateMgr =& TemplateManager::getManager($request);
			$templateMgr->assign('siteAdmin', TRUE);
			if ($plugin->getSetting(CONTEXT_ID_NONE, 'enabled')) {
				$templateMgr->assign('siteEnabled', TRUE);
				$templateMgr->assign('siteTrackingCode', $plugin->getSetting(CONTEXT_ID_NONE, 'trackingCode'));
				$templateMgr->assign('siteGoogleAnalyticsSiteId', $plugin->getSetting(CONTEXT_ID_NONE, 'googleAnalyticsSiteId'));
			} else {
				$templateMgr->assign('siteEnabled', FALSE);
				$templateMgr->assign('siteTrackingCode', __('plugins.generic.googleAnalytics.manager.settings.disabled'));
				$templateMgr->assign('siteGoogleAnalyticsSiteId', __('plugins.generic.googleAnalytics.manager.settings.disabled'));
			}
		}
		parent::display($request, $template);
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
		if (Validation::isSiteAdmin()) {
			$this->_data['enableSite'] = GOOGLE_ANALYTICS_SITE_UNCHANGED;
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$vars = array('googleAnalyticsSiteId', 'trackingCode');
		if (Validation::isSiteAdmin()) {
			$vars[] = 'enableSite';
		}
		$this->readUserVars($vars);
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'googleAnalyticsSiteId', trim($this->getData('googleAnalyticsSiteId'), "\"\';"), 'string');

		$trackingCode = $this->getData('trackingCode');
		if (($trackingCode != "urchin") && ($trackingCode != "ga") && ($trackingCode != "analytics")) {
			$trackingCode = "urchin";
		}
		$plugin->updateSetting($journalId, 'trackingCode', $trackingCode, 'string');
		if (Validation::isSiteAdmin()) {
			// Enable this code on the site level
			if ($this->getData('enableSite')) {
				$plugin->updateSetting(CONTEXT_ID_NONE, 'enabled', $this->getData('enableSite') == GOOGLE_ANALYTICS_SITE_ENABLE ? TRUE : FALSE, 'bool');
				$plugin->updateSetting(CONTEXT_ID_NONE, 'trackingCode', $this->getData('enableSite') == GOOGLE_ANALYTICS_SITE_ENABLE ? $trackingCode : '', 'string');
				$plugin->updateSetting(CONTEXT_ID_NONE, 'googleAnalyticsSiteId', $this->getData('enableSite') == GOOGLE_ANALYTICS_SITE_ENABLE ? trim($this->getData('googleAnalyticsSiteId'), "\"\';") : '', 'string');
			}
		}
	}
}

?>
