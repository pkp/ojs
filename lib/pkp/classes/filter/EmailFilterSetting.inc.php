<?php

/**
 * @file classes/filter/EmailFilterSetting.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailFilterSetting
 * @ingroup classes_filter
 *
 * @brief Class that describes a configurable filter setting which
 *  must be an email.
 */

import('lib.pkp.classes.filter.FilterSetting');
import('lib.pkp.classes.form.validation.FormValidatorEmail');

class EmailFilterSetting extends FilterSetting {
	/**
	 * Constructor
	 *
	 * @param $name string
	 * @param $displayName string
	 * @param $validationMessage string
	 * @param $required boolean
	 */
	function __construct($name, $displayName, $validationMessage, $required = FORM_VALIDATOR_REQUIRED_VALUE) {
		parent::__construct($name, $displayName, $validationMessage, $required);
	}

	//
	// Implement abstract template methods from FilterSetting
	//
	/**
	 * @see FilterSetting::getCheck()
	 */
	function &getCheck(&$form) {
		$check = new FormValidatorEmail($form, $this->getName(), $this->getRequired(), $this->getValidationMessage());
		return $check;
	}
}
?>
