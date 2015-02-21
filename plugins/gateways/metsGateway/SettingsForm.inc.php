<?php

/**
 * @file plugins/gateways/metsGateway/SettingsForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SettingsForm
 * @ingroup plugins_gateways_metsGateway
 *
 * @brief Form for METS gateway plugin settings
 */

import('lib.pkp.classes.form.Form');

class SettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function SettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		if ($plugin->getSetting($journalId, 'organization') == '') {
			$siteDao =& DAORegistry::getDAO('SiteDAO');
			$site = $siteDao->getSite();
			$organization = $site->getLocalizedTitle();
			$this->setData('organization', $organization);
		} else {
			$this->setData('organization', $plugin->getSetting($journalId, 'organization'));
		}

		$this->setData('contentWrapper', $plugin->getSetting($journalId, 'contentWrapper') ? $plugin->getSetting($journalId, 'contentWrapper') : 'FLocat');
		$this->setData('preservationLevel', $plugin->getSetting($journalId, 'preservationLevel') ? $plugin->getSetting($journalId, 'preservationLevel') : '1');
		$this->setData('exportSuppFiles', $plugin->getSetting($journalId, 'exportSuppFiles'));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('contentWrapper','organization','preservationLevel','exportSuppFiles'));
	}

	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'contentWrapper', $this->getData('contentWrapper'));
		$plugin->updateSetting($journalId, 'organization', $this->getData('organization'));
		$plugin->updateSetting($journalId, 'preservationLevel', $this->getData('preservationLevel'));
		$plugin->updateSetting($journalId, 'exportSuppFiles', $this->getData('exportSuppFiles'));
	}

}

?>
