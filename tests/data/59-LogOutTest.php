<?php

/**
 * @file tests/data/59-LogOutTest.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssuesTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create issues
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
