<?php

/**
 * @file JournalSetupStep2Form.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 * @class JournalSetupStep2Form
 *
 * Form for Step 2 of journal setup.
 *
 * $Id$
 */

import("manager.form.setup.JournalSetupForm");

class JournalSetupStep2Form extends JournalSetupForm {
	/**
	 * Constructor.
	 */
	function JournalSetupStep2Form() {
		parent::JournalSetupForm(
			2,
			array(
				'focusScopeDesc' => 'string',
				'numWeeksPerReview' => 'int',
				'remindForInvite' => 'int',
				'remindForSubmit' => 'int',
				'numDaysBeforeInviteReminder' => 'int',
				'numDaysBeforeSubmitReminder' => 'int',
				'rateReviewerOnQuality' => 'int',
				'restrictReviewerFileAccess' => 'int',
				'reviewerAccessKeysEnabled' => 'int',
				'reviewPolicy' => 'string',
				'mailSubmissionsToReviewers' => 'int',
				'reviewGuidelines' => 'string',
				'authorSelectsEditor' => 'int',
				'privacyStatement' => 'string',
				'customAboutItems' => 'object',
				'enableLockss' => 'bool',
				'lockssLicense' => 'string',
				'reviewerDatabaseLinks' => 'object',
				'notifyAllAuthorsOnDecision' => 'bool'
			)
		);
		
		$this->addCheck(new FormValidatorEmail($this, 'envelopeSender', 'optional', 'user.profile.form.emailRequired'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('focusScopeDesc', 'reviewPolicy', 'reviewGuidelines', 'privacyStatement', 'customAboutItems', 'lockssLicense');
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr = &TemplateManager::getManager();
		if (Config::getVar('general', 'scheduled_tasks'))
			$templateMgr->assign('scheduledTasksEnabled', true);

		parent::display();
	}
}

?>
