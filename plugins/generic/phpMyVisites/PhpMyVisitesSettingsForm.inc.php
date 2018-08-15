<?php

/**
 * @file plugins/generic/phpMyVisites/PhpMyVisitesSettingsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PhpMyVisitesSettingsForm
 * @ingroup plugins_generic_phpMyVisites
 *
 * @brief Form for journal managers to modify phpMyVisites plugin settings
 */

import('lib.pkp.classes.form.Form');

class PhpMyVisitesSettingsForm extends Form {

	/** @var int */
	var $journalId;

	/** @var object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function __construct(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

		$this->addCheck(new FormValidatorUrl($this, 'phpmvUrl', 'required', 'plugins.generic.phpmv.manager.settings.phpmvUrlRequired'));
		$this->addCheck(new FormValidator($this, 'phpmvSiteId', 'required', 'plugins.generic.phpmv.manager.settings.phpmvSiteIdRequired'));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$this->_data = array(
			'phpmvUrl' => $plugin->getSetting($journalId, 'phpmvUrl'),
			'phpmvSiteId' => $plugin->getSetting($journalId, 'phpmvSiteId')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('phpmvUrl', 'phpmvSiteId'));
	}

	/**
	 * Save settings. 
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;

		$plugin->updateSetting($journalId, 'phpmvUrl', rtrim($this->getData('phpmvUrl'), "/"), 'string');
		$plugin->updateSetting($journalId, 'phpmvSiteId', $this->getData('phpmvSiteId'), 'int');
	}
}


