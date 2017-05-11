<?php

/**
 * @file plugins/metadata/nlm30/tests/filter/Openurl10Nlm30CitationSchemaCrosswalkFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Openurl10Nlm30CitationSchemaCrosswalkFilterTest
 * @ingroup plugins_metadata_nlm30_tests_filter
 * @see Openurl10Nlm30CitationSchemaCrosswalkFilter
 *
 * @brief Tests for the Openurl10Nlm30CitationSchemaCrosswalkFilter class.
 */

import('lib.pkp.plugins.metadata.nlm30.filter.Openurl10Nlm30CitationSchemaCrosswalkFilter');
import('lib.pkp.plugins.metadata.nlm30.tests.filter.Nlm30Openurl10CrosswalkFilterTest');

class Openurl10Nlm30CitationSchemaCrosswalkFilterTest extends Nlm30Openurl10CrosswalkFilterTest {
	/**
	 * @covers Openurl10Nlm30CitationSchemaCrosswalkFilter
	 * @covers Nlm30Openurl10CrosswalkFilter
	 */
	public function testExecute() {
		$this->markTestSkipped('Weird class interaction with ControlledVocabEntryDAO leads to failure');

		$openurl10Description = $this->getTestOpenurl10Description();
		$nlm30Description = $this->getTestNlm30Description();

		// Properties that are not part of the OpenURL
		// description must be removed from the NLM description
		// before we compare the two.
		self::assertTrue($nlm30Description->removeStatement('person-group[@person-group-type="editor"]'));
		self::assertTrue($nlm30Description->removeStatement('source', 'de_DE'));
		self::assertTrue($nlm30Description->removeStatement('article-title', 'de_DE'));
		self::assertTrue($nlm30Description->removeStatement('publisher-loc'));
		self::assertTrue($nlm30Description->removeStatement('publisher-name'));
		self::assertTrue($nlm30Description->removeStatement('pub-id[@pub-id-type="doi"]'));
		self::assertTrue($nlm30Description->removeStatement('pub-id[@pub-id-type="pmid"]'));
		self::assertTrue($nlm30Description->removeStatement('uri'));
		self::assertTrue($nlm30Description->removeStatement('comment'));
		self::assertTrue($nlm30Description->removeStatement('annotation'));

		$filter = new Openurl10Nlm30CitationSchemaCrosswalkFilter();
		self::assertEquals($nlm30Description, $filter->execute($openurl10Description));
	}
}
?>
