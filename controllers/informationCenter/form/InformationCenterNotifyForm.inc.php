<?php

/**
 * @file controllers/informationCenter/form/InformationCenterNotifyForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InformationCenterNotifyForm
 * @ingroup informationCenter_form
 *
 * @brief Form to notify a user regarding a file
 */

import('classes.mail.ArticleMailTemplate');
import('lib.pkp.controllers.informationCenter.form.PKPInformationCenterNotifyForm');

class InformationCenterNotifyForm extends PKPInformationCenterNotifyForm {

	/**
	 * Constructor.
	 */
	function InformationCenterNotifyForm($itemId, $itemType) {
		parent::PKPInformationCenterNotifyForm($itemId, $itemType);
	}

	/**
	 * Return app-specific stage templates.
	 * @return array
	 */
	protected function _getStageTemplates() {

		return array(
			WORKFLOW_STAGE_ID_SUBMISSION => array(),
			WORKFLOW_STAGE_ID_EXTERNAL_REVIEW => array('EDITOR_ASSIGN'),
			WORKFLOW_STAGE_ID_EDITING => array('COPYEDIT_REQUEST'),
			WORKFLOW_STAGE_ID_PRODUCTION => array('LAYOUT_REQUEST', 'LAYOUT_COMPLETE', 'INDEX_REQUEST', 'INDEX_COMPLETE', 'EDITOR_ASSIGN')
		);
	}

	/**
	 * return app-specific mail template.
	 * @param Submission $submission
	 * @param String $templateKey
	 * @param boolean $includeSignature
	 * @return array
	 */
	protected function _getMailTemplate($article, $templateKey, $includeSignature = true) {
		if ($includeSignature)
			return new ArticleMailTemplate($article, $templateKey);
		else
			return new ArticleMailTemplate($article, $templateKey, null, null, null, false);
	}
}

?>
