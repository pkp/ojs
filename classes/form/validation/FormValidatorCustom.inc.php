<?php

/**
 * FormValidatorCustom.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package form.validation
 *
 * Form validation check with a custom user function performing the validation check.
 *
 * $Id$
 */

class FormValidatorCustom extends FormValidator {

	/** Custom validation function */
	var $userFunction;
	
	/** If true, field is considered valid if user function returns false instead of true */
	var $complementReturn;
	
	/**
	 * Constructor.
	 * @see FormValidator::FormValidator()
	 * @param $userFunction function the user function to use for validation
	 * @param $complementReturn boolean optional, complement the value returned by $userFunction
	 */
	function FormValidatorCustom($form, $field, $type, $message, $userFunction, $complementReturn = false) {
		parent::FormValidator(&$form, $field, $type, $message, $userFunction);
		$this->userFunction = $userFunction;
		$this->complementReturn = $complementReturn;
	}
	
	/**
	 * Check if field value is valid.
	 * Value is valid if it is empty and optional or validated by user-supplied function.
	 * @return boolean
	 */
	function isValid() {
		if ($this->isEmptyAndOptional($this->form->getData($this->field))) {
			return true;
			
		} else {
			$ret = call_user_func($this->userFunction, $this->form->getData($this->field));
			return $this->complementReturn ? !$ret : $ret;
		}
	}
	
}

?>
