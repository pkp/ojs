<?php

/**
 * @file plugins/generic/objectsForReview/classes/tasks/ObjectsForReviewReminder.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ObjectsForReviewReminder
 * @ingroup plugins_generic_objectsForReview_tasks
 *
 * @brief Class to perform automated reminders for object reviewers.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');

class ObjectsForReviewReminder extends ScheduledTask {
	/**
	 * Constructor.
	 */
	function ObjectsForReviewReminder() {
		parent::ScheduledTask();
	}

	/**
	 * @see ScheduledTask::getName()
	 */
	function getName() {
		return __('plugins.generic.objectsForReview.reminderTask.name');
	}

	/**
	 * Send email to object for review author
	 * @param $ofrAssignment ObjectForReviewAssignment
	 * @param $journal Journal
	 * @param $emailKey string
	 */
	function sendReminder($ofrAssignment, $journal, $emailKey) {
		$journalId = $journal->getId();

		$author =& $ofrAssignment->getUser();
		$objectForReview =& $ofrAssignment->getObjectForReview();
		$editor =& $objectForReview->getEditor();

		$paramArray = array(
			'authorName' => strip_tags($author->getFullName()),
			'objectForReviewTitle' => '"' . strip_tags($objectForReview->getTitle()) . '"',
			'objectForReviewDueDate' => date('l, F j, Y', strtotime($ofrAssignment->getDateDue())),
			'submissionUrl' => Request::url($journal->getPath(), 'author', 'submit'),
			'editorialContactSignature' => strip_tags($editor->getContactSignature())
		);

		import('classes.mail.MailTemplate');
		$mail = new MailTemplate($emailKey);
		$mail->setFrom($editor->getEmail(), $editor->getFullName());
		$mail->addRecipient($author->getEmail(), $author->getFullName());
		$mail->setSubject($mail->getSubject($journal->getPrimaryLocale()));
		$mail->setBody($mail->getBody($journal->getPrimaryLocale()));
		$mail->assignParams($paramArray);
		$mail->send();

		$ofrAssignment->setDateReminded(Core::getCurrentDate());
		$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
		$ofrAssignmentDao->updateObject($ofrAssignment);
	}

	/**
	 * @see ScheduledTask::executeActions()
	 */
	function executeActions() {
		$ofrPlugin =& PluginRegistry::getPlugin('generic', 'objectsforreviewplugin');
		if ($ofrPlugin) {
			$ofrPluginName = $ofrPlugin->getName();
			// Get all journals
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journals =& $journalDao->getJournals(true);
			// Register the plugin DAOs and get the others
			$ofrPlugin->registerDAOs();
			$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
			$ofrAssignmentDao =& DAORegistry::getDAO('ObjectForReviewAssignmentDAO');
			// For all journals
			while ($journal =& $journals->next()) {
				$journalId = $journal->getId();
				// If the plugin is enabled
				$pluginEnabled = $pluginSettingsDao->getSetting($journalId, $ofrPluginName, 'enabled');
				if ($pluginEnabled) {
					// Get plugin reminder settings
					$enableDueReminderBefore = $pluginSettingsDao->getSetting($journalId, $ofrPluginName, 'enableDueReminderBefore');
					$enableDueReminderAfter = $pluginSettingsDao->getSetting($journalId, $ofrPluginName, 'enableDueReminderAfter');
					$beforeDays = $pluginSettingsDao->getSetting($journalId, $ofrPluginName, 'numDaysBeforeDueReminder');
					$afterDays = $pluginSettingsDao->getSetting($journalId, $ofrPluginName, 'numDaysAfterDueReminder');
					// If a reminder is set
					if (($enableDueReminderBefore && $beforeDays > 0) || ($enableDueReminderAfter && $afterDays > 0)) {
						// Retrieve all incomplete object for review assignments
						$incompleteAssignments =& $ofrAssignmentDao->getIncompleteAssignmentsByContextId($journalId);
						foreach ($incompleteAssignments as $incompleteAssignment) {
							if ($incompleteAssignment->getDateDue() != null) {
								$dueDate = strtotime($incompleteAssignment->getDateDue());
								// Remind before:
								// If there hasn't been any such reminder, this option is set and due date is in the future
								if ($incompleteAssignment->getDateRemindedBefore() == null && $enableDueReminderBefore == 1 && time() < $dueDate) {
									$nowToDueDate = $dueDate - time();
									if ($nowToDueDate < 60 * 60 * 24 * $beforeDays) {
										$this->sendReminder($incompleteAssignment, $journal, 'OFR_REVIEW_REMINDER');
									}
								}
								// Remind after:
								// If there hasn't been any such reminder, this option is set and due date is in the past
								if ($incompleteAssignment->getDateRemindedAfter() == null && $enableDueReminderAfter == 1 && time() > $dueDate) {
									$dueDateToNow = time() - $dueDate;
									if ($dueDateToNow > 60 * 60 * 24 * $afterDays) {
										$this->sendReminder($incompleteAssignment, $journal, 'OFR_REVIEW_REMINDER_LATE');
									}
								}
							}
						}
					}
				}
				unset($journal);
			}
			return true;
		} else {
			return false;
		}
	}

}

?>
