<?php

/**
 * @file classes/form/validation/FormValidatorBoolean.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorBoolean
 * @ingroup form_validation
 *
 * @brief Form validation check that checks if the value can be
 *  interpreted as a boolean value. An empty field is considered
 *  'false', a value of '1' is considered 'true'.
 */

import('lib.pkp.classes.form.validation.FormValidator');

class FormValidatorBoolean extends FormValidator {
	/**
	 * Constructor.
	 * @param $form Form the associated form
	 * @param $field string the name of the associated field
	 * @param $message string the error message for validation failures (i18n key)
	 */
	function __construct(&$form, $field, $message) {
		parent::__construct($form, $field, FORM_VALIDATOR_OPTIONAL_VALUE, $message);
	}


	//
	// Public methods
	//
	/**
	 * Value is valid if it is empty (false) or has
	 * value '1' (true). This assumes checkbox
	 * behavior in the form.
	 * @see FormValidator::isValid()
	 * @return boolean
	 */
	function isValid() {
		$value = $this->getFieldValue();
		$form =& $this->getForm();
		if (empty($value) || $value == 'on') {
			// Make sure that the form will contain a real
			// boolean value after validation.
			$value = ($value == 'on' ? true : false);
			$form->setData($this->getField(), $value);
			return true;
		} elseif($value === '1' || $value === '0') {
			$value = ($value === '1' ? true : false);
			$form->setData($this->getField(), $value);
			return true;
		} else {
			return false;
		}
	}
}

?>
