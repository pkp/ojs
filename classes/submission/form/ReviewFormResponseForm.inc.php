<?php

/**
 * @file ReviewFormResponseForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission.form
 * @class ReviewFormResponseForm
 *
 * Peer review form response form.
 *
 */

import('form.Form');

class ReviewFormResponseForm extends Form {

	/** @var int the ID of the review */
	var $reviewId;

	/** @var int the ID of the review form */
	var $reviewFormId;

	/**
	 * Constructor.
	 * @param $reviewId int
	 * @param $reviewFormId int
	 * @param $type string
	 */
	function ReviewFormResponseForm($reviewId, $reviewFormId) {
		parent::Form('submission/reviewForm/reviewFormResponse.tpl');

		$this->reviewId = $reviewId;
		$this->reviewFormId = $reviewFormId;

		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
		$requiredReviewFormElementIds = $reviewFormElementDao->getRequiredReviewFormElementIds($this->reviewFormId);

		$this->addCheck(new FormValidatorCustom($this, 'reviewFormResponses', 'required', 'reviewer.article.reviewFormResponse.form.responseRequired', create_function('$reviewFormResponses, $requiredReviewFormElementIds', 'foreach ($requiredReviewFormElementIds as $requiredReviewFormElementId) { if (!isset($reviewFormResponses[$requiredReviewFormElementId]) || $reviewFormResponses[$requiredReviewFormElementId] == \'\') return false; } return true;'), array($requiredReviewFormElementIds)));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal =& Request::getJournal();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($this->reviewFormId, $journal->getJournalId());
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
		$reviewFormElements =& $reviewFormElementDao->getReviewFormElements($this->reviewFormId);
		$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');
		$reviewFormResponses =& $reviewFormResponseDao->getReviewReviewFormResponseValues($this->reviewId);
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = $reviewAssignmentDao->getReviewAssignmentById($this->reviewId);

		$editorPreview = Request::getRequestedPage() != 'reviewer';

		if (!$editorPreview) {
			ReviewerHandler::setupTemplate(true, $reviewAssignment->getArticleId(), $this->reviewId);
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageTitle', 'submission.reviewFormResponse');
		$templateMgr->assign_by_ref('reviewForm', $reviewForm);
		$templateMgr->assign('reviewFormElements', $reviewFormElements);
		$templateMgr->assign('reviewFormResponses', $reviewFormResponses);
		$templateMgr->assign('reviewId', $this->reviewId);
		$templateMgr->assign('articleId', $reviewAssignment->getArticleId());
		$templateMgr->assign('isLocked', isset($reviewAssignment) && $reviewAssignment->getDateCompleted() != null);
		$templateMgr->assign('editorPreview', $editorPreview);

		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'reviewFormResponses'
			)
		);
	}

	/**
	 * Save the response.
	 */
	function execute() {
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment =& $reviewAssignmentDao->getReviewAssignmentById($this->reviewId);
		$reviewAssignmentDao->updateReviewAssignment($reviewAssignment);

		$reviewFormResponseDao =& DAORegistry::getDAO('ReviewFormResponseDAO');

		foreach ($this->getData('reviewFormResponses') as $reviewFormElementId => $reviewFormResponseValue) {
			$reviewFormResponse =& $reviewFormResponseDao->getReviewFormResponse($this->reviewId, $reviewFormElementId);
			if (!isset($reviewFormResponse)) {
				$reviewFormResponse =& new ReviewFormResponse();
			}
			$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
			$reviewFormElement = $reviewFormElementDao->getReviewFormElement($reviewFormElementId);
			$elementType = $reviewFormElement->getElementType();
			switch ($elementType) {
				case REVIEW_FORM_ELEMENT_TYPE_SMALL_TEXT_FIELD:
				case REVIEW_FORM_ELEMENT_TYPE_TEXT_FIELD:
				case REVIEW_FORM_ELEMENT_TYPE_TEXTAREA:
					$reviewFormResponse->setResponseType('string');
					$reviewFormResponse->setValue($reviewFormResponseValue);
					break;
				case REVIEW_FORM_ELEMENT_TYPE_RADIO_BUTTONS:
				case REVIEW_FORM_ELEMENT_TYPE_DROP_DOWN_BOX:
					$reviewFormResponse->setResponseType('int');
					$reviewFormResponse->setValue($reviewFormResponseValue);
					break;
				case REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES:
					$reviewFormResponse->setResponseType('object');
					$reviewFormResponse->setValue($reviewFormResponseValue);
					break;
			}
			if ($reviewFormResponse->getReviewFormElementId() != null && $reviewFormResponse->getReviewId() != null) {
				$reviewFormResponseDao->updateReviewFormResponse($reviewFormResponse);
			} else {
				$reviewFormResponse->setReviewFormElementId($reviewFormElementId);
				$reviewFormResponse->setReviewId($this->reviewId);
				$reviewFormResponseDao->insertReviewFormResponse($reviewFormResponse);
			}
		}
	}
}

?>
