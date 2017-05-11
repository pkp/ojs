<?php

/**
 * @file classes/form/validation/FormValidatorListbuilder.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorListbuilder
 * @ingroup form_validation
 *
 * @brief Form validation check that checks if the JSON value submitted unpacks into something that
 * contains at least one valid user id.
 */

import('lib.pkp.classes.form.validation.FormValidator');

class FormValidatorListbuilder extends FormValidator {

	/* outcome of validation after callbacks */
	var $_valid = false;


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
	 * Check the number of listbuilder rows and ensure that at least one exists.
	 * @see FormValidator::isValid()
	 * @return boolean
	 */
	function isValid() {
		$value = json_decode($this->getFieldValue());
		return (is_object($value) && isset($value->numberOfRows) && $value->numberOfRows > 0);
	}
}

?>
