<?php

/**
 * @file classes/validation/ValidatorRegExp.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ValidatorRegExp
 * @ingroup validation
 *
 * @brief Validation check using a regular expression.
 */

import ('lib.pkp.classes.validation.Validator');

class ValidatorRegExp extends Validator {

	/** @var The regular expression to match against the field value */
	var $_regExp;

	/** @var The matches for further (optional) processing by subclasses */
	var $_matches;

	/**
	 * Constructor.
	 * @param $regExp string the regular expression (PCRE form)
	 */
	function __construct($regExp) {
		parent::__construct();
		$this->_regExp = $regExp;
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
		return (boolean)PKPString::regexp_match_get($this->_regExp, $value, $this->_matches);
	}


	//
	// Protected methods for use by sub-classes
	//
	/**
	 * Returns the reg-ex matches (if any) after isValid() was called.
	 */
	function getMatches() {
		return $this->_matches;
	}
}

?>
