<?php

/**
 * @file tests/classes/citation/CitationListTokenizerFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CitationListTokenizerFilterTest
 * @ingroup tests_classes_citation
 * @see CitationListTokenizerFilter
 *
 * @brief Test class for CitationListTokenizerFilter.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.citation.CitationListTokenizerFilter');

class CitationListTokenizerFilterTest extends PKPTestCase {
	/**
	 * @covers CitationListTokenizerFilter
	 */
	public function testCitationListTokenizerFilter() {
		$tokenizer = new CitationListTokenizerFilter();
		$rawCitationList = "\t1. citation1\n\n2 citation2\r\n 3) citation3\n[4]citation4";
		$expectedResult = array(
			'citation1',
			'citation2',
			'citation3',
			'citation4'
		);
		self::assertEquals($expectedResult, $tokenizer->process($rawCitationList));

		$rawCitationList = '';
		self::assertEquals(array(), $tokenizer->process($rawCitationList));
	}
}
?>
