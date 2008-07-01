<?php

/**
 * @file classes/form/validation/FormValidatorEmail.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorEmail
 * @ingroup form_validation
 * @see FormValidator
 *
 * @brief Form validation check for email addresses.
 */

// $Id$


import('form.validation.FormValidatorRegExp');

class FormValidatorLocaleEmail extends FormValidatorEmail {
	/**
	 * Validate against a localized email field.
	 * @return boolean
	 */
	function isValid() {
		if ($this->isEmptyAndOptional()) return true;
		$value = $this->form->getData($this->field);
		$primaryLocale = Locale::getPrimaryLocale();
		return is_array($value) && !empty($value[$primaryLocale]) && String::regexp_match($this->regExp, $value[$primaryLocale]);
	}

	function getMessage() {
		$primaryLocale = Locale::getPrimaryLocale();
		$allLocales = Locale::getAllLocales();
		return parent::getMessage() . ' (' . $allLocales[$primaryLocale] . ')';
	}
}

?>
