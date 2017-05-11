<?php

/**
 * @file classes/manager/form/ReviewFormElementForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormElementForm
 * @ingroup controllers_grid_settings_reviewForms_form
 * @see ReviewFormElement
 *
 * @brief Form for creating and modifying review form elements.
 *
 */

import('lib.pkp.classes.db.DBDataXMLParser');
import('lib.pkp.classes.form.Form');

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
	function __construct($reviewFormId, $reviewFormElementId = null) {
		parent::__construct('manager/reviewForms/reviewFormElementForm.tpl');

		$this->reviewFormId = $reviewFormId;
		$this->reviewFormElementId = $reviewFormElementId;

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'question', 'required', 'manager.reviewFormElements.form.questionRequired'));
		$this->addCheck(new FormValidator($this, 'elementType', 'required', 'manager.reviewFormElements.form.elementTypeRequired'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Get the names of fields for which localized data is allowed.
	 * @return array
	 */
	function getLocaleFieldNames() {
		$reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO');
		return $reviewFormElementDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function fetch($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		import('lib.pkp.classes.reviewForm.ReviewFormElement');
		$templateMgr->assign(array(
			'reviewFormId' => $this->reviewFormId,
			'reviewFormElementId' => $this->reviewFormElementId,
			'multipleResponsesElementTypes' => ReviewFormElement::getMultipleResponsesElementTypes(),
			'multipleResponsesElementTypesString' => ';'.implode(';', ReviewFormElement::getMultipleResponsesElementTypes()).';',
			'reviewFormElementTypeOptions' => ReviewFormElement::getReviewFormElementTypeOptions(),
		));
		return parent::fetch($request);
	}

	/**
	 * Initialize form data from current review form.
	 * @param $request PKPRequest
	 */
	function initData($request) {
		if ($this->reviewFormElementId) {
			$context = $request->getContext();
			$reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO');
			$reviewFormElement = $reviewFormElementDao->getById($this->reviewFormElementId, $this->reviewFormId);
			$this->_data = array(
				'question' => $reviewFormElement->getQuestion(null), // Localized
				'required' => $reviewFormElement->getRequired(),
				'included' => $reviewFormElement->getIncluded(),

				'elementType' => $reviewFormElement->getElementType(),
				'possibleResponses' => $reviewFormElement->getPossibleResponses(null) //Localized
			);
		} else {
			$this->_data = array(
				'included' => 1
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('question', 'required', 'included', 'elementType', 'possibleResponses'));
	}

	/**
	 * Save review form element.
	 * @param $request PKPRequest
	 * @return int Review form element ID
	 */
	function execute($request) {
		$reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO');

		if ($this->reviewFormElementId) {
			$context = $request->getContext();
			$reviewFormElement = $reviewFormElementDao->getById($this->reviewFormElementId);
			$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
			$reviewForm = $reviewFormDao->getById($reviewFormElement->getReviewFormId(), Application::getContextAssocType(), $context->getId());
			if (!$reviewForm) fatalError('Invalid review form element ID!');
		} else {
			$reviewFormElement = $reviewFormElementDao->newDataObject();
			$reviewFormElement->setReviewFormId($this->reviewFormId);
			$reviewFormElement->setSequence(REALLY_BIG_NUMBER);
		}

		$reviewFormElement->setQuestion($this->getData('question'), null); // Localized
		$reviewFormElement->setRequired($this->getData('required') ? 1 : 0);
		$reviewFormElement->setIncluded($this->getData('included') ? 1 : 0);
		$reviewFormElement->setElementType($this->getData('elementType'));

		if (in_array($this->getData('elementType'), ReviewFormElement::getMultipleResponsesElementTypes())) {
			$this->setData('possibleResponsesProcessed', $reviewFormElement->getPossibleResponses(null));
			ListbuilderHandler::unpack($request, $this->getData('possibleResponses'), array($this, 'deleteEntry'), array($this, 'insertEntry'), array($this, 'updateEntry'));
			$reviewFormElement->setPossibleResponses($this->getData('possibleResponsesProcessed'), null);
		} else {
			$reviewFormElement->setPossibleResponses(null, null);
		}
		if ($reviewFormElement->getId()) {
			$reviewFormElementDao->deleteSetting($reviewFormElement->getId(), 'possibleResponses');
			$reviewFormElementDao->updateObject($reviewFormElement);
		} else {
			$this->reviewFormElementId = $reviewFormElementDao->insertObject($reviewFormElement);
			$reviewFormElementDao->resequenceReviewFormElements($this->reviewFormId);
		}
		return $this->reviewFormElementId;
	}

	/**
	 * @copydoc ListbuilderHandler::insertEntry()
	 */
	function insertEntry($request, $newRowId) {
		$possibleResponsesProcessed = (array) $this->getData('possibleResponsesProcessed');
		foreach ($newRowId['possibleResponse'] as $key => $value) {
			$possibleResponsesProcessed[$key][] = $value;
		}
		$this->setData('possibleResponsesProcessed', $possibleResponsesProcessed);
		return true;
	}

	/**
	 * @copydoc ListbuilderHandler::deleteEntry()
	 */
	function deleteEntry($request, $rowId) {
		$possibleResponsesProcessed = (array) $this->getData('possibleResponsesProcessed');
		foreach (array_keys($possibleResponsesProcessed) as $locale) {
			// WARNING: Listbuilders don't like zero row IDs. They are offset
			// by 1 to avoid this case, so 1 is subtracted here to normalize.
			unset($possibleResponsesProcessed[$locale][$rowId-1]);
		}
		$this->setData('possibleResponsesProcessed', $possibleResponsesProcessed);
		return true;
	}

	/**
	 * @copydoc ListbuilderHandler::updateEntry
	 */
	function updateEntry($request, $rowId, $newRowId) {
		$possibleResponsesProcessed = (array) $this->getData('possibleResponsesProcessed');
		foreach ($newRowId['possibleResponse'] as $locale => $value) {
			// WARNING: Listbuilders don't like zero row IDs. They are offset
			// by 1 to avoid this case, so 1 is subtracted here to normalize.
			$possibleResponsesProcessed[$locale][$rowId-1] = $value;
		}
		$this->setData('possibleResponsesProcessed', $possibleResponsesProcessed);
		return true;
	}
}

?>
