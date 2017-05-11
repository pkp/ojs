<?php
/**
 * @defgroup plugins_citationParser_paracite_tests_filter ParaCite Filter Test Suite
 */

/**
 * @file plugins/citationParser/paracite/tests/filter/ParaciteRawCitationNlm30CitationSchemaFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ParaciteRawCitationNlm30CitationSchemaFilterTest
 * @ingroup plugins_citationParser_paracite_tests_filter
 * @see ParaciteRawCitationNlm30CitationSchemaFilter
 *
 * @brief Tests for the ParaciteRawCitationNlm30CitationSchemaFilter class.
 */

import('lib.pkp.tests.plugins.citationParser.Nlm30CitationSchemaParserFilterTestCase');
import('lib.pkp.plugins.citationParser.paracite.filter.ParaciteRawCitationNlm30CitationSchemaFilter');

class ParaciteRawCitationNlm30CitationSchemaFilterTest extends Nlm30CitationSchemaParserFilterTestCase {
	/**
	 * @covers ParaciteRawCitationNlm30CitationSchemaFilter
	 */
	public function testExecute() {
		$testCitations = array(
			CITATION_PARSER_PARACITE_STANDARD => array(
				array(
					'testInput' => 'Sheril, R. D. (1956). The terrifying future: Contemplating color television. San Diego: Halstead.',
					'testOutput' => array(
						'[@publication-type]' => 'book',
						'chapter-title' => 'The terrifying future: Contemplating color television',
						'person-group[@person-group-type="author"]' => array(
							array('given-names' => array('R'), 'surname' => 'Sheril')
						),
						'date' => '1956',
						'publisher-name' => 'Halstead',
						'publisher-loc' => 'San Diego'
					)
				),
				array(
					'testInput' => 'Crackton, P. (1987). The Loonie: God\'s long-awaited gift to colourful pocket change? Canadian Change, 64(7), 34-37.',
					'testOutput' => array(
						'[@publication-type]' => 'journal',
						'article-title' => 'The Loonie: God\'s long-awaited gift to colourful pocket change? Canadian Change, 64(7), 34-37',
						'person-group[@person-group-type="author"]' => array(
							array('given-names' => array('P'), 'surname' => 'Crackton')
						),
						'date' => '1987'
					)
				)
			),
			CITATION_PARSER_PARACITE_CITEBASE => array(
				array(
					'testInput' => 'Sheril, R. D. (1956). The terrifying future: Contemplating color television. San Diego: Halstead.',
					'testOutput' => array(
						'[@publication-type]' => 'journal',
						'person-group[@person-group-type="author"]' => array(
							array('given-names' => array('R', 'D'), 'surname' => 'Sheril')
						),
						'date' => '1956',
						'comment' => 'Sheril, R. D. . The terrifying future:Contemplating color television. San Diego:Halstead'
					)
				),
				array(
					'testInput' => 'Crackton, P. (1987). The Loonie: God\'s long-awaited gift to colourful pocket change? Canadian Change, 64(7), 34-37.',
					'testOutput' => array(
						'[@publication-type]' => 'journal',
						'source' => 'Canadian Chan',
						'person-group[@person-group-type="author"]' => array(
							array('given-names' => array('P'), 'surname' => 'Crackton')
						),
						'fpage' => 34,
						'date' => '1987',
						'comment' => 'Crackton, P. (1987). The Loonie:God\'s long-awaited gift to colourful pocket change? Canadian Change, 64(7), 34-37',
						'issue' => '7',
						'volume' => '64'
					)
				)
			),
			CITATION_PARSER_PARACITE_JIAO => array(
				array(
					'testInput' => 'Sheril, R. D. (1956). The terrifying future: Contemplating color television. San Diego: Halstead.',
					'testOutput' => array(
						'[@publication-type]' => 'journal',
						'date' => '1956'
					)
				),
				array(
					'testInput' => 'Crackton, P. (1987). The Loonie: God\'s long-awaited gift to colourful pocket change? Canadian Change, 64(7), 34–37.',
					'testOutput' => array(
						'[@publication-type]' => 'journal',
						'source' => 'Canadian Chan',
						'fpage' => 34,
						'date' => '1987',
						'issue' => '7',
						'volume' => '64'
					)
				)
			)
		);

		foreach (ParaciteRawCitationNlm30CitationSchemaFilter::getSupportedCitationModules() as $citationModule) {
			assert(isset($testCitations[$citationModule]));

			$filter = new ParaciteRawCitationNlm30CitationSchemaFilter(PersistableFilter::tempGroup(
					'primitive::string',
					'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)'));
			$filter->setData('citationModule', $citationModule);
			$this->assertNlm30CitationSchemaFilter($testCitations[$citationModule], $filter);
			unset($filter);
		}
	}

	/**
	 * @covers ParaciteRawCitationNlm30CitationSchemaFilter
	 */
	public function testAllCitationsWithThisParser() {
		foreach (ParaciteRawCitationNlm30CitationSchemaFilter::getSupportedCitationModules() as $citationModule) {
			$filter = new ParaciteRawCitationNlm30CitationSchemaFilter(PersistableFilter::tempGroup(
					'primitive::string',
					'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)'));
			$filter->setData('citationModule', $citationModule);
			parent::_testAllCitationsWithThisParser($filter);
			unset($filter);
		}
	}
}
?>
