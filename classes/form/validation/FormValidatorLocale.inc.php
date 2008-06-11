<?php

/**
 * @file FormValidatorLocale.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package form.validation
 * @class FormValidatorLocale
 *
 * Class to represent a form validation check for localized fields.
 *
 * $Id$
 */

class FormValidatorLocale extends FormValidator {
	/**
	 * Check if field value is valid.
	 * Default check is that field is either optional or not empty for the primary locale.
	 * @return boolean
	 */
	function isValid() {
		$primaryLocale = Locale::getPrimaryLocale();
		$value = $this->form->getData($this->field);
		return $this->type == 'optional' || (is_array($value) && !empty($value[$primaryLocale]));
	}

	/**
	 * Check if field value is empty and optional.
	 * @return boolean
	 */
	function isEmptyAndOptional() {
		$value = $this->form->getData($this->field);
		return $this->type == 'optional' && empty($value);
	}

	/**
	 * Get the field associated with the check.
	 * @return string
	 */
	function getField() {
		return $this->field;
	}

	/**
	 * Get the error message associated with a failed validation check.
	 * @return string
	 */
	function getMessage() {
		$primaryLocale = Locale::getPrimaryLocale();
		$allLocales = Locale::getAllLocales();
		return parent::getMessage() . ' (' . $allLocales[$primaryLocale] . ')';
	}

}

?>
