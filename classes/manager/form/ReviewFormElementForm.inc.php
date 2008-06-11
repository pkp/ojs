<?php

/**
 * @file ReviewFormElementForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form
 * @class ReviewFormElementForm
 *
 * Form for creating and modifying review form elements.
 *
 */

import('form.Form');

class ReviewFormElementForm extends Form {

	/** @var $reviewFormId int The ID of the review form being edited */
	var $reviewFormId;

	/** @var $reviewFormElementId int The ID of the review form element being edited */
	var $reviewFormElementId;

	/**
	 * Constructor.
	 * @param $reviewFormId int
	 * @param $reviewFormElementId int
	 */
	function ReviewFormElementForm($reviewFormId, $reviewFormElementId = null) {
		parent::Form('manager/reviewForms/reviewFormElementForm.tpl');

		$this->reviewFormId = $reviewFormId;
		$this->reviewFormElementId = $reviewFormElementId;

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'question', 'required', 'manager.reviewFormElements.form.questionRequired'));
		$this->addCheck(new FormValidator($this, 'elementType', 'required', 'manager.reviewFormElements.form.elementTypeRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Get the names of fields for which localized data is allowed.
	 * @return array
	 */
	function getLocaleFieldNames() {
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
		return $reviewFormElementDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('reviewFormId', $this->reviewFormId);
		$templateMgr->assign('reviewFormElementId', $this->reviewFormElementId);
		$templateMgr->assign_by_ref('multipleResponsesElementTypes', ReviewFormElement::getMultipleResponsesElementTypes());
		// in order to be able to search for an element in the array in the javascript function 'togglePossibleResponses':
		$templateMgr->assign('multipleResponsesElementTypesString', ';'.implode(';', ReviewFormElement::getMultipleResponsesElementTypes()).';');
		import('reviewForm.ReviewFormElement');
		$templateMgr->assign_by_ref('reviewFormElementTypeOptions', ReviewFormElement::getReviewFormElementTypeOptions());
		$templateMgr->assign('helpTopicId','journal.managementPages.reviewForms');
		parent::display();
	}

	/**
	 * Initialize form data from current review form.
	 */
	function initData() {
		if ($this->reviewFormElementId != null) {
			$journal =& Request::getJournal();
			$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
			$reviewFormElement =& $reviewFormElementDao->getReviewFormElement($this->reviewFormElementId);

			if ($reviewFormElement == null) {
				$this->reviewFormElementId = null;
			} else {
				$this->_data = array(
					'question' => $reviewFormElement->getQuestion(null), // Localized
					'required' => $reviewFormElement->getRequired(),
					'elementType' => $reviewFormElement->getElementType(),
					'possibleResponses' => $reviewFormElement->getPossibleResponses(null) //Localized
				);
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('question', 'required', 'elementType', 'possibleResponses'));
	}

	/**
	 * Save review form element.
	 */
	function execute() {
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');

		if ($this->reviewFormElementId != null) {
			$reviewFormElement =& $reviewFormElementDao->getReviewFormElement($this->reviewFormElementId);
		}

		if (!isset($reviewFormElement)) {
			$reviewFormElement =& new ReviewFormElement();
			$reviewFormElement->setReviewFormId($this->reviewFormId);
			$reviewFormElement->setSequence(REALLY_BIG_NUMBER);
		}

		$reviewFormElement->setQuestion($this->getData('question'), null); // Localized
		$reviewFormElement->setRequired($this->getData('required') ? 1 : 0);
		$reviewFormElement->setElementType($this->getData('elementType'));

		if (in_array($this->getData('elementType'), ReviewFormElement::getMultipleResponsesElementTypes())) {
			$reviewFormElement->setPossibleResponses($this->getData('possibleResponses'), null); // Localized
		} else {
			$reviewFormElement->setPossibleResponses(null, null);
		}

		if ($reviewFormElement->getReviewFormElementId() != null) {
			$reviewFormElementDao->deleteSetting($reviewFormElement->getReviewFormElementId(), 'possibleResponses');
			$reviewFormElementDao->updateReviewFormElement($reviewFormElement);
			$this->reviewFormElementId = $reviewFormElement->getReviewFormElementId();
		} else {
			$this->reviewFormElementId = $reviewFormElementDao->insertReviewFormElement($reviewFormElement);
			$reviewFormElementDao->resequenceReviewFormElements($this->reviewFormId);
		}

	}
}

?>
