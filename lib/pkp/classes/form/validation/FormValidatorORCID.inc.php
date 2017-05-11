<?php

/**
 * @file classes/form/validation/FormValidatorORCID.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorORCID
 * @ingroup form_validation
 *
 * @brief Form validation check for ORCID iDs.
 */

import('lib.pkp.classes.form.validation.FormValidator');

class FormValidatorORCID extends FormValidator {
	/**
	 * Constructor.
	 * @param $form Form the associated form
	 * @param $field string the name of the associated field
	 * @param $type string the type of check, either "required" or "optional"
	 * @param $message string the error message for validation failures (i18n key)
	 */
	function __construct($form, $field, $type, $message) {
		import('lib.pkp.classes.validation.ValidatorORCID');
		$validator = new ValidatorORCID();
		parent::__construct($form, $field, $type, $message, $validator);
	}
}

?>
