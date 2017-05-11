<?php
/**
 * @defgroup plugins_citationParser_parscit_tests_filter ParsCit Filter Test Suite
 */

/**
 * @file plugins/citationParser/parscit/tests/filter/ParscitRawCitationNlm30CitationSchemaFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ParscitRawCitationNlm30CitationSchemaFilterTest
 * @ingroup plugins_citationParser_parscit_tests_filter
 * @see ParscitRawCitationNlm30CitationSchemaFilter
 *
 * @brief Tests for the ParscitRawCitationNlm30CitationSchemaFilter class.
 */

import('lib.pkp.tests.plugins.citationParser.Nlm30CitationSchemaParserFilterTestCase');
import('lib.pkp.plugins.citationParser.parscit.filter.ParscitRawCitationNlm30CitationSchemaFilter');

class ParscitRawCitationNlm30CitationSchemaFilterTest extends Nlm30CitationSchemaParserFilterTestCase {
	/**
	 * @covers ParscitRawCitationNlm30CitationSchemaFilter
	 */
	public function testExecute() {
		$this->markTestSkipped('Unreliable web service.');

		$testCitations = array(
			array(
				'testInput' => 'Sheril, R. D. (1956). The terrifying future: Contemplating color television. San Diego: Halstead.',
				'testOutput' => array(
					'source' => 'The terrifying future: Contemplating color television',
					'person-group[@person-group-type="author"]' => array(
						array('given-names' => array('R', 'D'), 'surname' => 'Sheril')
					),
					'date' => '1956',
					'publisher-name' => 'Halstead',
					'publisher-loc' => 'San Diego',
					'[@publication-type]' => NLM30_PUBLICATION_TYPE_BOOK
				)
			),
			array(
				'testInput' => 'Crackton, P. (1987). The Loonie: God\'s long-awaited gift to colourful pocket change? Canadian Change, 64(7), 34–37.',
				'testOutput' => array(
					'article-title' => 'The Loonie: God&apos;s long-awaited gift to colourful pocket change',
					'person-group[@person-group-type="author"]' => array(
						array('given-names' => array('P'), 'surname' => 'Crackton')
					),
					'fpage' => 34,
					'lpage' => 37,
					'date' => '1987',
					'source' => 'Canadian Change',
					'volume' => '64',
					'issue' => '7',
					'[@publication-type]' => NLM30_PUBLICATION_TYPE_JOURNAL
				)
			)
		);

		$filter = new ParscitRawCitationNlm30CitationSchemaFilter(PersistableFilter::tempGroup(
				'primitive::string',
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)'));
		$this->assertNlm30CitationSchemaFilter($testCitations, $filter);
	}

	/**
	 * @covers ParaciteRawCitationNlm30CitationSchemaFilter
	 */
	public function testAllCitationsWithThisParser() {
		$filter = new ParscitRawCitationNlm30CitationSchemaFilter(PersistableFilter::tempGroup(
				'primitive::string',
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)'));
		parent::_testAllCitationsWithThisParser($filter);
	}

	/**
	 * @covers ParscitRawCitationNlm30CitationSchemaFilter
	 */
	public function testExecuteWithWebServiceError() {
		$constructor = array(PersistableFilter::tempGroup(
				'primitive::string',
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)'));
		$this->assertWebServiceError('ParscitRawCitationNlm30CitationSchemaFilter', $constructor);
	}
}
?>
