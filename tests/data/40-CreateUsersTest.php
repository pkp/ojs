<?php

/**
 * @file tests/data/40-CreateUsersTest.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CreateUsersTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create test users
 */

import('lib.pkp.tests.data.PKPCreateUsersTest');

class CreateUsersTest extends PKPCreateUsersTest {
	/**
	 * Create users
	 */
	function testCreateUsers() {
		$this->createUsers(array(
			array(
				'username' => 'rvaca',
				'givenName' => 'Ramiro',
				'familyName' => 'Vaca',
				'country' => 'Mexico',
				'affiliation' => 'Universidad Nacional Autónoma de México',
				'roles' => array('Server manager'),
			),
			array(
				'username' => 'dbarnes',
				'givenName' => 'Daniel',
				'familyName' => 'Barnes',
				'country' => 'Australia',
				'affiliation' => 'University of Melbourne',
				'roles' => array('Server editor'),
			),
			array(
				'username' => 'dbuskins',
				'givenName' => 'David',
				'familyName' => 'Buskins',
				'country' => 'United States',
				'affiliation' => 'University of Chicago',
				'roles' => array('Series editor'),
			),
			array(
				'username' => 'sberardo',
				'givenName' => 'Stephanie',
				'familyName' => 'Berardo',
				'country' => 'Canada',
				'affiliation' => 'University of Toronto',
				'roles' => array('Series editor'),
			),
			array(
				'username' => 'minoue',
				'givenName' => 'Minoti',
				'familyName' => 'Inoue',
				'country' => 'Japan',
				'affiliation' => 'Kyoto University',
				'roles' => array('Series editor'),
			),
		));
	}
}
