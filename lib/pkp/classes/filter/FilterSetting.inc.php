<?php

/**
 * @file classes/filter/FilterSetting.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilterSetting
 * @ingroup classes_filter
 *
 * @brief Class that describes a configurable filter setting.
 */

import('lib.pkp.classes.form.validation.FormValidator');

class FilterSetting {
	/** @var string the (internal) name of the setting */
	var $_name;

	/** @var string the supported transformation */
	var $_displayName;

	/** @var string */
	var $_validationMessage;

	/** @var boolean */
	var $_required;

	/** @var boolean */
	var $_isLocalized;

	/**
	 * Constructor
	 *
	 * @param $name string
	 * @param $displayName string
	 * @param $validationMessage string
	 * @param $required string
	 * @param $isLocalized boolean
	 */
	function __construct($name, $displayName, $validationMessage, $required = FORM_VALIDATOR_REQUIRED_VALUE, $isLocalized = false) {
		$this->setName($name);
		$this->setDisplayName($displayName);
		$this->setValidationMessage($validationMessage);
		$this->setRequired($required);
		$this->setIsLocalized($isLocalized);
	}

	//
	// Setters and Getters
	//
	/**
	 * Set the setting name
	 * @param $name string
	 */
	function setName($name) {
		$this->_name = $name;
	}

	/**
	 * Get the setting name
	 * @return string
	 */
	function getName() {
		return $this->_name;
	}

	/**
	 * Set the display name
	 * @param $displayName string
	 */
	function setDisplayName($displayName) {
		$this->_displayName = $displayName;
	}

	/**
	 * Get the display name
	 * @return string
	 */
	function getDisplayName() {
		return $this->_displayName;
	}

	/**
	 * Set the validation message
	 * @param $validationMessage string
	 */
	function setValidationMessage($validationMessage) {
		$this->_validationMessage = $validationMessage;
	}

	/**
	 * Get the validation message
	 * @return string
	 */
	function getValidationMessage() {
		return $this->_validationMessage;
	}

	/**
	 * Set the required flag
	 * @param $required string
	 */
	function setRequired($required) {
		$this->_required = $required;
	}

	/**
	 * Get the required flag
	 * @return string
	 */
	function getRequired() {
		return $this->_required;
	}

	/**
	 * Set the localization flag
	 * @param $isLocalized boolean
	 */
	function setIsLocalized($isLocalized) {
		$this->_isLocalized = $isLocalized;
	}

	/**
	 * Get the localization flag
	 * @return boolean
	 */
	function getIsLocalized() {
		return $this->_isLocalized;
	}


	//
	// Protected Template Methods
	//
	/**
	 * Get the form validation check
	 * @return FormValidator
	 */
	function &getCheck(&$form) {
		// A validator is only required if this setting is mandatory.
		if ($this->getRequired() == FORM_VALIDATOR_OPTIONAL_VALUE) {
			$nullVar = null;
			return $nullVar;
		}

		// Instantiate a simple form validator.
		$check = new FormValidator($form, $this->getName(), $this->getRequired(), $this->getValidationMessage());
		return $check;
	}
}
?>
