<?php

/**
 * JournalSetupStep2Form.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 *
 * Form for Step 2 of journal setup.
 *
 * $Id$
 */

import("manager.form.setup.JournalSetupForm");

class JournalSetupStep2Form extends JournalSetupForm {
	
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
				'reviewPolicy' => 'string',
				'mailSubmissionsToReviewers' => 'int',
				'reviewGuidelines' => 'string',
				'authorSelectsEditor' => 'int',
				'privacyStatement' => 'string',
				'openAccessPolicy' => 'string',
				'emailSignature' => 'string',
				'disableUserReg' => 'bool',
				'allowRegReader' => 'bool',
				'allowRegAuthor' => 'bool',
				'allowRegReviewer' => 'bool',
				'restrictSiteAccess' => 'bool',
				'restrictArticleAccess' => 'bool',
				'articleEventLog' => 'bool',
				'articleEmailLog' => 'bool',
				'customAboutItems' => 'object'
			)
		);
		
		// No validation checks for this form		
	}

	function display() {
		$templateMgr = &TemplateManager::getManager();
		if (Config::getVar('general', 'scheduled_tasks'))
			$templateMgr->assign('scheduledTasksEnabled', true);
		parent::display();
	}
}

?>
