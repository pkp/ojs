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
				'roles' => array('Journal manager'),
			),
			array(
				'username' => 'dbarnes',
				'givenName' => 'Daniel',
				'familyName' => 'Barnes',
				'country' => 'Australia',
				'affiliation' => 'University of Melbourne',
				'roles' => array('Journal editor'),
			),
			array(
				'username' => 'dbuskins',
				'givenName' => 'David',
				'familyName' => 'Buskins',
				'country' => 'United States',
				'affiliation' => 'University of Chicago',
				'roles' => array('Section editor'),
			),
			array(
				'username' => 'sberardo',
				'givenName' => 'Stephanie',
				'familyName' => 'Berardo',
				'country' => 'Canada',
				'affiliation' => 'University of Toronto',
				'roles' => array('Section editor'),
			),
			array(
				'username' => 'minoue',
				'givenName' => 'Minoti',
				'familyName' => 'Inoue',
				'country' => 'Japan',
				'affiliation' => 'Kyoto University',
				'roles' => array('Section editor'),
			),
			array(
				'username' => 'jjanssen',
				'givenName' => 'Julie',
				'familyName' => 'Janssen',
				'country' => 'Netherlands',
				'affiliation' => 'Utrecht University',
				'roles' => array('Reviewer'),
			),
			array(
				'username' => 'phudson',
				'givenName' => 'Paul',
				'familyName' => 'Hudson',
				'country' => 'Canada',
				'affiliation' => 'McGill University',
				'roles' => array('Reviewer'),
			),
			array(
				'username' => 'amccrae',
				'givenName' => 'Aisla',
				'familyName' => 'McCrae',
				'country' => 'Canada',
				'affiliation' => 'University of Manitoba',
				'roles' => array('Reviewer'),
			),
			array(
				'username' => 'agallego',
				'givenName' => 'Adela',
				'familyName' => 'Gallego',
				'country' => 'United States',
				'affiliation' => 'State University of New York',
				'roles' => array('Reviewer'),
			),
			array(
				'username' => 'mfritz',
				'givenName' => 'Maria',
				'familyName' => 'Fritz',
				'country' => 'Belgium',
				'affiliation' => 'Ghent University',
				'roles' => array('Copyeditor'),
			),
			array(
				'username' => 'svogt',
				'givenName' => 'Sarah',
				'familyName' => 'Vogt',
				'country' => 'Chile',
				'affiliation' => 'Universidad de Chile',
				'roles' => array('Copyeditor'),
			),
			array(
				'username' => 'gcox',
				'givenName' => 'Graham',
				'familyName' => 'Cox',
				'country' => 'United States',
				'affiliation' => 'Duke University',
				'roles' => array('Layout Editor'),
			),
			array(
				'username' => 'shellier',
				'givenName' => 'Stephen',
				'familyName' => 'Hellier',
				'country' => 'South Africa',
				'affiliation' => 'University of Cape Town',
				'roles' => array('Layout Editor'),
			),
			array(
				'username' => 'cturner',
				'givenName' => 'Catherine',
				'familyName' => 'Turner',
				'country' => 'United Kingdom',
				'affiliation' => 'Imperial College London',
				'roles' => array('Proofreader'),
			),
			array(
				'username' => 'skumar',
				'givenName' => 'Sabine',
				'familyName' => 'Kumar',
				'country' => 'Singapore',
				'affiliation' => 'National University of Singapore',
				'roles' => array('Proofreader'),
			),
		));
	}
}
