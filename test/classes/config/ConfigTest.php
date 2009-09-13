<?php
require_once 'test/OjsBaseTestCase.php';

class ConfigTest extends OjsBaseTestCase {
	/**
	 * @covers Config::getConfigFileName
	 */
	public function testGetDefaultConfigFileName() {
		$expectedResult = dirname(dirname(dirname(dirname(__FILE__)))). "\config.inc.php";
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
	public function testReloadDataWithNonExistantConfigFile() {
		$this->expectOutputString('<h1>Cannot read configuration file some_config</h1>');
		Config::reloadData();
	}

	/**
	 * @depends testSetConfigFileName
	 * @covers Config::reloadData
	 */
	public function testReloadDataAndGetData() {
		Config::setConfigFileName('test/config.mysql.inc.php');
		$result = Config::reloadData();
		$expectedResult = array(
    		'installed' => false,
    		'base_url' => 'http://pkp.sfu.ca/ojs',
    		'registry_dir' => 'registry',
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
		Config::setConfigFileName('test/config.pgsql.inc.php');
		self::assertEquals('pgsql', Config::getVar('database', 'driver'));
	}
}
?>
