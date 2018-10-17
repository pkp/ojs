<?php

/**
 * @file tests/functional/TestTestingEnvironmentTest.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalTestingEnvironmentTest
 * @ingroup tests_functional
 * @see FunctionalTestingEnvironment
 *
 * @brief Integration/Functional test to make sure the testing environment is working.
 */


import('lib.pkp.tests.WebTestCase');

class FunctionalTestingEnvironmentTest extends WebTestCase {

	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array('sessions');
	}

	/**
	 * Just login as admin user to test the testing environment.
	 */
	function testTestingEnvironment() {
		$this->logIn('admin', 'admin');
		$this->logOut();
	}
}
