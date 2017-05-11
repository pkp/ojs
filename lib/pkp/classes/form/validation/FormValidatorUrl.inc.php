<?php

/**
 * @file classes/form/validation/FormValidatorUrl.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorUrl
 * @ingroup form_validation
 * @see FormValidator
 *
 * @brief Form validation check for URLs.
 */

import('lib.pkp.classes.form.validation.FormValidator');
import('lib.pkp.classes.validation.ValidatorUrl');

class FormValidatorUrl extends FormValidator {
	/**
	 * Constructor.
	 * @param $form Form the associated form
	 * @param $field string the name of the associated field
	 * @param $type string the type of check, either "required" or "optional"
	 * @param $message string the error message for validation failures (i18n key)
	 */
	function __construct(&$form, $field, $type, $message) {
		$validator = new ValidatorUrl();
		parent::__construct($form, $field, $type, $message, $validator);
		array_push($form->cssValidation[$field], 'url');
	}
}

?>
