<?php
// Include required classes and functions
require_once('test/OjsTestCase.php');
import('db.compat.AdodbXmlschemaCompat');

/**
 * Runs tests on the compatibility layer over adoSchema.
 *
 * @author Florian Grandel
 */
class adoSchemaTest extends OjsTestCase {
	/**
	 * @covers AdodbXmlschemaCompat
	 */
	public function testInstantiateAdoSchema() {
		$adoConn = &DBConnection::getConn();
		$schema = &new AdodbXmlschemaCompat($adoConn, Config::getVar('i18n', 'database_charset'));
		self::assertType('AdodbXmlschemaCompat', $schema);
		self::assertType('AdodbMysqlCompatDict', $schema->dict);
		self::assertEquals(Config::getVar('i18n', 'database_charset'), $schema->dict->GetCharSet());
	}
}
?>
