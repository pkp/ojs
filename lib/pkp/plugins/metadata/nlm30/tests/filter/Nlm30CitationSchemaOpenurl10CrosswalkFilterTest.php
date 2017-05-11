<?php

/**
 * @file plugins/metadata/nlm30/tests/filter/Nlm30CitationSchemaOpenurl10CrosswalkFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationSchemaOpenurl10CrosswalkFilterTest
 * @ingroup plugins_metadata_nlm30_tests_filter
 * @see Nlm30CitationSchemaOpenurl10CrosswalkFilter
 *
 * @brief Tests for the Nlm30CitationSchemaOpenurl10CrosswalkFilter class.
 */

import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaOpenurl10CrosswalkFilter');
import('lib.pkp.plugins.metadata.nlm30.tests.filter.Nlm30Openurl10CrosswalkFilterTest');

class Nlm30CitationSchemaOpenurl10CrosswalkFilterTest extends Nlm30Openurl10CrosswalkFilterTest {
	/**
	 * @covers Nlm30CitationSchemaOpenurl10CrosswalkFilter
	 * @covers Nlm30Openurl10CrosswalkFilter
	 */
	public function testExecute() {
		$this->markTestSkipped('Weird class interaction with ControlledVocabEntryDAO leads to failure');

		$nlm30Description = $this->getTestNlm30Description();
		$openurl10Description = $this->getTestOpenurl10Description();

		$filter = new Nlm30CitationSchemaOpenurl10CrosswalkFilter();
		self::assertEquals($openurl10Description, $filter->execute($nlm30Description));
	}
}
?>
