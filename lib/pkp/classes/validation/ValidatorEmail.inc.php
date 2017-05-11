<?php

/**
 * @file classes/validation/ValidatorEmail.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ValidatorEmail
 * @ingroup validation
 * @see Validator
 *
 * @brief Validation check for email addresses.
 */

import('lib.pkp.classes.validation.ValidatorRegExp');

class ValidatorEmail extends ValidatorRegExp {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct(ValidatorEmail::getRegexp());
	}


	//
	// Public static methods
	//
	/**
	 * Return the regex for an email check.
	 * @return string
	 */
	static function getRegexp() {
		return '/^' . PCRE_EMAIL_ADDRESS . '$/i';
	}
}

?>
