<?php

/**
 * @file plugins/generic/stopForumSpam/StopForumSpamSettingsForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StopForumSpamSettingsForm
 * @ingroup plugins_generic_stopForumSpam
 *
 * @brief Form for journal managers to modify the Stop Forum Spam plugin settings
 */


import('lib.pkp.classes.form.Form');

class StopForumSpamSettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function StopForumSpamSettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$this->_data = array(
			'checkIp' => $plugin->getSetting($journalId, 'checkIp'),
			'checkEmail' => $plugin->getSetting($journalId, 'checkEmail'),
			'checkUsername' => $plugin->getSetting($journalId, 'checkUsername'),
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('checkIp', 'checkEmail', 'checkUsername'));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;

		$checkIp = $this->getData('checkIp') ? true : false;
		$checkEmail = $this->getData('checkEmail') ? true : false;
		$checkUsername = $this->getData('checkUsername') ? true : false;

		$plugin->updateSetting($journalId, 'checkIp', $checkIp, 'bool');
		$plugin->updateSetting($journalId, 'checkEmail', $checkEmail, 'bool');
		$plugin->updateSetting($journalId, 'checkUsername', $checkUsername, 'bool');

	}
}

?>
