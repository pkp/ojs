<?php
/**
 * @file controllers/grid/settings/reviewForms/form/PKPPreviewReviewForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PreviewReviewForm
 * @ingroup controllers_grid_settings_reviewForms_form
 *
 * @brief Form for manager to preview review form.
 */

import('lib.pkp.classes.db.DBDataXMLParser');
import('lib.pkp.classes.form.Form');

class PreviewReviewForm extends Form {

	/** The ID of the review form being edited */
	var $reviewFormId;

	/**
	 * Constructor.
	 * @param $template string
	 * @param $reviewFormId omit for a new review form
	 */
	function __construct($reviewFormId = null) {
		parent::__construct('manager/reviewForms/previewReviewForm.tpl');

		$this->reviewFormId = (int) $reviewFormId;

		// Validation checks for this form
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Display the form.
	 */
	function fetch($args, $request) {
		$json = new JSONMessage();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('reviewFormId', $this->reviewFormId);

		return parent::fetch($request);
	}

	/**
	 * Initialize form data from current settings.
	 * @param $reviewForm ReviewForm optional
	 */
	function initData($request) {
		if ($this->reviewFormId) {
			// Get review form
			$context = $request->getContext();
			$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
			$reviewForm = $reviewFormDao->getById($this->reviewFormId, Application::getContextAssocType(), $context->getId());

			// Get review form elements
			$reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO');
			$reviewFormElements = $reviewFormElementDao->getByReviewFormId($this->reviewFormId);
			$count = count($reviewFormElements);

			// Set data
			$this->setData('title', $reviewForm->getLocalizedTitle(null));
			$this->setData('description', $reviewForm->getLocalizedDescription(null));
			$this->setData('reviewFormElements', $reviewFormElements);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();
	}
}
?>
