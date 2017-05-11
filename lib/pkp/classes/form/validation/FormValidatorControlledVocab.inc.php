<?php

/**
 * @file classes/form/validation/FormValidatorControlledVocab.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorControlledVocab
 * @ingroup form_validation
 *
 * @brief Form validation check that checks if value is within a certain set.
 */

import('lib.pkp.classes.form.validation.FormValidator');

class FormValidatorControlledVocab extends FormValidator {
	/**
	 * Constructor.
	 * @param $form Form the associated form
	 * @param $field string the name of the associated field
	 * @param $type string the type of check, either "required" or "optional"
	 * @param $message string the error message for validation failures (i18n key)
	 * @param $symbolic string
	 * @param $assocType int
	 * @param $assocId int
	 */
	function __construct(&$form, $field, $type, $message, $symbolic, $assocType, $assocId) {
		import('lib.pkp.classes.validation.ValidatorControlledVocab');
		$validator = new ValidatorControlledVocab($symbolic, $assocType, $assocId);
		parent::__construct($form, $field, $type, $message, $validator);
	}
}

?>
