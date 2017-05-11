<?php

/**
 * @file tests/classes/db/DBDataXMLParserTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DBDataXMLParserTest
 * @ingroup tests_classes_db
 * @see DBDataXMLParser
 *
 * @brief Tests for the DBDataXMLParser class.
 */

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.db.DBDataXMLParser');

class DBDataXMLParserTest extends DatabaseTestCase {
	/**
	 * Test SQL queries embedded in XML data descriptor.
	 * @covers DBDataXMLParser::parseData
	 */
	public function testParseSQLData() {
		$dataXMLParser = new DBDataXMLParser();
		$dataXMLParser->setDBConn(DBConnection::getConn());
		$sql = $dataXMLParser->parseData(dirname(__FILE__) . '/data-sql.xml');
		switch (Config::getVar('database', 'driver')) {
			case 'mysqli':
			case 'mysql':
				$this->assertEquals(array('RAW QUERY', 'RAW MYSQL QUERY'), $sql);
				break;
			case 'postgres':
				$this->assertEquals(array('RAW QUERY', 'RAW POSTGRESQL QUERY'), $sql);
				break;
			default: $this->fail('Unknown DB driver.');
		}
	}

	/**
	 * Test table data embedded in XML data descriptor.
	 * @covers DBDataXMLParser::parseData
	 */
	public function testParseTableData() {
		$dataXMLParser = new DBDataXMLParser();
		$dataXMLParser->setDBConn(DBConnection::getConn());
		$sql = $dataXMLParser->parseData(dirname(__FILE__) . '/data-table.xml');
		$this->assertEquals(array(
			'INSERT INTO mytable (default_col, notnullable_default_col, nullable_default_col, notnullable_col, nullable_col, normal_col) VALUES (\'MY_DEFAULT\', \'\', NULL, \'\', NULL, \'MY_VALUE_1\')',
			'INSERT INTO mytable (default_col, notnullable_default_col, nullable_default_col, notnullable_col, nullable_col, normal_col) VALUES (\'DEFAULT_OVERRIDDEN\', \'\', NULL, \'\', NULL, \'MY_VALUE_2\')'
		), $sql);
	}

	/**
	 * Test SQL DDL changes embedded in XML data descriptor.
	 * @covers DBDataXMLParser::parseData
	 */
	public function testParseSQLDDL() {
		$dataXMLParser = new DBDataXMLParser();
		$dataXMLParser->setDBConn(DBConnection::getConn());
		switch (Config::getVar('database', 'driver')) {
			case 'mysql':
			case 'mysqli':
				$this->assertEquals(
					array(
						array('DROP TABLE IF EXISTS myDropTable'),
						array('ALTER TABLE myModTable DROP COLUMN myDropColumn'),
						array('RENAME TABLE sessions TO myNewTableName'),
					),
					$dataXMLParser->parseData(dirname(__FILE__) . '/data-ddl.xml')
				);
				break;
			case 'postgres':
				$this->markTestSkipped('PostgreSQL/ADODB weirdness prevents this test.');
				break;
			default: $this->fail('Unknown DB driver.');
		}
	}
}

?>
