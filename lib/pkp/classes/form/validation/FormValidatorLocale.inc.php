<?php

/**
 * @file classes/form/validation/FormValidatorLocale.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorLocale
 * @ingroup form_validation
 *
 * @brief Class to represent a form validation check for localized fields.
 */

class FormValidatorLocale extends FormValidator {
	/** @var string Symbolic name of the locale to require */
	var $_requiredLocale;

	/**
	 * Constructor.
	 * @param $form Form the associated form
	 * @param $field string the name of the associated field
	 * @param $type string the type of check, either "required" or "optional"
	 * @param $message string the error message for validation failures (i18n key)
	 * @param $validator Validator the validator used to validate this form field (optional)
	 * @param $requiredLocale The name of the required locale, i.e. en_US
	 */
	function __construct(&$form, $field, $type, $message, $requiredLocale = null, $validator = null) {
		parent::__construct($form, $field, $type, $message, $validator);
		if ($requiredLocale === null) $requiredLocale = AppLocale::getPrimaryLocale();
		$this->_requiredLocale = $requiredLocale;
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the error message associated with a failed validation check.
	 * @see FormValidator::getMessage()
	 * @return string
	 */
	function getMessage() {
		$allLocales = AppLocale::getAllLocales();
		return parent::getMessage() . ' (' . $allLocales[$this->_requiredLocale] . ')';
	}

	//
	// Protected helper methods
	//
	/**
	 * @see FormValidator::getFieldValue()
	 * @return mixed
	 */
	function getFieldValue() {
		$form =& $this->getForm();
		$data = $form->getData($this->getField());

		$fieldValue = '';
		if (is_array($data) && isset($data[$this->_requiredLocale])) {
			$fieldValue = $data[$this->_requiredLocale];
			if (is_scalar($fieldValue)) $fieldValue = trim((string)$fieldValue);
		}
		return $fieldValue;
	}
}

?>
