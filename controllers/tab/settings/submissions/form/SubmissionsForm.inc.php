<?php

/**
 * @file controllers/tab/settings/submissions/form/SubmissionsForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSetupStep2Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 2 of journal setup.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class SubmissionsForm extends ContextSettingsForm {
	/**
	 * Constructor.
	 */
	function SubmissionsForm($wizardMode = false) {
		$settings = array(
			'authorSelectsEditor' => 'bool',
			'pubFreqPolicy' => 'string',
			'provideRefLinkInstructions' => 'bool',
			'copyrightNotice' => 'string',
			'copyrightNoticeAgree' => 'bool',
			'requireAuthorCompetingInterests' => 'bool',
			'requireReviewerCompetingInterests' => 'bool',
		);
		parent::ContextSettingsForm($settings, 'controllers/tab/settings/submissions/form/submissionsForm.tpl', $wizardMode);

		$this->addCheck(new FormValidatorEmail($this, 'envelopeSender', 'optional', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorEmail($this, 'copySubmissionAckAddress', 'optional', 'user.profile.form.emailRequired'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('copyrightNotice', 'pubFreqPolicy');
	}
}

?>
