<?php

/**
 * @file classes/filter/SetFilterSetting.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SetFilterSetting
 * @ingroup classes_filter
 *
 * @brief Class that describes a configurable filter setting which must
 *  be one of a given set of values.
 */

import('lib.pkp.classes.filter.FilterSetting');
import('lib.pkp.classes.form.validation.FormValidatorInSet');

class SetFilterSetting extends FilterSetting {
	/** @var array */
	var $_acceptedValues;

	/**
	 * Constructor
	 *
	 * @param $name string
	 * @param $displayName string
	 * @param $validationMessage string
	 * @param $acceptedValues array
	 * @param $required boolean
	 */
	function __construct($name, $displayName, $validationMessage, $acceptedValues, $required = FORM_VALIDATOR_REQUIRED_VALUE) {
		$this->_acceptedValues = $acceptedValues;
		parent::__construct($name, $displayName, $validationMessage, $required);
	}

	//
	// Getters and Setters
	//
	/**
	 * Set the accepted values
	 * @param $acceptedValues array
	 */
	function setAcceptedValues($acceptedValues) {
		$this->_acceptedValues = $acceptedValues;
	}

	/**
	 * Get the accepted values
	 * @return array
	 */
	function getAcceptedValues() {
		return $this->_acceptedValues;
	}

	/**
	 * Get a localized array of the accepted
	 * values with the key being the accepted value
	 * and the value being a localized display name.
	 *
	 * NB: The standard implementation displays the
	 * accepted values.
	 *
	 * Can be overridden by sub-classes.
	 *
	 * @return array
	 */
	function getLocalizedAcceptedValues() {
		return array_combine($this->getAcceptedValues(), $this->getAcceptedValues());
	}

	//
	// Implement abstract template methods from FilterSetting
	//
	/**
	 * @see FilterSetting::getCheck()
	 */
	function &getCheck(&$form) {
		$check = new FormValidatorInSet($form, $this->getName(), $this->getRequired(), $this->getValidationMessage(), $this->getAcceptedValues());
		return $check;
	}
}
?>
