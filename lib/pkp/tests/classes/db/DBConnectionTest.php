<?php

/**
 * @file tests/classes/db/DBConnectionTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DBConnectionTest
 * @ingroup tests_classes_db
 * @see DBConnection
 *
 * @brief Tests for the DBConnectionTest class.
 */

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.db.DBConnection');

class DBConnectionTest extends DatabaseTestCase {
	const CONFIG_PGSQL = 'pgsql';

	/**
	 * @covers DBConnection::__construct
	 * @covers DBConnection::initDefaultDBConnection
	 * @covers DBConnection::initConn
	 */
	public function testInitDefaultDBConnection() {
		$conn = new DBConnection();
		$dbConn = $conn->getDBConn();
		switch (Config::getVar('database', 'driver')) {
			case 'mysqli':
				self::assertInstanceOf('ADODB_mysqli', $dbConn);
				break;
			case 'mysql':
				self::assertInstanceOf('ADODB_mysql', $dbConn);
				break;
			case 'postgres':
				self::assertInstanceOf('ADODB_postgres64', $dbConn);
				break;
			default:
				$this->fail('Unknown DB driver.');
				return false;
		}
		$conn->disconnect();
		unset($conn);
	}

	/**
	 * @covers DBConnection::__construct
	 * @covers DBConnection::initDefaultDBConnection
	 * @covers DBConnection::initConn
	 * @covers AdodbPostgres7Compat::AdodbPostgres7Compat
	 */
	public function testInitPostgresDBConnection() {
		$this->markTestSkipped();
		$this->setTestConfiguration(self::CONFIG_PGSQL);
		$conn = new DBConnection();
		$dbConn = $conn->getDBConn();
		self::assertInstanceOf('ADODB_postgres7', $dbConn);
		$conn->disconnect();
		unset($conn);
	}

	/**
	 * @covers DBConnection::__construct
	 * @covers DBConnection::initCustomDBConnection
	 * @covers DBConnection::initConn
	 */
	public function testInitCustomDBConnection() {
		$this->markTestSkipped();
		$this->setTestConfiguration(self::CONFIG_PGSQL);
		$conn = new DBConnection('sqlite', 'localhost', 'ojs', 'ojs', 'ojs', true, false, false);
		$dbConn = $conn->getDBConn();
		self::assertInstanceOf('ADODB_sqlite', $dbConn);
		$conn->disconnect();
		unset($conn);
	}
}

?>
