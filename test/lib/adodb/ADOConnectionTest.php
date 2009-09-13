<?php
// Include required classes and functions
require_once('test/OjsTestCase.php');

class ADOConnectionTest extends OjsTestCase {
	/**
	 * @covers AdodbMysqlCompat
	 * @covers AdodbConnectionCompatDelegate::AdodbConnectionCompatDelegate
	 * @covers AdodbConnectionCompatDelegate::_ExecuteDelegate
	 * @covers ADOConnection::Execute
	 * @covers DBConnection::getNumQueries
	 */
	public function testCountQueriesWhileExecuteOnMySQL() {
		$this->setTestConfiguration(self::CONFIG_MYSQL);
		$this->internalTestCountQueriesWhileExecute();
	}

	/**
	 * @covers AdodbPostgres7Compat
	 * @covers AdodbConnectionCompatDelegate::AdodbConnectionCompatDelegate
	 * @covers AdodbConnectionCompatDelegate::_ExecuteDelegate
	 * @covers ADOConnection::Execute
	 * @covers DBConnection::getNumQueries
	 */
	public function testCountQueriesWhileExecuteOnPostgres() {
		$this->setTestConfiguration(self::CONFIG_PGSQL);
		$this->internalTestCountQueriesWhileExecute();
	}

	private function internalTestCountQueriesWhileExecute() {
    	// Get database connection
    	$ojsConn = &DBConnection::getInstance();
		$adoConn = &$ojsConn->getConn();

    	// Reset num queries for a defined test environment
		$adoConn->numQueries = 0;

    	// Run two queries and see whether the counter works
		$testSql = "SELECT * FROM test_table";
    	$adoConn->Execute($testSql);
    	self::assertEquals(1, $ojsConn->getNumQueries());
    	$adoConn->Execute($testSql);
    	self::assertEquals(2, $ojsConn->getNumQueries());
    }
}
?>
