<?php

/**
 * @file tests/data/59-LogOutTest.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LogOutTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Log out
 */

import('lib.pkp.tests.WebTestCase');

class LogOutTest extends WebTestCase {
	/**
	 * Log out.
	 */
	function testLogOut() {
		$this->logOut();
	}
}
