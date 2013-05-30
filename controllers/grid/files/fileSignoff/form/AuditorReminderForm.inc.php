<?php

/**
 * @file controllers/grid/users/reviewer/form/AuditorReminderForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuditorReminderForm
 * @ingroup controllers_grid_files_fileSignoff_form
 *
 * @brief Form for sending a singoff reminder to an auditor.
 */

import('lib.pkp.controllers.grid.files.fileSignoff.form.PKPAuditorReminderForm');

class AuditorReminderForm extends PKPAuditorReminderForm {

	/** The galley id, if any */
	var $_galleyId;

	/**
	 * Constructor.
	 */
	function AuditorReminderForm(&$signoff, $submissionId, $stageId, $galleyId = null) {
		parent::PKPAuditorReminderForm($signoff, $submissionId, $stageId);
		$this->_galleyId = $galleyId;

		// Validation checks for this form
		$this->addCheck(new FormValidatorPost($this));
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the galley id.
	 * @return int
	 */
	function getGalleyId() {
		return $this->_galleyId;
	}


	//
	// Overridden template methods
	//
	/**
	 * Initialize form data from the associated author.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function initData($args, $request) {
		parent::initData($args, $request);
		$this->setData('galleyId', $this->getGalleyId());
	}

	/**
	 * Return a context-specific instance of the mail template.
	 * @return ArticleMailTemplate
	 */
	function _getMailTemplate($submission) {
		import('classes.mail.ArticleMailTemplate');
		$email = new ArticleMailTemplate($submission, 'REVIEW_REMIND', null, null, null, false);
		return $email;
	}
}

?>
