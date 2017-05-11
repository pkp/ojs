<?php
/**
 * @defgroup plugins_citationParser_regex_tests_filter RegEx Filter Test Suite
 */

/**
 * @file plugins/citationParser/regex/tests/filter/RegexRawCitationNlm30CitationSchemaFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegexRawCitationNlm30CitationSchemaFilterTest
 * @ingroup plugins_citationParser_regex_tests_filter
 * @see RegexRawCitationNlm30CitationSchemaFilter
 *
 * @brief Tests for the RegexRawCitationNlm30CitationSchemaFilter class.
 */

import('lib.pkp.tests.plugins.citationParser.Nlm30CitationSchemaParserFilterTestCase');
import('lib.pkp.plugins.citationParser.regex.filter.RegexRawCitationNlm30CitationSchemaFilter');

class RegexRawCitationNlm30CitationSchemaFilterTest extends Nlm30CitationSchemaParserFilterTestCase {
	/**
	 * @covers RegexRawCitationNlm30CitationSchemaFilter
	 */
	public function testExecute() {
		$testCitations = array(
			array(
				'testInput' => 'McFarland EG, Park HB. Am J Clin Nutr. 2003 Sep;78(3 Suppl):647-650. PMID: 12936960 [PubMed - indexed for MEDLINE] [] <http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=Retrieve&db=pubMed&list_uids=12936960&dopt=Abstract>',
				'testOutput' => array (
					'[@publication-type]' => 'journal',
					'person-group[@person-group-type="author"]' => array(
						array('given-names' => array('E', 'G'), 'surname' => 'McFarland'),
						array('given-names' => array('H', 'B'), 'surname' => 'Park'),
					),
					'fpage' => 647,
					'lpage' => 650,
					'date' => '2003-09',
					'uri' => 'http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=Retrieve&db=pubMed&list_uids=12936960&dopt=Abstract',
					'volume' => '78',
					'issue' => '3 Suppl',
					'pub-id[@pub-id-type="pmid"]' => '12936960',
					'comment' => 'PubMed - indexed for MEDLINE',
				)
			),
			array(
				'testInput' => 'McFarland EG, Park HB. Limited lateral acromioplasty for rotator cuff surgery. Orthopedics 2005; 28(3):256-259. [accessed: 2009 Jul 17] http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=PureSearch&db=pubMed&details_term=15790083',
				'testOutput' => array(
					'[@publication-type]' => 'journal',
					'person-group[@person-group-type="author"]' => array(
						array('given-names' => array('E', 'G'), 'surname' => 'McFarland'),
						array('given-names' => array('H', 'B'), 'surname' => 'Park')
					),
					'article-title' => 'Limited lateral acromioplasty for rotator cuff surgery',
					'fpage' => 256,
					'lpage' => 259,
					'date' => '2005',
					'date-in-citation[@content-type="access-date"]' => '2009-07-17',
					'uri' => 'http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=PureSearch&db=pubMed&details_term=15790083',
					'source' => 'Orthopedics',
					'issue' => '3',
					'volume' => '28',
					'pub-id[@pub-id-type="pmid"]' => '15790083'
				)
			),
			array(
				'testInput' => 'McFarland EG, Park HB. Limited lateral acromioplasty for rotator cuff surgery. Orthopedics 2005; 28(3):256-259. doi:10.1000/182. http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=PureSearch&db=pubMed&pubmedid=15790083',
				'testOutput' => array(
					'[@publication-type]' => 'journal',
					'person-group[@person-group-type="author"]' => array(
						array('given-names' => array('E', 'G'), 'surname' => 'McFarland'),
						array('given-names' => array('H', 'B'), 'surname' => 'Park')
					),
					'article-title' => 'Limited lateral acromioplasty for rotator cuff surgery',
					'fpage' => 256,
					'lpage' => 259,
					'date' => '2005',
					'pub-id[@pub-id-type="doi"]' => '10.1000/182',
					'uri' => 'http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=PureSearch&db=pubMed&pubmedid=15790083',
					'source' => 'Orthopedics',
					'issue' => '3',
					'volume' => '28',
					'pub-id[@pub-id-type="pmid"]' => '15790083'
				)
			),
			array(
				'testInput' => 'Murray PR, Rosenthal KS, et al. Medical microbiology. 4th ed. New York: Mosby; 2002.',
				'testOutput' => array(
					'[@publication-type]' => 'book',
					'person-group[@person-group-type="author"]' => array(
						array('given-names' => array('P', 'R'), 'surname' => 'Murray'),
						array('given-names' => array('K', 'S'), 'surname' => 'Rosenthal'),
						'et-al'
					),
					'date' => '2002',
					'source' => 'Medical microbiology. 4th ed',
					'publisher-name' => 'Mosby',
					'publisher-loc' => 'New York'
				)
			),
			array(
				'testInput' => 'Limited lateral acromioplasty for rotator cuff surgery. URL: http://www.ncbi.nlm.nih.gov/entrez/query.fcgi',
				'testOutput' => array(
					'article-title' => 'Limited lateral acromioplasty for rotator cuff surgery',
					'uri' => 'http://www.ncbi.nlm.nih.gov/entrez/query.fcgi'
				)
			),
			array(
				'testInput' => 'McFarland EG, Park HB. Limited lateral acromioplasty for rotator cuff surgery. URL: http://www.ncbi.nlm.nih.gov/entrez/query.fcgi',
				'testOutput' => array(
					'person-group[@person-group-type="author"]' => array(
						array('given-names' => array('E', 'G'), 'surname' => 'McFarland'),
						array('given-names' => array('H', 'B'), 'surname' => 'Park')
					),
					'article-title' => 'Limited lateral acromioplasty for rotator cuff surgery',
					'uri' => 'http://www.ncbi.nlm.nih.gov/entrez/query.fcgi'
				)
			),
			array(
				'testInput' => 'McFarland EG, Park HB. Limited lateral acromioplasty for rotator cuff surgery. Web edition test. Orthopedics 2005. URL: http://www.ncbi.nlm.nih.gov/entrez/query.fcgi',
				'testOutput' => array(
					'person-group[@person-group-type="author"]' => array(
						array('given-names' => array('E', 'G'), 'surname' => 'McFarland'),
						array('given-names' => array('H', 'B'), 'surname' => 'Park')
					),
					'article-title' => 'Limited lateral acromioplasty for rotator cuff surgery. Web edition test',
					'uri' => 'http://www.ncbi.nlm.nih.gov/entrez/query.fcgi',
					'source' => 'Orthopedics 2005'
				)
			)
		);

		$filter = new RegexRawCitationNlm30CitationSchemaFilter(PersistableFilter::tempGroup(
				'primitive::string',
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)'));
		$this->assertNlm30CitationSchemaFilter($testCitations, $filter);
	}

	/**
	 * @see Nlm30CitationSchemaParserFilterTestCase::testAllCitationsWithThisParser()
	 */
	public function testAllCitationsWithThisParser() {
		$filter = new RegexRawCitationNlm30CitationSchemaFilter(PersistableFilter::tempGroup(
				'primitive::string',
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)'));
		parent::_testAllCitationsWithThisParser($filter);
	}
}
?>
