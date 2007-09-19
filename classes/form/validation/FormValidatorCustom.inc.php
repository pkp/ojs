<?php

/**
 * @file FormValidatorCustom.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package form.validation
 * @class FormValidatorCustom
 *
 * Form validation check with a custom user function performing the validation check.
 *
 * $Id$
 */

import('form.validation.FormValidator');

class FormValidatorCustom extends FormValidator {

	/** Custom validation function */
	var $userFunction;

	/** Additional arguments to pass to $userFunction */
	var $additionalArguments;

	/** If true, field is considered valid if user function returns false instead of true */
	var $complementReturn;

	/**
	 * Constructor.
	 * The user function is passed the form data as its first argument and $additionalArguments, if set, as the remaining arguments. This function must return a boolean value.
	 * @see FormValidator::FormValidator()
	 * @param $userFunction function the user function to use for validation
	 * @param $additionalArguments array optional, a list of additional arguments to pass to $userFunction
	 * @param $complementReturn boolean optional, complement the value returned by $userFunction
	 */
	function FormValidatorCustom(&$form, $field, $type, $message, $userFunction, $additionalArguments = array(), $complementReturn = false) {
		parent::FormValidator($form, $field, $type, $message);
		$this->userFunction = $userFunction;
		$this->additionalArguments = $additionalArguments;
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
			$ret = call_user_func_array($this->userFunction, array_merge(array($this->form->getData($this->field)), $this->additionalArguments));
			return $this->complementReturn ? !$ret : $ret;
		}
	}

}

?>
