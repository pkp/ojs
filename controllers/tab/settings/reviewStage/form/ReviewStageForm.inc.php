<?php

/**
 * @file controllers/tab/settings/reviewStage/form/ReviewStageForm.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewStageForm
 * @ingroup controllers_tab_settings_reviewStage_form
 *
 * @brief Form to edit review stage settings.
 */

import('lib.pkp.controllers.tab.settings.reviewStage.form.PKPReviewStageForm');

class ReviewStageForm extends PKPReviewStageForm {

	/**
	 * Constructor.
	 */
	function ReviewStageForm($wizardMode = false) {
		parent::PKPReviewStageForm(
			$wizardMode,
			array(
				'restrictReviewerFileAccess' => 'bool',
				'reviewerAccessKeysEnabled' => 'bool',
				'mailSubmissionsToReviewers' => 'bool',
			),
			'controllers/tab/settings/reviewStage/form/ojsReviewStageForm.tpl'
		);
	}
}

?>
