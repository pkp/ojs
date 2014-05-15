<?php

/**
 * @file plugins/generic/objectsForReview/classes/form/ObjectForReviewSettingsForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectsForReviewSettingsForm
 * @ingroup plugins_generic_objectsForReview
 *
 * @brief Form for editors to modify objects for review plugin settings
 */

import('lib.pkp.classes.form.Form');

class ObjectsForReviewSettingsForm extends Form {
	/** @var object */
	var $plugin;

	/** @var int */
	var $journalId;

	/** @var array Keys are valid review due weeks values */
	var $validDueWeeks;

	/** @var array Keys are valid email reminder days values */
	var $validNumDays;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function ObjectsForReviewSettingsForm(&$plugin, $journalId) {
		$this->plugin =& $plugin;
		$this->journalId = (int) $journalId;

		$validModes = array(
			OFR_MODE_FULL,
			OFR_MODE_METADATA
		);

		$this->validDueWeeks = range(0,50);
		$this->validNumDays = range(0,30);

		parent::Form($plugin->getTemplatePath() . 'editor' . '/' . 'settingsForm.tpl');

		// Management mode provided and valid
		$this->addCheck(new FormValidator($this, 'mode', 'required', 'plugins.generic.objectsForReview.settings.modeRequired'));
		$this->addCheck(new FormValidatorInSet($this, 'mode', 'required', 'plugins.generic.objectsForReview.settings.modeValid', $validModes));
		// Check if due weeks are valid
		$this->addCheck(new FormValidatorInSet($this, 'dueWeeks', 'optional', 'plugins.generic.objectsForReview.settings.dueWeeksValid', array_keys($this->validDueWeeks)));
		// If provided, check if the reminder days before and after are valid
		$this->addCheck(new FormValidatorInSet($this, 'numDaysBeforeDueReminder', 'optional', 'plugins.generic.objectsForReview.settings.numDaysReminderValid', array_keys($this->validNumDays)));
		$this->addCheck(new FormValidatorInSet($this, 'numDaysAfterDueReminder', 'optional', 'plugins.generic.objectsForReview.settings.numDaysReminderValid', array_keys($this->validNumDays)));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display($request) {
		$templateMgr =& TemplateManager::getManager($request);
		if (Config::getVar('general', 'scheduled_tasks')) {
			$templateMgr->assign('scheduledTasksEnabled', true);
		}
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$templateMgr->assign('counts', $ofrAssignmentDao->getStatusCounts($this->journalId));
		$templateMgr->assign('validDueWeeks', $this->validDueWeeks);
		$templateMgr->assign('validNumDays', $this->validNumDays);
		parent::display($request);
	}

	/**
	 * Get the names of the fields for which localized settings are used
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('additionalInformation');
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;
		$this->_data = array(
			'mode' => $plugin->getSetting($journalId, 'mode'),
			'coverPageIssue' => $plugin->getSetting($journalId, 'coverPageIssue'),
			'coverPageAbstract' => $plugin->getSetting($journalId, 'coverPageAbstract'),
			'dueWeeks' => $plugin->getSetting($journalId, 'dueWeeks'),
			'enableDueReminderBefore' => $plugin->getSetting($journalId, 'enableDueReminderBefore'),
			'numDaysBeforeDueReminder' => $plugin->getSetting($journalId, 'numDaysBeforeDueReminder'),
			'enableDueReminderAfter' => $plugin->getSetting($journalId, 'enableDueReminderAfter'),
			'numDaysAfterDueReminder' => $plugin->getSetting($journalId, 'numDaysAfterDueReminder'),
			'additionalInformation' => $plugin->getSetting($journalId, 'additionalInformation')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'mode',
				'coverPageIssue',
				'coverPageAbstract',
				'dueWeeks',
				'enableDueReminderBefore',
				'numDaysBeforeDueReminder',
				'enableDueReminderAfter',
				'numDaysAfterDueReminder',
				'additionalInformation',
			)
		);
		// If full management mode, due weeks provided and valid
		if ($this->_data['mode'] == OFR_MODE_FULL) {
			$this->addCheck(new FormValidator($this, 'dueWeeks', 'required', 'plugins.generic.objectsForReview.settings.dueWeeksRequired'));
			$this->addCheck(new FormValidatorInSet($this, 'dueWeeks', 'required', 'plugins.generic.objectsForReview.settings.dueWeeksValid', array_keys($this->validDueWeeks)));
		}
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;
		$plugin->updateSetting($journalId, 'mode', $this->getData('mode'), 'int');
		$plugin->updateSetting($journalId, 'coverPageIssue', $this->getData('coverPageIssue'), 'bool');
		$plugin->updateSetting($journalId, 'coverPageAbstract', $this->getData('coverPageAbstract'), 'bool');
		$plugin->updateSetting($journalId, 'dueWeeks', $this->getData('dueWeeks'), 'int');
		$plugin->updateSetting($journalId, 'enableDueReminderBefore', $this->getData('enableDueReminderBefore'), 'bool');
		$plugin->updateSetting($journalId, 'numDaysBeforeDueReminder', $this->getData('numDaysBeforeDueReminder'), 'int');
		$plugin->updateSetting($journalId, 'enableDueReminderAfter', $this->getData('enableDueReminderAfter'), 'bool');
		$plugin->updateSetting($journalId, 'numDaysAfterDueReminder', $this->getData('numDaysAfterDueReminder'), 'int');
		$plugin->updateSetting($journalId, 'additionalInformation', $this->getData('additionalInformation'), 'object'); // Localized
	}

}

?>
