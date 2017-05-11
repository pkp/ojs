<?php
/**
 * @file controllers/grid/settings/reviewForms/form/ReviewFormElements.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormElements
 * @ingroup controllers_grid_settings_reviewForms_form
 *
 * @brief Form for manager to edit review form elements. 
 */

import('lib.pkp.classes.db.DBDataXMLParser');
import('lib.pkp.classes.form.Form');

class ReviewFormElements extends Form {

	/** The ID of the review form being edited */
	var $reviewFormId;

	/**
	 * Constructor.
	 * @param $template string
	 * @param $reviewFormId 
	 */
	function __construct($reviewFormId) {
		parent::__construct('manager/reviewForms/reviewFormElements.tpl');

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
	function initData($reviewForm = null) {
		if (isset($this->reviewFormId)) {
			// Get review form
			$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
			$reviewForm = $reviewFormDao->getById($this->reviewFormId, ASSOC_TYPE_JOURNAL, $this->contextId);

			/***
			$completeCounts = $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), true);
			$incompleteCounts = $reviewFormDao->getUseCounts(ASSOC_TYPE_JOURNAL, $journal->getId(), false);

			if (!isset($reviewForm) || $completeCounts[$reviewFormId] != 0 || $incompleteCounts[$reviewFormId] != 0) {
				Request::redirect(null, null, 'reviewForms');
			}
			***/

			// Get review form elements
			//$rangeInfo = $this->getRangeInfo('reviewFormElements'); 
			//FIXME getRange Info is in classes/handler/PKPHandler.inc.php (line 374) 
			$reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO');
			//$reviewFormElements = $reviewFormElementDao->getByReviewFormId($reviewFormId, $rangeInfo);
			$reviewFormElements = $reviewFormElementDao->getByReviewFormId($reviewFormId, null);

			// Get titles of unused review forms
			$unusedReviewFormTitles = $reviewFormDao->getTitlesByAssocId(ASSOC_TYPE_JOURNAL, $this->contextId, 0);

			// Set data
			$this->setData('reviewFormId', $reviewFormId);
			$this->setData('reviewFormElements', $reviewFormElements);
		}
	}
}

?>
