<?php

/**
 * FormValidatorArray.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package form.validation
 *
 * Form validation check that checks an array of fields.
 *
 * $Id$
 */

import('form.validation.FormValidatorArray');

class FormValidatorArray extends FormValidator {

	/** @var array Array of fields to check */
	var $fields;

	/** @var array Array of field names where an error occurred */
	var $errorFields;
	
	/**
	 * Constructor.
	 * @see FormValidator::FormValidator()
	 * @param $field string field name specifying an array of fields, i.e. name[]
	 * @param $fields array all subfields for each item in the array, i.e. name[][foo]. If empty it is assumed that name[] is a data field
	 */
	function FormValidatorArray($form, $field, $type, $message, $fields = array()) {
		parent::FormValidator(&$form, $field, $type, $message);
		$this->fields = $fields;
		$this->errorFields = array();
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
		foreach ($data as $key => $value) {
			if (count($this->fields) == 0) {
				if (trim($value) == '') {
					$ret = false;
					array_push($this->errorFields, "{$this->field}[{$key}]");
				}
				
			} else {
				foreach ($this->fields as $field) {
					if (trim($value[$field]) == '') {
						$ret = false;
						array_push($this->errorFields, "{$this->field}[{$key}][{$field}]");
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
	
}

?>
