<?php
/**
 * @defgroup plugins_citationParser_freecite_tests_filter FreeCite Filter Test Suite
 */

/**
 * @file plugins/citationParser/freecite/tests/filter/FreeciteRawCitationNlm30CitationSchemaFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FreeciteRawCitationNlm30CitationSchemaFilterTest
 * @ingroup plugins_citationParser_freecite_tests_filter
 * @see FreeciteRawCitationNlm30CitationSchemaFilter
 *
 * @brief Tests for the FreeciteRawCitationNlm30CitationSchemaFilter class.
 */

import('lib.pkp.tests.plugins.citationParser.Nlm30CitationSchemaParserFilterTestCase');
import('lib.pkp.plugins.citationParser.freecite.filter.FreeciteRawCitationNlm30CitationSchemaFilter');

class FreeciteRawCitationNlm30CitationSchemaFilterTest extends Nlm30CitationSchemaParserFilterTestCase {
	/**
	 * @covers FreeciteRawCitationNlm30CitationSchemaFilter
	 */
	public function testExecute() {
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
				'testInput' => 'Crackton, P. (1987). The Loonie: God\'s long-awaited gift to colourful pocket change? Canadian Change, 64(7), 34-37.',
				'testOutput' => array(
					'[@publication-type]' => 'journal',
					'article-title' => 'The Loonie: God\'s long-awaited gift to colourful pocket change',
					'person-group[@person-group-type="author"]' => array(
						array('given-names' => array('P'), 'surname' => 'Crackton')
					),
					'fpage' => 34,
					'lpage' => 37,
					'date' => '1987',
					'source' => 'Canadian Change',
					'issue' => '7',
					'volume' => '64'
				)
			),
			array(
				'testInput' => 'Iyer, Naresh Sundaram. "A Family of Dominance Filters for Multiple Criteria Decision Making: Choosing the Right Filter for a Decision Situation." Ph.D. diss., Ohio State University, 2001.',
				'testOutput' => array(
					'[@publication-type]' => 'thesis',
					'source' => 'A Family of Dominance Filters for Multiple Criteria Decision Making: Choosing the Right Filter for a Decision Situation',
					'person-group[@person-group-type="author"]' => array(
						array('given-names' => array('Iyer', 'Naresh'), 'surname' => 'Sundaram')
					),
					'date' => '2001',
					'comment' => 'Ph.D',
					'publisher-name' => 'Ohio State University'
				)
			)
		);

		$filter = new FreeciteRawCitationNlm30CitationSchemaFilter(PersistableFilter::tempGroup(
				'primitive::string',
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)'));
		$this->assertNlm30CitationSchemaFilter($testCitations, $filter);
	}

	/**
	 * @covers FreeciteRawCitationNlm30CitationSchemaFilter
	 */
	public function testExecuteWithWebServiceError() {
		$constructor = array(PersistableFilter::tempGroup(
				'primitive::string',
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)'));
		$this->assertWebServiceError('FreeciteRawCitationNlm30CitationSchemaFilter', $constructor);
	}

	/**
	 * @see Nlm30CitationSchemaParserFilterTestCase::testAllCitationsWithThisParser()
	 */
	public function testAllCitationsWithThisParser() {
		$filter = new FreeciteRawCitationNlm30CitationSchemaFilter(PersistableFilter::tempGroup(
				'primitive::string',
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)'));
		parent::_testAllCitationsWithThisParser($filter);
	}
}
?>
