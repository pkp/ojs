<?php

/**
 * @file classes/filter/BooleanFilterSetting.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BooleanFilterSetting
 * @ingroup classes_filter
 *
 * @brief Class that describes a configurable filter setting which must
 *  be either true or false.
 */

import('lib.pkp.classes.filter.FilterSetting');
import('lib.pkp.classes.form.validation.FormValidatorBoolean');

class BooleanFilterSetting extends FilterSetting {
	/**
	 * Constructor
	 *
	 * @param $name string
	 * @param $displayName string
	 * @param $validationMessage string
	 */
	function __construct($name, $displayName, $validationMessage) {
		parent::__construct($name, $displayName, $validationMessage, FORM_VALIDATOR_OPTIONAL_VALUE);
	}


	//
	// Implement abstract template methods from FilterSetting
	//
	/**
	 * @see FilterSetting::getCheck()
	 */
	function &getCheck(&$form) {
		$check = new FormValidatorBoolean($form, $this->getName(), $this->getValidationMessage());
		return $check;
	}
}
?>
