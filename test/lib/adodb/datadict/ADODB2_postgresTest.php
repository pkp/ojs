<?php
// Where do we get our test data from?
define('DATABASE_XML_FILE', 'test/dbscripts/xml/test_schema.xml');

// Which is our test configuration?
define('CONFIG_FILE', 'test/config.pgsql.inc.php');

// Disable sessions
define('SESSION_DISABLE_INIT', 1);

// Change to basedir
chdir(dirname(dirname(dirname(dirname(dirname(__FILE__))))));

// Include required classes and functions
require_once('PHPUnit/Framework.php');
require_once('includes/driver.inc.php');
require_once('adodb/adodb-xmlschema.inc.php');

class ADODB_postgresTest extends PHPUnit_Framework_TestCase {
	var $schema;

	public function setUp() {
    	$dbconn = &DBConnection::getConn();
    	$this->schema = &new adoSchema($dbconn, Config::getVar('i18n', 'database_charset'));

    	/* $dbdict = &NewDataDictionary($this->dbconn);
		$dbdict->SetCharSet(Config::getVar('i18n', 'database_charset')); */
	}

    public function test_recreate_copy_table() {
    	$expectedResult = array(
            "CREATE TABLE test_table (\n" .
            "id                       SERIAL,\n" .
            "foreign_key_id           INT8 NOT NULL,\n" .
            "float                    FLOAT8,\n" .
            "flag                     SMALLINT,\n" .
            "string                   VARCHAR(6) DEFAULT '' NOT NULL,\n" .
            "text                     TEXT,\n" .
            "                 PRIMARY KEY (id)\n" .
            ")",
            "CREATE INDEX index ON test_table (foreign_key_id)"
		);
    	$sql = $this->schema->parseSchema(DATABASE_XML_FILE);
		self::assertEquals($expectedResult, $sql);
    	//$this->schema->ExecuteSchema();
    }

    public function tearDown() {
    	$this->schema->destroy();
    }
}
?>