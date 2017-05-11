<?php

/**
 * @file classes/form/validation/FormValidatorRegExp.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorRegExp
 * @ingroup form_validation
 *
 * @brief Form validation check using a regular expression.
 */

import('lib.pkp.classes.form.validation.FormValidator');

class FormValidatorRegExp extends FormValidator {
	/**
	 * Constructor.
	 * @param $form Form the associated form
	 * @param $field string the name of the associated field
	 * @param $type string the type of check, either "required" or "optional"
	 * @param $message string the error message for validation failures (i18n key)
	 * @param $regExp string the regular expression (PCRE form)
	 */
	function __construct(&$form, $field, $type, $message, $regExp) {
		import('lib.pkp.classes.validation.ValidatorRegExp');
		$validator = new ValidatorRegExp($regExp);
		parent::__construct($form, $field, $type, $message, $validator);
	}
}

?>
