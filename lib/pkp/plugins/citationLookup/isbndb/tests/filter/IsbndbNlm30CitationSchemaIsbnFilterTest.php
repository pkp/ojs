<?php

/**
 * @file plugins/citationLookup/isbndb/tests/filter/IsbndbNlm30CitationSchemaIsbnFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IsbndbNlm30CitationSchemaIsbnFilterTest
 * @ingroup plugins_citationLookup_isbndb_tests_filter
 *
 * @brief Tests for IsbndbNlm30CitationSchemaIsbnFilter
 */


require_mock_env('env2');

import('lib.pkp.plugins.citationLookup.isbndb.filter.IsbndbNlm30CitationSchemaIsbnFilter');
import('lib.pkp.plugins.citationLookup.isbndb.tests.filter.IsbndbNlm30CitationSchemaFilterTest');

class IsbndbNlm30CitationSchemaIsbnFilterTest extends IsbndbNlm30CitationSchemaFilterTest {

	/**
	 * @covers IsbndbNlm30CitationSchemaIsbnFilter
	 * @covers IsbndbNlm30CitationSchemaFilter
	 */
	public function testExecute() {
		$this->markTestSkipped('ISBNDB API key daily limit too low.');

		// Test data
		$isbnSearchTest = array(
			'testInput' => array(
				'person-group[@person-group-type="author"]' => array(
					0 => array('given-names' => array('John'), 'surname' => 'Willinsky')
				),
				'source' => array(
					'en_US' => 'After literacy'
				)
			),
			'testOutput' => '9780820452425' // ISBN
		);

		// Build the test array
		$citationFilterTests = array(
			$isbnSearchTest
		);

		// Test the filter
		$filter = new IsbndbNlm30CitationSchemaIsbnFilter(PersistableFilter::tempGroup(
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)',
				'primitive::string'));

		$apiKey = $this->_getIsbndbApiKey();
		if (!$apiKey) $this->markTestSkipped('ISBNDB API key not available.');

		$filter->setData('apiKey', $apiKey);
		$this->assertNlm30CitationSchemaFilter($citationFilterTests, $filter);
	}
}
?>
