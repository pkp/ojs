<?php
/**
 * @defgroup plugins_citationOutput_vancouver_tests_filter Vancouver Filter Test Suite
 */

/**
 * @file plugins/citationOutput/vancouver/tests/filter/Nlm30CitationSchemaVancouverFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationSchemaVancouverFilterTest
 * @ingroup plugins_citationOutput_vancouver_tests_filter
 * @see Nlm30CitationSchemaVancouverFilter
 *
 * @brief Tests for the Nlm30CitationSchemaVancouverFilter class.
 */

import('lib.pkp.plugins.citationOutput.vancouver.filter.Nlm30CitationSchemaVancouverFilter');
import('lib.pkp.tests.plugins.citationOutput.Nlm30CitationSchemaCitationOutputFormatFilterTest');

class Nlm30CitationSchemaVancouverFilterTest extends Nlm30CitationSchemaCitationOutputFormatFilterTest {
	/*
	 * Implements abstract methods from Nlm30CitationSchemaCitationOutputFormatFilter
	 */
	protected function getFilterInstance() {
		return new Nlm30CitationSchemaVancouverFilter(PersistableFilter::tempGroup(
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)',
				'primitive::string'));
	}

	protected function getBookResultNoAuthor() {
		return array('<p>Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil. São Paulo: Iglu; 2001.', '</p>');
	}

	protected function getBookResult() {
		return array('<p>Azevedo MA. Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil. São Paulo: Iglu; 2001.', '</p>');
	}

	protected function getBookChapterResult() {
		return array('<p>Azevedo MA, Guerra V. Psicologia genética e lógica. In: Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil. São Paulo: Iglu; 2001. p. 15-25.', '</p>');
	}

	protected function getBookChapterWithEditorResult() {
		return array('<p>Azevedo MA, Guerra V. Psicologia genética e lógica. In: Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil. Banks-Leite L, editor. São Paulo: Iglu; 2001. p. 15-25.', '</p>');
	}

	protected function getBookChapterWithEditorsResult() {
		return array('<p>Azevedo MA, Guerra V. Psicologia genética e lógica. In: Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil. Banks-Leite L, Velado Jr M, editors. 2nd ed. São Paulo: Iglu; 2001. p. 15-25.', '</p>');
	}

	protected function getJournalArticleResult() {
		return array('<p>Silva VA, dos Santos P. Etinobotânica Xucuru: espécies místicas. Biotemas 2000 Jun;15(1):45-57. PubMed PMID: 12140307. doi: 10146:55793-493.', '</p>');
	}

	protected function getJournalArticleWithMoreThanSevenAuthorsResult() {
		return array('<p>Silva VA, dos Santos P, Miller FH, Choi MJ, Angeli LL, Harland AA, et al. Etinobotânica Xucuru: espécies místicas. Biotemas 2000 Jun;15(1):45-57. PubMed PMID: 12140307. doi: 10146:55793-493.', '</p>');
	}

	protected function getConfProcResult() {
		return array('<p>Liu S. Defending against business crises with the help of intelligent agent based early warning solutions. Proceedings of The Seventh International Conference on Enterprise Information Systems. Miami, FL: 2005 [cited 2006 Aug 12]. Available from: http://www.iceis.org/iceis2005/abstracts_2005.htm', '</p>');
	}
}
?>
