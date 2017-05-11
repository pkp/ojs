<?php

/**
 * @file classes/form/validation/FormValidatorDate.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorDate
 * @ingroup form_validation
 *
 * @brief Form validation check that field is a date or date part.
 */

import('lib.pkp.classes.form.validation.FormValidator');
import('lib.pkp.classes.validation.ValidatorDate');

class FormValidatorDate extends FormValidator {

	/** 
	 * @var int Date minimum resolution required
	 */
	var $_scopeMin;

	/** 
	 * @var int Date maximum resolution allowed
	 */
	var $_scopeMax;
	
	/**
	 * Constructor.
	 * @param $form Form the associated form
	 * @param $field string the name of the associated field
	 * @param $type string the type of check, either "required" or "optional"
	 * @param $message string the error message for validation failures (i18n key)
	 * @param $dateFormat int the ValidatorDate date format to allow
	 * @param $dateScope string the minimum resolution of a date to allow
	 * @param $dateScope string the maximum resolution of a date to allow
	 */
	function __construct(&$form, $field, $type, $message, $dateFormat = DATE_FORMAT_ISO, $dateScopeMin = VALIDATOR_DATE_SCOPE_YEAR, $dateScopeMax = VALIDATOR_DATE_SCOPE_DAY) {
		$validator = new ValidatorDate($dateFormat);
		$this->_scopeMin = $dateScopeMin;
		$this->_scopeMax = $dateScopeMax;
		parent::__construct($form, $field, $type, $message, $validator);
	}

	//
	// Implement abstract methods from Validator
	//
	/**
	 * @see Validator::isValid()
	 * @param $value mixed
	 * @return boolean
	 */
	function isValid() {
		// check if generally formatted as a date and if required
		if (!parent::isValid()) return false;
		// if parent::isValid is true and $value is empty, this value is optional
		$fieldValue = $this->getFieldValue();
		if (!$fieldValue) return true;

		$validator = parent::getValidator();
		return $validator->isValid($fieldValue, $this->_scopeMin, $this->_scopeMax);
	}
}

?>
