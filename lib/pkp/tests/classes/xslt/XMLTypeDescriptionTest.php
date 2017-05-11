<?php

/**
 * @file tests/classes/xslt/XMLTypeDescriptionTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class XMLTypeDescriptionTest
 * @ingroup tests_classes_xslt
 * @see XMLTypeDescription
 *
 * @brief Test class for XMLTypeDescription.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.xslt.XMLTypeDescription');

class XMLTypeDescriptionTest extends PKPTestCase {

	/**
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	function tearDown() {
		PKPTestHelper::xdebugScream(true);
	}

	/**
	 * @covers XMLTypeDescription
	 */
	public function testInstantiateAndCheck() {
		$this->markTestSkipped();
		// Xdebug's scream parameter will disable the @ operator
		// that we need for XML validation.
		PKPTestHelper::xdebugScream(false);

		// Test with dtd validation
		$typeDescription = new XMLTypeDescription('dtd');
		$testXmlDom = new DOMDocument();
		$testXmlDom->load(dirname(__FILE__).'/dtdsample-valid.xml');
		self::assertTrue($typeDescription->isCompatible($testXmlDom));
		$testXmlDom->load(dirname(__FILE__).'/dtdsample-invalid.xml');
		self::assertFalse($typeDescription->isCompatible($testXmlDom));

		// Test with xsd validation
		$typeDescription = new XMLTypeDescription('schema('.dirname(__FILE__).'/xsdsample.xsd)');
		$testXmlDom = new DOMDocument();
		$testXmlDom->load(dirname(__FILE__).'/xsdsample-valid.xml');
		self::assertTrue($typeDescription->isCompatible($testXmlDom));
		$testXmlDom->load(dirname(__FILE__).'/xsdsample-invalid.xml');
		self::assertFalse($typeDescription->isCompatible($testXmlDom));

		// Test with rng validation
		$typeDescription = new XMLTypeDescription('relax-ng('.dirname(__FILE__).'/rngsample.rng)');
		$testXmlDom = new DOMDocument();
		$testXmlDom->load(dirname(__FILE__).'/rngsample-valid.xml');
		self::assertTrue($typeDescription->isCompatible($testXmlDom));
		$testXmlDom->load(dirname(__FILE__).'/rngsample-invalid.xml');
		self::assertFalse($typeDescription->isCompatible($testXmlDom));

		// Try passing in the document as a string
		$document =
			'<addressBook>
			  <card>
			    <name>John Smith</name>
			    <email>js@example.com</email>
			  </card>
			  <card>
			    <name>Fred Bloggs</name>
			    <email>fb@example.net</email>
			  </card>
			</addressBook>';
		self::assertTrue($typeDescription->isCompatible($document));


		// Test without schema validation
		$typeDescription = new XMLTypeDescription('*');
		$testXmlDom = new DOMDocument();
		$testXmlDom->load(dirname(__FILE__).'/rngsample-valid.xml');
		self::assertTrue($typeDescription->isCompatible($testXmlDom));
		$testXmlDom->load(dirname(__FILE__).'/rngsample-invalid.xml');
		self::assertTrue($typeDescription->isCompatible($testXmlDom));
	}

	/**
	 * @covers XMLTypeDescription
	 * @expectedException PHPUnit_Framework_Error
	 */
	public function testInstantiateWithInvalidTypeDescriptor1() {
		// Type name is not fully qualified.
		$typeDescription = new XMLTypeDescription('Nlm30CitationSchema(CITATION)');
	}
}
?>
