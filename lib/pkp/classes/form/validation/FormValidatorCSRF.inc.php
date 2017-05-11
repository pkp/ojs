<?php

/**
 * @file classes/form/validation/FormValidatorCSRF.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorCSRF
 * @ingroup form_validation
 *
 * @brief Form validation check to make sure the CSRF token is correct.
 */

import ('lib.pkp.classes.form.validation.FormValidator');

class FormValidatorCSRF extends FormValidator {
	/**
	 * Constructor.
	 * @param $form Form
	 * @param $message string the locale key to use (optional)
	 */
	function __construct(&$form, $message = 'form.csrfInvalid') {
		parent::__construct($form, 'dummy', FORM_VALIDATOR_REQUIRED_VALUE, $message);
	}


	//
	// Public methods
	//
	/**
	 * Check if the CSRF token is correct.
	 * overrides FormValidator::isValid()
	 * @return boolean
	 */
	function isValid() {
		$request = PKPApplication::getRequest();
		return $request->checkCSRF();
	}
}

?>
