<?php

/**
 * @file classes/form/validation/FormValidatorUrl.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorUrl
 * @ingroup form_validation
 * @see FormValidator
 *
 * @brief Form validation check for URLs.
 */

// $Id$


import('form.validation.FormValidatorRegExp');

class FormValidatorUrl extends FormValidatorRegExp {
	function getRegexp() {
		return '/^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?(\/.)?/i';
	}

	/**
	 * Constructor.
	 * @see FormValidatorRegExp::FormValidatorRegExp()
	 */
	function FormValidatorUrl(&$form, $field, $type, $message) {
		parent::FormValidatorRegExp($form, $field, $type, $message, FormValidatorUrl::getRegexp());
	}
}

?>
