<?php

/**
 * @file classes/form/validation/FormValidatorPost.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorPost
 * @ingroup form_validation
 *
 * @brief Form validation check to make sure the form is POSTed.
 */

import ('lib.pkp.classes.form.validation.FormValidator');

class FormValidatorPost extends FormValidator {
	/**
	 * Constructor.
	 * @param $form Form
	 * @param $message string the locale key to use (optional)
	 */
	function __construct(&$form, $message = 'form.postRequired') {
		parent::__construct($form, 'dummy', FORM_VALIDATOR_REQUIRED_VALUE, $message);
	}


	//
	// Public methods
	//
	/**
	 * Check if form was posted.
	 * overrides FormValidator::isValid()
	 * @return boolean
	 */
	function isValid() {
		return Request::isPost();
	}
}

?>
