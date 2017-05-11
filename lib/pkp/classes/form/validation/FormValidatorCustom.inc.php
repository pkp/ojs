<?php

/**
 * @file classes/form/validation/FormValidatorCustom.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorCustom
 * @ingroup form_validation
 *
 * @brief Form validation check with a custom user function performing the validation check.
 */

import('lib.pkp.classes.form.validation.FormValidator');

class FormValidatorCustom extends FormValidator {

	/** @var callable Custom validation function */
	var $_userFunction;

	/** @var array Additional arguments to pass to $userFunction */
	var $_additionalArguments;

	/** @var boolean If true, field is considered valid if user function returns false instead of true */
	var $_complementReturn;

	/**
	 * Constructor.
	 * The user function is passed the form data as its first argument and $additionalArguments, if set, as the remaining arguments. This function must return a boolean value.
	 * @param $form Form the associated form
	 * @param $field string the name of the associated field
	 * @param $type string the type of check, either "required" or "optional"
	 * @param $message string the error message for validation failures (i18n key)
	 * @param $userFunction callable function the user function to use for validation
	 * @param $additionalArguments array optional, a list of additional arguments to pass to $userFunction
	 * @param $complementReturn boolean optional, complement the value returned by $userFunction
	 */
	function __construct(&$form, $field, $type, $message, $userFunction, $additionalArguments = array(), $complementReturn = false) {
		parent::__construct($form, $field, $type, $message);
		$this->_userFunction = $userFunction;
		$this->_additionalArguments = $additionalArguments;
		$this->_complementReturn = $complementReturn;
	}


	//
	// Public methods
	//
	/**
	 * @see FormValidator::isValid()
	 * Value is valid if it is empty and optional or validated by user-supplied function.
	 * @return boolean
	 */
	function isValid() {
		if ($this->isEmptyAndOptional()) {
			return true;

		} else {
			$ret = call_user_func_array($this->_userFunction, array_merge(array($this->getFieldValue()), $this->_additionalArguments));
			return $this->_complementReturn ? !$ret : $ret;
		}
	}
}

?>
