<?php

/**
 * @file JournalSetupStep3Form.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 * @class JournalSetupStep3Form
 *
 * Form for Step 3 of journal setup.
 *
 * $Id$
 */

import("manager.form.setup.JournalSetupForm");

class JournalSetupStep3Form extends JournalSetupForm {
	/**
	 * Constructor.
	 */
	function JournalSetupStep3Form() {
		parent::JournalSetupForm(
			3,
			array(
				'authorGuidelines' => 'string',
				'submissionChecklist' => 'object',
				'copyrightNotice' => 'string',
				'includeCreativeCommons' => 'bool',
				'copyrightNoticeAgree' => 'bool',
				'requireAuthorCompetingInterests' => 'bool',
				'requireReviewerCompetingInterests' => 'bool',
				'competingInterestGuidelines' => 'string',
				'metaDiscipline' => 'bool',
				'metaDisciplineExamples' => 'string',
				'metaSubjectClass' => 'bool',
				'metaSubjectClassTitle' => 'string',
				'metaSubjectClassUrl' => 'string',
				'metaSubject' => 'bool',
				'metaSubjectExamples' => 'string',
				'metaCoverage' => 'bool',
				'metaCoverageGeoExamples' => 'string',
				'metaCoverageChronExamples' => 'string',
				'metaCoverageResearchSampleExamples' => 'string',
				'metaType' => 'bool',
				'metaTypeExamples' => 'string',
				'copySubmissionAckPrimaryContact' => 'bool',
				'copySubmissionAckSpecified' => 'bool',
				'copySubmissionAckAddress' => 'string'
			)
		);

		$this->addCheck(new FormValidatorEmail($this, 'copySubmissionAckAddress', 'optional', 'user.profile.form.emailRequired'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('authorGuidelines', 'submissionChecklist', 'copyrightNotice', 'metaDisciplineExamples', 'metaSubjectClassTitle', 'metaSubjectClassUrl', 'metaSubjectExamples', 'metaCoverageGeoExamples', 'metaCoverageChronExamples', 'metaCoverageResearchSampleExamples', 'metaTypeExamples', 'competingInterestGuidelines');
	}

	/**
	 * Display the form
	 */
	function display() {
		import('mail.MailTemplate');
		$mail = &new MailTemplate('SUBMISSION_ACK');
		if ($mail->isEnabled()) {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('submissionAckEnabled', true);
		}

		parent::display();
	}
}

?>
