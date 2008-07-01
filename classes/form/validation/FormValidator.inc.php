<?php

/**
 * @defgroup form_validation
 */
 
/**
 * @file classes/form/validation/FormValidator.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidator
 * @ingroup form_validation
 *
 * @brief Class to represent a form validation check.
 */

// $Id$


import('form.validation.FormValidatorRegExp');
import('form.validation.FormValidatorEmail');
import('form.validation.FormValidatorUrl');
import('form.validation.FormValidatorAlphaNum');
import('form.validation.FormValidatorInSet');
import('form.validation.FormValidatorArray');
import('form.validation.FormValidatorArrayCustom');
import('form.validation.FormValidatorLocale');
import('form.validation.FormValidatorLocaleEmail');
import('form.validation.FormValidatorLength');
import('form.validation.FormValidatorCustom');
import('form.validation.FormValidatorCaptcha');
import('form.validation.FormValidatorPost');

class FormValidator {

	/** The Form associated with the check */
	var $form;

	/** The name of the field */
	var $field;

	/** The type of check ("required" or "optional") */
	var $type;

	/** The error message associated with a validation failure */
	var $message;

	/**
	 * Constructor.
	 * @param $form Form the associated form
	 * @param $field string the name of the associated field
	 * @param $type string the type of check, either "required" or "optional"
	 * @param $message string the error message for validation failures (i18n key)
	 */
	function FormValidator(&$form, $field, $type, $message) {
		$this->form = &$form;
		$this->field = $field;
		$this->type = $type;
		$this->message = $message;
	}

	/**
	 * Check if field value is valid.
	 * Default check is that field is either optional or not empty.
	 * @return boolean
	 */
	function isValid() {
		return $this->type == 'optional' || trim($this->form->getData($this->field)) != '';
	}

	/**
	 * Check if field value is empty and optional.
	 * @return boolean
	 */
	function isEmptyAndOptional() {
		return $this->type == 'optional' && trim($this->form->getData($this->field)) == '';
	}

	/**
	 * Get the field associated with the check.
	 * @return string
	 */
	function getField() {
		return $this->field;
	}

	/**
	 * Get the error message associated with a failed validation check.
	 * @return string
	 */
	function getMessage() {
		return Locale::translate($this->message);
	}

}

?>
