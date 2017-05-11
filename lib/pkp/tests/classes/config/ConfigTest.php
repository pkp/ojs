<?php

/**
 * @file tests/classes/config/ConfigTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConfigTest
 * @ingroup tests_classes_config
 * @see Config
 *
 * @brief Tests for the Config class.
 */


import('lib.pkp.tests.PKPTestCase');

class ConfigTest extends PKPTestCase {
	/**
	 * @covers Config::getConfigFileName
	 */
	public function testGetDefaultConfigFileName() {
		$expectedResult = Core::getBaseDir(). DIRECTORY_SEPARATOR. 'config.inc.php';
		self::assertEquals($expectedResult, Config::getConfigFileName());
	}

	/**
	 * @covers Config::setConfigFileName
	 */
	public function testSetConfigFileName() {
		Config::setConfigFileName('some_config');
		self::assertEquals('some_config', Config::getConfigFileName());
	}

	/**
	 * @depends testSetConfigFileName
	 * @expectedException PHPUnit_Framework_Error
	 * @covers Config::reloadData
	 */
	public function testReloadDataWithNonExistentConfigFile() {
		$this->expectOutputRegex('/Cannot read configuration file some_config/');
		Config::setConfigFileName('some_config');
		Config::reloadData();
	}

	/**
	 * @depends testSetConfigFileName
	 * @covers Config::reloadData
	 */
	public function testReloadDataAndGetData() {
		$this->markTestSkipped();
		Config::setConfigFileName('lib/pkp/tests/config/config.mysql.inc.php');
		$result = Config::reloadData();
		$expectedResult = array(
			'installed' => true,
			'base_url' => 'http://pkp.sfu.ca/ojs',
			'session_cookie_name' => 'OJSSID',
			'session_lifetime' => 30,
			'scheduled_tasks' => false,
			'date_format_trunc' => '%m-%d',
			'date_format_short' => '%Y-%m-%d',
			'date_format_long' => '%B %e, %Y',
			'datetime_format_short' => '%Y-%m-%d %I:%M %p',
			'datetime_format_long' => '%B %e, %Y - %I:%M %p',
			'disable_path_info' => false,
		);

		// We'll only check part of the configuration data to
		// keep the test less verbose.
		self::assertEquals($expectedResult, $result['general']);

		$result = &Config::getData();
		self::assertEquals($expectedResult, $result['general']);
	}

	/**
	 * @depends testReloadDataAndGetData
	 * @covers Config::getVar
	 * @covers Config::getData
	 */
	public function testGetVar() {
		self::assertEquals('mysql', Config::getVar('database', 'driver'));
		self::assertNull(Config::getVar('general', 'non-existent-config-var'));
		self::assertNull(Config::getVar('non-existent-config-section', 'non-existent-config-var'));
	}

	/**
	 * @depends testGetVar
	 * @covers Config::getVar
	 * @covers Config::getData
	 */
	public function testGetVarFromOtherConfig() {
		Config::setConfigFileName('lib/pkp/tests/config/config.pgsql.inc.php');
		self::assertEquals('pgsql', Config::getVar('database', 'driver'));
	}
}

?>
