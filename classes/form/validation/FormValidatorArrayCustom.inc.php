<?php

/**
 * @file classes/form/validation/FormValidatorArrayCustom.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorArrayCustom
 * @ingroup form_validation
 *
 * @brief Form validation check with a custom user function performing the validation check of an array of fields.
 */

// $Id$


import('form.validation.FormValidator');

class FormValidatorArrayCustom extends FormValidator {

	/** @var array Array of fields to check */
	var $fields;

	/** @var array Array of field names where an error occurred */
	var $errorFields;

	/** @var boolean is the field a multilingual-capable field */
	var $isLocaleField;

	/** Custom validation function */
	var $userFunction;

	/** Additional arguments to pass to $userFunction */
	var $additionalArguments;

	/** If true, field is considered valid if user function returns false instead of true */
	var $complementReturn;

	/**
	 * Constructor.
	 * @see FormValidator::FormValidator()
	 * @param $field string field name specifying an array of fields, i.e. name[]
	 * @param $fields array all subfields for each item in the array, i.e. name[][foo]. If empty it is assumed that name[] is a data field
	 */
	function FormValidatorArrayCustom(&$form, $field, $type, $message, $userFunction, $additionalArguments = array(), $complementReturn = false, $fields = array(), $isLocaleField = false) {
		parent::FormValidator($form, $field, $type, $message);
		$this->fields = $fields;
		$this->errorFields = array();
		$this->isLocaleField = $isLocaleField;
		$this->userFunction = $userFunction;
		$this->additionalArguments = $additionalArguments;
		$this->complementReturn = $complementReturn;
	}

	/**
	 * Check if field value is valid.
	 * Value is valid if it is empty and optional or is in the set of accepted values.
	 * @return boolean
	 */
	function isValid() {
		if ($this->type == 'optional') {
			return true;
		}

		$ret = true;
		$data = $this->form->getData($this->field);
		if (!is_array($data)) return false;
		foreach ($data as $key => $value) {
			if (count($this->fields) == 0) {
				if ($this->isLocaleField) {
					$ret = call_user_func_array($this->userFunction, array_merge(array($value), $key, $this->additionalArguments));
				} else {
					$ret = call_user_func_array($this->userFunction, array_merge(array($value), $this->additionalArguments));					
				}
				$ret = $this->complementReturn ? !$ret : $ret;
				if (!$ret) {
					if ($this->isLocaleField) {
						array_push($this->errorFields, array($key => "{$this->field}[{$key}]"));
					} else {
						array_push($this->errorFields, "{$this->field}[{$key}]");
					}
				}
			} else {
				foreach ($this->fields as $field) {
					if ($this->isLocaleField) {
						$ret = call_user_func_array($this->userFunction, array_merge(array($value[$field]), $key, $this->additionalArguments));
					} else {
						$ret = call_user_func_array($this->userFunction, array_merge(array($value[$field]), $this->additionalArguments));
					}
					$ret = $this->complementReturn ? !$ret : $ret;
					if (!$ret) {
						if ($this->isLocaleField) {
							array_push($this->errorFields, array($key => "{$this->field}[{$key}][{$field}]"));
						} else {
							array_push($this->errorFields, "{$this->field}[{$key}][{$field}]");
						}
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * Get array of fields where an error occurred.
	 * @return array
	 */
	function getErrorFields() {
		return $this->errorFields;
	}

	/**
	 * Is the field an array.
	 * @return boolean
	 */
	function isArray() {
		return is_array($this->form->getData($this->field));
	}

	/**
	 * Is it a multilingual-capable field.
	 * @return boolean
	 */
	function isLocaleField() {
		return $this->isLocaleField;
	}
}

?>
