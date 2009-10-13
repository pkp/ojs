<?php
// Include required classes and functions
require_once('test/OjsTestCase.php');
require_once('classes/db/DBConnection.inc.php');

class DBConnectionTest extends OjsTestCase {
	/**
	 * @covers DBConnection::DBConnection
	 * @covers DBConnection::initDefaultDBConnection
	 * @covers DBConnection::initConn
	 * @covers AdodbMysqlCompat::AdodbMysqlCompat
	 */
    public function testInitDefaultDBConnection() {
    	$conn = new DBConnection();
    	$dbConn = $conn->getDBConn();
    	self::assertType('AdodbMysqlCompat', $dbConn);
    	$conn->disconnect();
    	unset($conn);
    }

	/**
	 * @covers DBConnection::DBConnection
	 * @covers DBConnection::initDefaultDBConnection
	 * @covers DBConnection::initConn
	 * @covers AdodbPostgres7Compat::AdodbPostgres7Compat
	 */
    public function testInitPostgresDBConnection() {
    	$this->setTestConfiguration(self::CONFIG_PGSQL);
    	$conn = new DBConnection();
    	$dbConn = $conn->getDBConn();
    	self::assertType('AdodbPostgres7Compat', $dbConn);
    	$conn->disconnect();
    	unset($conn);
    }

    /**
	 * @covers DBConnection::DBConnection
	 * @covers DBConnection::initCustomDBConnection
	 * @covers DBConnection::initConn
	 */
    public function testInitCustomDBConnection() {
    	$this->setTestConfiguration(self::CONFIG_PGSQL);
    	$conn = new DBConnection('sqlite', 'localhost', 'ojs', 'ojs', 'ojs', true, false, false);
    	$dbConn = $conn->getDBConn();
    	self::assertType('ADODB_sqlite', $dbConn);
    	$conn->disconnect();
    	unset($conn);
    }
}
?>
