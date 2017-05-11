<?php

/**
 * @file classes/validation/ValidatorUrl.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ValidatorUrl
 * @ingroup validation
 * @see Validator
 *
 * @brief Validation check for URLs.
 */

import('lib.pkp.classes.validation.ValidatorUri');

class ValidatorUrl extends ValidatorUri {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct(ValidatorUrl::_getAllowedSchemes());
	}

	//
	// Public static methods
	//
	/**
	 * @see ValidatorUri::getRegexp()
	 * @param $allowedSchemes Array of strings to restrict accepted schemes to defined set, or null for any allowed
	 * @return string
	 */
	static function getRegexp($allowedSchemes = null) {
		if ($allowedSchemes === null) $allowedSchemes = self::_getAllowedSchemes();
		else $allowedSchemes = array_intersect(self::_getAllowedSchemes(), $allowedSchemes);
		return parent::getRegexp($allowedSchemes);
	}

	//
	// Private static methods
	//
	/**
	 * Return allowed schemes
	 * @return array
	 */
	static function _getAllowedSchemes() {
		return array('http', 'https', 'ftp');
	}
}

?>
