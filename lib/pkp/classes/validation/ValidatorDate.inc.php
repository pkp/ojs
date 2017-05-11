<?php

/**
 * @file classes/validation/ValidatorDate.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ValidatorDate
 * @ingroup validation
 * @see Validator
 *
 * @brief Validation check for email addresses.
 */

import('lib.pkp.classes.validation.ValidatorRegExp');

define('DATE_FORMAT_ISO', 0x01);
define('VALIDATOR_DATE_SCOPE_DAY', 1);
define('VALIDATOR_DATE_SCOPE_MONTH', 2);
define('VALIDATOR_DATE_SCOPE_YEAR', 4);

class ValidatorDate extends ValidatorRegExp {
	/**
	 * Constructor.
	 */
	function __construct($dateFormat = DATE_FORMAT_ISO) {
		parent::__construct(ValidatorDate::getRegexp($dateFormat));
	}


	//
	// Implement abstract methods from Validator
	//
	/**
	 * @see Validator::isValid()
	 * @param $value mixed
	 * @param $minScope int The minimum date resolution allowed, e.g. VALIDATOR_DATE_SCOPE_MONTH will allow YYYY-MM or YYYY-MM-DD, but not YYYY
	 * @param $maxScope int The maximum date resolution allowed, e.g. VALIDATOR_DATE_SCOPE_MONTH will allow YYYY or YYYY-MM, but not YYYY-MM-DD
	 * @return boolean
	 */
	function isValid($value, $minScope = VALIDATOR_DATE_SCOPE_YEAR, $maxScope = VALIDATOR_DATE_SCOPE_DAY) {
		if (!parent::isValid($value)) return false;
		
		if ($minScope < $maxScope) return false;

		$dateMatches = $this->getMatches();
		if (isset($dateMatches['month'])) {
			if (($dateMatches['month'] >= 1 && $dateMatches['month'] <= 12) || $maxScope == VALIDATOR_DATE_SCOPE_YEAR ) {
				if (isset($dateMatches['day'])) {
					return (checkdate($dateMatches['month'], $dateMatches['day'], $dateMatches['year']) && $maxScope == VALIDATOR_DATE_SCOPE_DAY);
				} else {
					return $maxScope < VALIDATOR_DATE_SCOPE_YEAR && $minScope > VALIDATOR_DATE_SCOPE_DAY;
				}
			} else {
				return false;
			}
		} else {
			return ($minScope == VALIDATOR_DATE_SCOPE_YEAR);
		}
	}


	//
	// Public static methods
	//
	/**
	 * Return the regex for a date check. This can be called
	 * statically.
	 * @param $dateFormat integer one of the DATE_FORMAT_* ids.
	 * @return string
	 */
	function getRegexp($dateFormat = DATE_FORMAT_ISO) {
		switch ($dateFormat) {
			case DATE_FORMAT_ISO:
				return '/(?P<year>\d{4})(?:-(?P<month>\d{2})(?:-(?P<day>\d{2}))?)?/';
				break;

			default:
				// FIXME: Additional date formats can be
				// added to the case list as required.
				assert(false);
		}
	}
}
?>
