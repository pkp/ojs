<?php

/**
 * @file plugins/citationLookup/isbndb/tests/filter/IsbndbNlm30CitationSchemaFilterTest.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IsbndbNlm30CitationSchemaFilterTest
 * @ingroup plugins_citationLookup_isbndb_tests_filter
 *
 * @brief Basic configuration for Isbndb tests
 */

import('lib.pkp.plugins.metadata.nlm30.tests.filter.Nlm30CitationSchemaFilterTestCase');

class IsbndbNlm30CitationSchemaFilterTest extends Nlm30CitationSchemaFilterTestCase {
	/**
	 * Get the ISBNDB API key
	 * @return string
	 */
	protected function _getIsbndbApiKey() {
		return getenv('ISBNDB_TEST_APIKEY');
	}
}
?>
