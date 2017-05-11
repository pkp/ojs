<?php
/**
 * @file tests/mock/env1/MockValidation.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Validation
 * @ingroup tests_mock_env1
 * @see PKPPageRouterTest
 *
 * @brief Mock implementation of the Validation class for the PKPPageRouterTest
 */


class Validation {
	static $_isLoggedIn = false;

	static function isLoggedIn() {
		return Validation::$_isLoggedIn;
	}

	static function setIsLoggedIn($isLoggedIn) {
		Validation::$_isLoggedIn = $isLoggedIn;
	}
}

?>
