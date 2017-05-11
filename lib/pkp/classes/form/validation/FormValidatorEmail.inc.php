<?php

/**
 * @file classes/form/validation/FormValidatorEmail.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorEmail
 * @ingroup form_validation
 * @see FormValidator
 *
 * @brief Form validation check for email addresses.
 */

import('lib.pkp.classes.form.validation.FormValidatorRegExp');
import('lib.pkp.classes.validation.ValidatorEmail');

class FormValidatorEmail extends FormValidator {
	/**
	 * Constructor.
	 * @param $form Form the associated form
	 * @param $field string the name of the associated field
	 * @param $type string the type of check, either "required" or "optional"
	 * @param $message string the error message for validation failures (i18n key)
	 */
	function __construct(&$form, $field, $type = 'optional', $message = 'email.invalid') {
		$validator = new ValidatorEmail();
		parent::__construct($form, $field, $type, $message, $validator);
		array_push($form->cssValidation[$field], 'email');
	}

	function getMessage() {
		return __($this->_message, array('email' => $this->getFieldValue()));
	}
}

?>
