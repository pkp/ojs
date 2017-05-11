<?php

/**
 * @file tests/classes/metadata/MetadataDescriptionDummyAdapterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataDescriptionDummyAdapterTest
 * @ingroup tests_classes_metadata
 * @see MetadataDescriptionDummyAdapter
 *
 * @brief Test class for MetadataDescriptionDummyAdapter.
 */


require_mock_env('env1');

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema');
import('lib.pkp.classes.metadata.MetadataDescriptionDummyAdapter');
import('lib.pkp.tests.classes.metadata.TestSchema');

class MetadataDescriptionDummyAdapterTest extends PKPTestCase {
	/**
	 * @covers MetadataDescriptionDummyAdapter
	 */
	public function testMetadataDescriptionDummyAdapter() {
		$schema = 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema';

		// Instantiate a test description.
		$originalDescription = new MetadataDescription($schema, ASSOC_TYPE_CITATION);
		$originalDescription->addStatement('source', $originalTitle = 'original source');
		$originalDescription->addStatement('article-title', $originalTitle = 'original title');

		// Test constructor.
		$adapter = new MetadataDescriptionDummyAdapter($originalDescription);
		self::assertEquals(ASSOC_TYPE_CITATION, $adapter->getAssocType());
		self::assertEquals($schema, $adapter->getMetadataSchemaName());
		self::assertInstanceOf('Nlm30CitationSchema', $adapter->getMetadataSchema());

		// Test metadata injection.
		$sourceDescription = new MetadataDescription($schema, ASSOC_TYPE_CITATION);
		$sourceDescription->addStatement('article-title', $injectedTitle = 'injected title');
		$resultDescription =& $adapter->injectMetadataIntoDataObject($sourceDescription, $originalDescription);
		$expectedResult = array(
			'source' => array(
				'en_US' => 'original source'
			),
			'article-title' => array(
				'en_US' => 'injected title'
			)
		);
		self::assertEquals($expectedResult, $resultDescription->getStatements());

		// Test meta-data extraction.
		$extractedDescription = $adapter->extractMetadataFromDataObject($originalDescription);
		self::assertEquals($originalDescription, $extractedDescription);

		// Test meta-data field names (only test one field of each
		// category (translated or not) so that the test doesn't
		// break when we expand the NLM schema).
		$fieldNames = $adapter->getMetadataFieldNames(false);
		self::assertTrue(in_array('date', $fieldNames)); // NB: no namespace pre-fix in this case!
		$fieldNames = $adapter->getMetadataFieldNames(true);
		self::assertTrue(in_array('article-title', $fieldNames));
	}
}
?>
