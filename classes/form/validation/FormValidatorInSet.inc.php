<?php

/**
 * FormValidatorInSet.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package form.validation
 *
 * Form validation check that checks if value is within a certain set.
 *
 * $Id$
 */

import('form.validation.FormValidator');

class FormValidatorInSet extends FormValidator {

	/**  Array of all values accepted as valid */
	var $acceptedValues;
	
	/**
	 * Constructor.
	 * @see FormValidator::FormValidator()
	 * @param $acceptedValues array all possible accepted values
	 */
	function FormValidatorInSet(&$form, $field, $type, $message, $acceptedValues) {
		parent::FormValidator($form, $field, $type, $message);
		$this->acceptedValues = $acceptedValues;
	}
	
	/**
	 * Check if field value is valid.
	 * Value is valid if it is empty and optional or is in the set of accepted values.
	 * @return boolean
	 */
	function isValid() {
		return $this->isEmptyAndOptional() || in_array($this->form->getData($this->field), $this->acceptedValues);
	}
	
}

?>
