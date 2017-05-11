<?php

/**
 * @file classes/validation/ValidatorISSN.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ValidatorISSN
 * @ingroup validation
 * @see Validator
 *
 * @brief Validation check for ISSNs.
 */

import('lib.pkp.classes.validation.ValidatorRegExp');

class ValidatorISSN extends ValidatorRegExp {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct(self::getRegexp());
	}


	//
	// Implement abstract methods from Validator
	//
	/**
	 * @see Validator::isValid()
	 * @param $value mixed
	 * @return boolean
	 */
	function isValid($value) {
		if (!parent::isValid($value)) return false;

		// Test the check digit
		$matches = $this->getMatches();
		$issn = $matches[1] . $matches[2];

		$check = 0;
		for ($i=0; $i<7; $i++) {
			$check += $issn[$i] * (8-$i);
		}
		$check = $check % 11;
		switch ($check) {
			case 0:
				$check = '0';
				break;
			case 1:
				$check = 'X';
				break;
			default:
				$check = (string) (11 - $check);
		}
		return ($issn[7] === $check);
	}

	//
	// Public static methods
	//
	/**
	 * Return the regex for an ISSN check. This can be called
	 * statically.
	 * @return string
	 */
	static function getRegexp() {
		return '/^(\d{4})-(\d{3}[\dX])$/';
	}
}

?>
