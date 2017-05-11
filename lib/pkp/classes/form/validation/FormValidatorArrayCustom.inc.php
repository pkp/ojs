<?php

/**
 * @file classes/form/validation/FormValidatorArrayCustom.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorArrayCustom
 * @ingroup form_validation
 *
 * @brief Form validation check with a custom user function performing the validation check of an array of fields.
 */

import('lib.pkp.classes.form.validation.FormValidator');

class FormValidatorArrayCustom extends FormValidator {

	/** @var array Array of fields to check */
	var $_fields;

	/** @var array Array of field names where an error occurred */
	var $_errorFields;

	/** @var boolean is the field a multilingual-capable field */
	var $_isLocaleField;

	/** @var callable Custom validation function */
	var $_userFunction;

	/** @var array Additional arguments to pass to $userFunction */
	var $_additionalArguments;

	/** @var boolean If true, field is considered valid if user function returns false instead of true */
	var $_complementReturn;

	/**
	 * Constructor.
	 * @param $form Form the associated form
	 * @param $field string the name of the associated field
	 * @param $type string the type of check, either "required" or "optional"
	 * @param $message string the error message for validation failures (i18n key)
	 * @param $userFunction function the user function to use for validation
	 * @param $additionalArguments array optional, a list of additional arguments to pass to $userFunction
	 * @param $complementReturn boolean optional, complement the value returned by $userFunction
	 * @param $fields array all subfields for each item in the array, i.e. name[][foo]. If empty it is assumed that name[] is a data field
	 * @param $isLocaleField boolean
	 */
	function __construct(&$form, $field, $type, $message, $userFunction, $additionalArguments = array(), $complementReturn = false, $fields = array(), $isLocaleField = false) {
		parent::__construct($form, $field, $type, $message);
		$this->_fields = $fields;
		$this->_errorFields = array();
		$this->_isLocaleField = $isLocaleField;
		$this->_userFunction = $userFunction;
		$this->_additionalArguments = $additionalArguments;
		$this->_complementReturn = $complementReturn;
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

	/**
	 * Is it a multilingual-capable field.
	 * @return boolean
	 */
	function isLocaleField() {
		return $this->_isLocaleField;
	}


	//
	// Public methods
	//
	/**
	 * @see FormValidator::isValid()
	 * @return boolean
	 */
	function isValid() {
		if ($this->isEmptyAndOptional()) return true;

		$data = $this->getFieldValue();
		if (!is_array($data)) return false;

		$isValid = true;
		foreach ($data as $key => $value) {
			// Bypass check for empty sub-fields if validation type is "optional"
			if ($this->getType() == FORM_VALIDATOR_OPTIONAL_VALUE && ($value == array() || $value == '')) continue;

			if (count($this->_fields) == 0) {
				if ($this->isLocaleField()) {
					$ret = call_user_func_array($this->_userFunction, array_merge(array($value, $key), $this->_additionalArguments));
				} else {
					$ret = call_user_func_array($this->_userFunction, array_merge(array($value), $this->_additionalArguments));
				}
				$ret = $this->_complementReturn ? !$ret : $ret;
				if (!$ret) {
					$isValid = false;
					if ($this->isLocaleField()) {
						$this->_errorFields[$key] = $this->getField()."[{$key}]";
					} else {
						array_push($this->_errorFields, $this->getField()."[{$key}]");
					}
				}
			} else {
				// In the two-dimensional case we always expect a value array.
				if (!is_array($value)) {
					$isValid = false;
					if ($this->isLocaleField()) {
						$this->_errorFields[$key] = $this->getField()."[{$key}]";
					} else {
						array_push($this->_errorFields, $this->getField()."[{$key}]");
					}
					continue;
				}

				foreach ($this->_fields as $field) {
					// Bypass check for empty sub-sub-fields if validation type is "optional"
					if ($this->getType() == FORM_VALIDATOR_OPTIONAL_VALUE) {
						if (!isset($value[$field]) || $value[$field] == array() or $value[$field] == '') continue;
					} else {
						// Make sure that we pass in 'null' to the user function
						// if the expected field doesn't exist in the value array.
						if (!array_key_exists($field, $value)) $value[$field] = null;
					}

					if ($this->isLocaleField()) {
						$ret = call_user_func_array($this->_userFunction, array_merge(array($value[$field], $key), $this->_additionalArguments));
					} else {
						$ret = call_user_func_array($this->_userFunction, array_merge(array($value[$field]), $this->_additionalArguments));
					}
					$ret = $this->_complementReturn ? !$ret : $ret;
					if (!$ret) {
						$isValid = false;
						if ($this->isLocaleField()) {
							if (!isset($this->_errorFields[$key])) $this->_errorFields[$key] = array();
							array_push($this->_errorFields[$key], $this->getField()."[{$key}][{$field}]");
						} else {
							array_push($this->_errorFields, $this->getField()."[{$key}][{$field}]");
						}
					}
				}
			}
		}
		return $isValid;
	}

	/**
	 * Is the field an array.
	 * @return boolean
	 */
	function isArray() {
		return is_array($this->getFieldValue());
	}
}

?>
