<?php
// Include required classes and functions
require_once('test/OjsTestCase.php');

/**
 * Runs tests on the mysql and postgres compatibility
 * layer over ADOConnection.
 *
 * We run all mysql tests first and then the postgres
 * tests to avoid expensive database connection switches.
 *
 * @author Florian Grandel
 */
class ADOConnectionTest extends OjsTestCase {
	/**
	 * @covers AdodbMysqlCompat
	 * @covers AdodbConnectionCompatDelegate
	 * @covers ADOConnection::Execute
	 * @covers DBConnection::getNumQueries
	 */
	public function testCountQueriesWhileExecuteOnMySQL() {
		$this->setTestConfiguration(self::CONFIG_MYSQL);
		$this->internalTestCountQueriesWhileExecute();
	}

	/**
	 * @covers AdodbMysqlCompat::NewDataDictionary
	 * @covers AdodbConnectionCompatDelegate::_NewDataDictionaryDelegate
	 * @covers AdodbMysqlCompatDict
	 * @covers AdodbDatadictCompatDelegate
	 */
	public function testNewDataDictionaryOnMySQL() {
		$this->setTestConfiguration(self::CONFIG_MYSQL);
		$this->internalTestNewDataDictionary('mysql');
	}

	/**
	 * @covers AdodbMysqlCompat::SetCharSet
	 * @covers AdodbMysqlCompat::GetCharSet
	 */
	public function testSetAndGetCharSetOnMySQL() {
		$this->setTestConfiguration(self::CONFIG_MYSQL);
		$adoConn = &DBConnection::getConn();

		// Switching the character set with the database
		// connected should work normally.
		self::assertTrue($adoConn->SetCharSet('ascii'));
		self::assertEquals('ascii', $adoConn->GetCharSet());
		self::assertTrue($adoConn->SetCharSet(Config::getVar('i18n', 'connection_charset')));
		self::assertEquals(Config::getVar('i18n', 'connection_charset'), $adoConn->GetCharSet());

		// Switching the character set with the database
		// disconnected should return false.
		$adoConn->disconnect();
		self::assertFalse($adoConn->SetCharSet(Config::getVar('i18n', 'connection_charset')));
		self::assertFalse($adoConn->GetCharSet());
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

	/**
	 * @covers AdodbPostgres7Compat::NewDataDictionary
	 * @covers AdodbConnectionCompatDelegate::_NewDataDictionaryDelegate
	 * @covers AdodbPostgres7CompatDict
	 * @covers AdodbDatadictCompatDelegate
	 */
	public function testNewDataDictionaryOnPostgres() {
		$this->setTestConfiguration(self::CONFIG_PGSQL);
		$this->internalTestNewDataDictionary('postgres');
	}

	/**
	 * @covers ADODB_postgres7::SetCharSet
	 * @covers ADODB_postgres7::GetCharSet
	 */
	public function testSetAndGetCharSetOnPostgres() {
		$this->setTestConfiguration(self::CONFIG_PGSQL);
		$adoConn = &DBConnection::getConn();

		// Switching the character set with the database
		// connected should work normally.
		self::assertTrue($adoConn->SetCharSet('SQL_ASCII'));
		self::assertEquals('SQL_ASCII', $adoConn->GetCharSet());
		self::assertTrue($adoConn->SetCharSet(Config::getVar('i18n', 'connection_charset')));
		self::assertEquals(Config::getVar('i18n', 'connection_charset'), $adoConn->GetCharSet());

		// Trying to get the character set with the database
		// disconnected should return false.
		$adoConn->disconnect();
		self::assertFalse($adoConn->GetCharSet());
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

	private function internalTestNewDataDictionary($driver) {
		// Get database connection
		$adoConn = &DBConnection::getConn();

		// Get data dictionary instance
		$dataDict = &$adoConn->NewDataDictionary();

		// Test data dictionary
		$driverClass = ($driver == 'postgres' ? 'Postgres7' : 'Mysql');
		self::assertType(sprintf('Adodb%sCompatDict', $driverClass), $dataDict);
		self::assertEquals($driver, $dataDict->dataProvider);
		self::assertType(sprintf('Adodb%sCompat', $driverClass), $dataDict->connection);
		self::assertEquals(sprintf('ADODB%sCOMPATDICT', strtoupper($driverClass)), $dataDict->upperName);
		self::assertEquals($adoConn->nameQuote, $dataDict->quote);
		self::assertFalse(empty($dataDict->serverInfo));
		self::assertEquals($adoConn->serverInfo(), $dataDict->serverInfo);
	}
}
?>
