<?php

/**
 * @defgroup validation Validation
 * Implements validation operations.
 */

/**
 * @file classes/validation/Validator.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Validator
 * @ingroup validation
 *
 * @brief Abstract class that represents a validation check. This class and its
 *  sub-classes can be used outside a form validation context which enables
 *  re-use of complex validation code.
 */

class Validator {
	/**
	 * Constructor.
	 */
	function __construct() {
	}

	/**
	 * Check whether the given value is valid.
	 * @param $value mixed the value to be checked
	 * @return boolean
	 */
	function isValid($value) {
		// To be implemented by sub-classes
		assert(false);
	}
}

?>
