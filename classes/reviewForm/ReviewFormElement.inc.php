<?php

/**
 * @file classes/reviewForm/ReviewFormElement.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormElement
 * @ingroup reviewForm
 * @see ReviewFormElementDAO
 *
 * @brief Basic class describing a review form element.
 *
 */

define('REVIEW_FORM_ELEMENT_TYPE_SMALL_TEXT_FIELD',	0x000001);
define('REVIEW_FORM_ELEMENT_TYPE_TEXT_FIELD',		0x000002);
define('REVIEW_FORM_ELEMENT_TYPE_TEXTAREA',		0x000003);
define('REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES',		0x000004);
define('REVIEW_FORM_ELEMENT_TYPE_RADIO_BUTTONS',	0x000005);
define('REVIEW_FORM_ELEMENT_TYPE_DROP_DOWN_BOX',	0x000006);

class ReviewFormElement extends DataObject {

	/**
	 * Constructor.
	 */
	function ReviewFormElement() {
		parent::DataObject();
	}

	/**
	 * Get localized question.
	 * @return string
	 */
	function getReviewFormElementQuestion() {
		return $this->getLocalizedData('question');
	}

	/**
	 * Get localized possible response.
	 * @return array
	 */
	function getReviewFormElementPossibleResponses() {
		return $this->getLocalizedData('possibleResponses');
	}

	//
	// Get/set methods
	//

	/**
	 * Get the ID of the review form element.
	 * @return int
	 */
	function getReviewFormElementId() {
		return $this->getData('reviewFormElementId');
	}

	/**
	 * Set the ID of the review form element.
	 * @param $reviewFormElementId int
	 */
	function setReviewFormElementId($reviewFormElementId) {
		return $this->setData('reviewFormElementId', $reviewFormElementId);
	}

	/**
	 * Get the review form ID of the review form element.
	 * @return int
	 */
	function getReviewFormId() {
		return $this->getData('reviewFormId');
	}

	/**
	 * Set the review form ID of the review form element.
	 * @param $reviewFormId int
	 */
	function setReviewFormId($reviewFormId) {
		return $this->setData('reviewFormId', $reviewFormId);
	}

	/**
	 * Get sequence of review form element.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence of review form element.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		return $this->setData('sequence', $sequence);
	}

	/**
	 * Get the type of the review form element.
	 * @return string
	 */
	function getElementType() {
		return $this->getData('reviewFormElementType');
	}

	/**
	 * Set the type of the review form element.
	 * @param $reviewFormElementType string
	 */
	function setElementType($reviewFormElementType) {
		return $this->setData('reviewFormElementType', $reviewFormElementType);
	}

	/**
	 * get required
	 * @return boolean
	 */
	function getRequired() {
		return $this->getData('required');
	}

	/**
	 * set viewable
	 * @param $viewable boolean
	 */
	function setRequired($required) {
		return $this->setData('required', $required);
	}

	/**
	 * Get question.
	 * @param $locale string
	 * @return string
	 */
	function getQuestion($locale) {
		return $this->getData('question', $locale);
	}

	/**
	 * Set question.
	 * @param $question string
	 * @param $locale string
	 */
	function setQuestion($question, $locale) {
		return $this->setData('question', $question, $locale);
	}

	/**
	 * Get possible response.
	 * @param $locale string
	 * @return string
	 */
	function getPossibleResponses($locale) {
		return $this->getData('possibleResponses', $locale);
	}

	/**
	 * Set possibleResponse.
	 * @param $possibleResponse string
	 * @param $locale string
	 */
	function setPossibleResponses($possibleResponses, $locale) {
		return $this->setData('possibleResponses', $possibleResponses, $locale);
	}

	/**
	 * Get an associative array matching review form element type codes with locale strings.
	 * (Includes default '' => "Choose One" string.)
	 * @return array reviewFormElementType => localeString
	 */
	function &getReviewFormElementTypeOptions() {
		static $reviewFormElementTypeOptions = array(
			'' => 'manager.reviewFormElements.chooseType',
			REVIEW_FORM_ELEMENT_TYPE_SMALL_TEXT_FIELD => 'manager.reviewFormElements.smalltextfield',
			REVIEW_FORM_ELEMENT_TYPE_TEXT_FIELD => 'manager.reviewFormElements.textfield',
			REVIEW_FORM_ELEMENT_TYPE_TEXTAREA => 'manager.reviewFormElements.textarea',
			REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES => 'manager.reviewFormElements.checkboxes',
			REVIEW_FORM_ELEMENT_TYPE_RADIO_BUTTONS => 'manager.reviewFormElements.radiobuttons',
			REVIEW_FORM_ELEMENT_TYPE_DROP_DOWN_BOX => 'manager.reviewFormElements.dropdownbox'
		);
		return $reviewFormElementTypeOptions;
	}

	/**
	 * Get an array of all multiple responses element types
	 * @return array reviewFormElementTypes
	 */
	function &getMultipleResponsesElementTypes() {
		static $multipleResponsesElementTypes = array(REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES, REVIEW_FORM_ELEMENT_TYPE_RADIO_BUTTONS, REVIEW_FORM_ELEMENT_TYPE_DROP_DOWN_BOX);
		return $multipleResponsesElementTypes;
	}
}

?>
