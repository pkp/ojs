<?php

/**
 * @file controllers/tab/settings/reviewStage/form/OJSReviewStageForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewStageForm
 * @ingroup controllers_tab_settings_reviewStage_form
 *
 * @brief Form to edit review stage settings.
 */

import('lib.pkp.controllers.tab.settings.reviewStage.form.ReviewStageForm');

class OJSReviewStageForm extends ReviewStageForm {

	/**
	 * Constructor.
	 */
	function OJSReviewStageForm($wizardMode = false) {
		parent::ReviewStageForm(
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
