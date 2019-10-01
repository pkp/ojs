<?php

/**
 * @file controllers/grid/users/stageParticipant/form/StageParticipantNotifyForm.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StageParticipantNotifyForm
 * @ingroup grid_users_stageParticipant_form
 *
 * @brief Form to notify a user regarding a file
 */

import('classes.mail.ArticleMailTemplate');
import('lib.pkp.controllers.grid.users.stageParticipant.form.PKPStageParticipantNotifyForm');

class StageParticipantNotifyForm extends PKPStageParticipantNotifyForm {

	/**
	 * Constructor.
	 */
	function __construct($itemId, $itemType, $stageId, $template = null) {
		parent::__construct($itemId, $itemType, $stageId, $template);
	}

	/**
	 * Return app-specific stage templates.
	 * @return array
	 */
	protected function _getStageTemplates() {
		return array(
			WORKFLOW_STAGE_ID_SUBMISSION => array('EDITOR_ASSIGN'),
			WORKFLOW_STAGE_ID_EXTERNAL_REVIEW => array('EDITOR_ASSIGN'),
			WORKFLOW_STAGE_ID_EDITING => array('COPYEDIT_REQUEST'),
			WORKFLOW_STAGE_ID_PRODUCTION => array('LAYOUT_REQUEST', 'LAYOUT_COMPLETE', 'INDEX_REQUEST', 'INDEX_COMPLETE', 'EDITOR_ASSIGN')
		);
	}

	/**
	 * return app-specific mail template.
	 * @param $submission Submission
	 * @param $templateKey string
	 * @param $includeSignature boolean optional
	 * @return array
	 */
	protected function _getMailTemplate($submission, $templateKey, $includeSignature = true) {
		if ($includeSignature)
			return new ArticleMailTemplate($submission, $templateKey);
		else
			return new ArticleMailTemplate($submission, $templateKey, null, null, null, false);
	}
}


