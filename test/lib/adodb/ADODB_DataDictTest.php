<?php
// Include required classes and functions
require_once('test/OjsTestCase.php');

/**
 * Runs tests on the mysql and postgres compatibility
 * layer over ADODB_DataDict.
 *
 * We run all mysql tests first and then the postgres
 * tests to avoid expensive database connection switches.
 *
 * @author Florian Grandel
 */
class ADODB_DataDictTest extends OjsTestCase {
	/**
	 * @covers AdodbMysqlCompatDict::__call
	 * @covers AdodbDatadictCompatDelegate::_GetCharSetDelegate
	 * @covers AdodbDatadictCompatDelegate::_SetCharSetDelegate
	 */
	public function testSetAndGetCharsetOnMySQL() {
		$this->setTestConfiguration(self::CONFIG_MYSQL);
		$adoConn = &DBConnection::getConn();
		$dataDict = &$adoConn->NewDataDictionary();

		$dataDict->SetCharSet('ascii');
		self::assertEquals('ascii', $dataDict->GetCharSet());
		$dataDict->SetCharSet('utf8');
		self::assertEquals('utf8', $dataDict->GetCharSet());
	}

	/**
	 * We test CreateDatabase() without options as
	 * OJS does not use the options parameter.
	 *
	 * @covers AdodbMysqlCompatDict::CreateDatabase
	 */
	public function testCreateDatabaseOnMySQL() {
		$this->setTestConfiguration(self::CONFIG_MYSQL);
		$adoConn = &DBConnection::getConn();
		$dataDict = &$adoConn->NewDataDictionary();

		// Test without a character set
		$sql = $dataDict->CreateDatabase('test_database');
		self::assertEquals(array('CREATE DATABASE test_database'), $sql);

		// Test with character set
		$dataDict->SetCharSet('utf8');
		$sql = $dataDict->CreateDatabase('test_database');
		self::assertEquals(array('CREATE DATABASE test_database DEFAULT CHARACTER SET utf8'), $sql);
	}

	/**
	 * @covers AdodbMysqlCompatDict::_TableSQL
	 */
	public function test_TableSQLOnMySQL() {
		$this->setTestConfiguration(self::CONFIG_MYSQL);
		$adoConn = &DBConnection::getConn();
		$dataDict = &$adoConn->NewDataDictionary();

		// Test data
		$lines = array(
			'TEST_TABLE_ID' => 'test_table_id            BIGINT NOT NULL AUTO_INCREMENT',
			'SOME_FOREIGN_KEY_ID' => 'some_foreign_key_id      BIGINT NOT NULL',
			'SOME_STRING' => 'some_string              VARCHAR(6) NOT NULL DEFAULT \'\''
		);
		$pkey = array( 'test_table_id' );

		// First try without a character set
		$sql = $dataDict->_TableSQL('test_table', $lines, $pkey, array());
		$expectedResult = array(
			"CREATE TABLE test_table (\n" .
			"test_table_id            BIGINT NOT NULL AUTO_INCREMENT,\n" .
			"some_foreign_key_id      BIGINT NOT NULL,\n" .
			"some_string              VARCHAR(6) NOT NULL DEFAULT '',\n" .
			"                 PRIMARY KEY (test_table_id)\n" .
			")"
		);
		self::assertEquals($expectedResult, $sql);

		// Now try with a character set
		$dataDict->SetCharSet('utf8');
		$sql = $dataDict->_TableSQL('test_table', $lines, $pkey, array());
		$expectedResult[0] .= ' DEFAULT CHARACTER SET utf8';
		self::assertEquals($expectedResult, $sql);
	}

	/**
	 * @covers AdodbPostgres7CompatDict::CreateDatabase
	 * @covers AdodbPostgres7CompatDict::__call
	 */
	public function testCreateDatabaseOnPostgres() {
		$this->setTestConfiguration(self::CONFIG_PGSQL);
		$adoConn = &DBConnection::getConn();
		$dataDict = &$adoConn->NewDataDictionary();

		// Test without a character set
		$sql = $dataDict->CreateDatabase('test_database');
		self::assertEquals(array('CREATE DATABASE test_database TEMPLATE template0'), $sql);

		// Test with character set
		$dataDict->SetCharSet('UTF8');
		$sql = $dataDict->CreateDatabase('test_database');
		self::assertEquals(array('CREATE DATABASE test_database WITH ENCODING \'UTF8\' TEMPLATE template0'), $sql);
	}
}
?>
