<?php
/**
 * @defgroup tests Tests
 * Tests and test framework for unit and integration tests.
 */

/**
 * @file tests/PKPTestCase.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPTestCase
 * @ingroup tests
 *
 * @brief Class that implements functionality common to all PKP unit test cases.
 *
 * NB: PHPUnit 3.x requires PHP 5.2 or later so we can use PHP5 constructs.
 */

// Include PHPUnit
import('lib.pkp.tests.PKPTestHelper');

abstract class PKPTestCase extends PHPUnit_Framework_TestCase {
	private
		$daoBackup = array(),
		$registryBackup = array(),
		$mockedRegistryKeys = array();

	/**
	 * Override this method if you want to backup/restore
	 * DAOs before/after the test.
	 * @return array A list of DAO names to backup and restore.
	 */
	protected function getMockedDAOs() {
		return array();
	}

	/**
	 * Override this method if you want to backup/restore
	 * registry entries before/after the test.
	 * @return array A list of registry keys to backup and restore.
	 */
	protected function getMockedRegistryKeys() {
		return $this->mockedRegistryKeys;
	}

	/**
	 * @copydoc PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		$this->setBackupGlobals(true);

		// Rather than using "include_once()", ADOdb uses
		// a global variable to maintain the information
		// whether its library has been included before (wtf!).
		// This causes problems with PHPUnit as PHPUnit will
		// delete all global state between two consecutive
		// tests to isolate tests from each other.
		if(function_exists('_array_change_key_case')) {
			global $ADODB_INCLUDED_LIB;
			$ADODB_INCLUDED_LIB = 1;
		}
		Config::setConfigFileName(Core::getBaseDir(). DIRECTORY_SEPARATOR. 'config.inc.php');

		// Backup DAOs.
		foreach($this->getMockedDAOs() as $mockedDao) {
			$this->daoBackup[$mockedDao] = DAORegistry::getDAO($mockedDao);
		}

		// Backup registry keys.
		foreach($this->getMockedRegistryKeys() as $mockedRegistryKey) {
			$this->registryBackup[$mockedRegistryKey] = Registry::get($mockedRegistryKey);
		}
	}

	/**
	 * @copydoc PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown() {
		// Restore registry keys.
		foreach($this->getMockedRegistryKeys() as $mockedRegistryKey) {
			Registry::set($mockedRegistryKey, $this->registryBackup[$mockedRegistryKey]);
		}

		// Restore DAOs.
		foreach($this->getMockedDAOs() as $mockedDao) {
			DAORegistry::registerDAO($mockedDao, $this->daoBackup[$mockedDao]);
		}
	}

	/**
	 * @copydoc PHPUnit_Framework_TestCase::getActualOutput()
	 */
	public function getActualOutput() {
		// We do not want to see output.
		return '';
	}


	//
	// Protected helper methods
	//
	/**
	 * Set a non-default test configuration
	 * @param $config string the id of the configuration to use
	 * @param $configPath string (optional) where to find the config file, default: 'config'
	 * @param $dbConnect (optional) whether to try to re-connect the data base, default: true
	 */
	protected function setTestConfiguration($config, $configPath = 'config') {
		// Get the configuration file belonging to
		// this test configuration.
		$configFile = $this->getConfigFile($config, $configPath);

		// Avoid unnecessary configuration switches.
		if (Config::getConfigFileName() != $configFile) {
			// Switch the configuration file
			Config::setConfigFileName($configFile);
		}
	}

	/**
	 * Mock a web request.
	 *
	 * For correct timing you have to call this method
	 * in the setUp() method of a test after calling
	 * parent::setUp() or in a test method. You can also
	 * call this method as many times as necessary from
	 * within your test and you're guaranteed to receive
	 * a fresh request whenever you call it.
	 *
	 * And make sure that you merge any additional mocked
	 * registry keys with the ones returned from this class.
	 *
	 * @param $path string
	 * @param $userId int
	 *
	 * @return Request
	 */
	protected function mockRequest($path = 'index/test-page/test-op', $userId = null) {
		// Back up the default request.
		if (!isset($this->registryBackup['request'])) {
			$this->mockedRegistryKeys[] = 'request';
			$this->registryBackup['request'] = Registry::get('request');
		}

		// Create a test request.
		Registry::delete('request');
		$application = PKPApplication::getApplication();
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['PATH_INFO'] = $path;
		$request = $application->getRequest();
		import('classes.core.PageRouter');

		// Test router.
		$router = new PageRouter();
		$router->setApplication($application);
		import('lib.pkp.classes.core.Dispatcher');
		$dispatcher = new Dispatcher();
		$dispatcher->setApplication($application);
		$router->setDispatcher($dispatcher);
		$request->setRouter($router);

		// Test user.
		$session = $request->getSession();
		$session->setUserId($userId);

		return $request;
	}


	//
	// Private helper methods
	//
	/**
	 * Resolves the configuration id to a configuration
	 * file
	 * @param $config string
	 * @return string the resolved configuration file name
	 */
	private function getConfigFile($config, $configPath = 'config') {
		// Build the config file name.
		return './lib/pkp/tests/'.$configPath.'/config.'.$config.'.inc.php';
	}
}
?>
