<?php
require_once 'test/OjsBaseTestCase.php';

abstract class OjsTestCase extends OjsBaseTestCase {
	const
		// Available test configurations
		CONFIG_PGSQL = 'test/config.pgsql.inc.php',
		CONFIG_MYSQL = 'test/config.mysql.inc.php',

		// Test phases
		TEST_SET_UP = 1,
		TEST_TEAR_DOWN = 2;

	private
		$_testSchemaFile = null,
		$_testSchema = null;


	public function setUp() {
		// By default we use the MySQL test configuration
		$this->setTestConfiguration(self::CONFIG_MYSQL);

		// Database setup
		$this->installTestSchema(self::TEST_SET_UP);
	}

	public function tearDown() {
		// Clean up database
		$this->installTestSchema(self::TEST_TEAR_DOWN);
	}

	/**
	 * Set a non-default test configuration
	 * @param $configFile string
	 */
	protected function setTestConfiguration($configFile) {
		// Avoid unnecessary configuration switches.
		if (Config::getConfigFileName() != $configFile) {
			// Switch the configuration file
    		Config::setConfigFileName($configFile);

    		// Re-open the database connection with the
    		// new configuration.
    		DBConnection::getInstance(new DBConnection());
		}
	}

	/**
	 * (Un-)install the test schema (if it exists)
	 * @param $testPhase string
	 */
	private function installTestSchema($testPhase) {
		if (is_readable($this->getTestSchemaFile($testPhase))) {
			if (is_null($this->_testSchema)) {
				import('classes.db.compat.AdodbXmlschemaCompat');
				$this->_testSchema = &new AdodbXmlschemaCompat(
    				DBConnection::getConn(),
    				Config::getVar('i18n', 'database_charset')
    			);
			}
			$this->_testSchema->ParseSchema($this->getTestSchemaFile($testPhase));
			$this->_testSchema->ExecuteSchema();
		}
	}

	/**
	 * Identify the canonical file name of
	 * the test-specific schema.
	 * @param $testPhase string
	 * @return string
	 */
	private function getTestSchemaFile($testPhase) {
		if (is_null($this->_testSchemaFile)) {
    		$testName = get_class($this);
    		$loadedFiles = get_included_files();
    		foreach ($loadedFiles as $loadedFile) {
    			if (strpos($loadedFile, $testName) !== FALSE) {
    				$testFile = $loadedFile;
    				break;
    			}
    		}
    		$this->_testSchemaFile = substr($testFile, 0, -4);
		}

		switch($testPhase) {
			case self::TEST_SET_UP:
				return $this->_testSchemaFile . '.setUp.xml';
				break;

			case self::TEST_TEAR_DOWN:
				return $this->_testSchemaFile . '.tearDown.xml';
				break;

			default:
				fatalError('OJS Test Case: Unknown test phase');
		}
	}
}
?>
