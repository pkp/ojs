<?php

/**
 * @file classes/form/validation/FormValidatorArray.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorArray
 * @ingroup form_validation
 *
 * @brief Form validation check that checks an array of fields.
 */

import('lib.pkp.classes.form.validation.FormValidator');

class FormValidatorArray extends FormValidator {

	/** @var array Array of fields to check */
	var $_fields;

	/** @var array Array of field names where an error occurred */
	var $_errorFields;

	/**
	 * Constructor.
	 * @param $form Form the associated form
	 * @param $field string the name of the associated field
	 * @param $type string the type of check, either "required" or "optional"
	 * @param $message string the error message for validation failures (i18n key)
	 * @param $fields array all subfields for each item in the array, i.e. name[][foo]. If empty it is assumed that name[] is a data field
	 */
	function __construct(&$form, $field, $type, $message, $fields = array()) {
		parent::__construct($form, $field, $type, $message);
		$this->_fields = $fields;
		$this->_errorFields = array();
	}


	//
	// Setters and Getters
	//
	/**
	 * Get array of fields where an error occurred.
	 * @return array
	 */
	function getErrorFields() {
		return $this->_errorFields;
	}


	//
	// Public methods
	//
	/**
	 * @see FormValidator::isValid()
	 * Value is valid if it is empty and optional or all field values are set.
	 * @return boolean
	 */
	function isValid() {
		if ($this->getType() == FORM_VALIDATOR_OPTIONAL_VALUE) return true;

		$data = $this->getFieldValue();
		if (!is_array($data)) return false;

		$isValid = true;
		foreach ($data as $key => $value) {
			if (count($this->_fields) == 0) {
				// We expect all fields to contain values.
				if (is_null($value) || trim((string)$value) == '') {
					$isValid = false;
					array_push($this->_errorFields, $this->getField()."[{$key}]");
				}
			} else {
				// In the two-dimensional case we always expect a value array.
				if (!is_array($value)) {
					$isValid = false;
					array_push($this->_errorFields, $this->getField()."[{$key}]");
					continue;
				}

				// Go through all sub-sub-fields and check them explicitly
				foreach ($this->_fields as $field) {
					if (!isset($value[$field]) || trim((string)$value[$field]) == '') {
						$isValid = false;
						array_push($this->_errorFields, $this->getField()."[{$key}][{$field}]");
					}
				}
			}
		}

		return $isValid;
	}
}

?>
