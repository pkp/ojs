<?php
/**
 * @defgroup plugins_citationOutput_mla_tests_filter MLA Filter Test Suite
 */

/**
 * @file plugins/citationOutput/mla/tests/filter/Nlm30CitationSchemaMlaFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationSchemaMlaFilterTest
 * @ingroup plugins_citationOutput_mla_tests_filter
 * @see Nlm30CitationSchemaMlaFilter
 *
 * @brief Tests for the Nlm30CitationSchemaMlaFilter class.
 */

import('lib.pkp.plugins.citationOutput.mla.filter.Nlm30CitationSchemaMlaFilter');
import('lib.pkp.tests.plugins.citationOutput.Nlm30CitationSchemaCitationOutputFormatFilterTest');

class Nlm30CitationSchemaMlaFilterTest extends Nlm30CitationSchemaCitationOutputFormatFilterTest {
	/*
	 * Implements abstract methods from Nlm30CitationSchemaCitationOutputFormatFilter
	 */
	protected function getFilterInstance() {
		return new Nlm30CitationSchemaMlaFilter(PersistableFilter::tempGroup(
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)',
				'primitive::string'));
	}

	protected function getBookResultNoAuthor() {
		return array('<p style="text-indent:-2em;margin-left:2em"><i>Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil.</i> São Paulo: Iglu, 2001. Print.', '</p>');
	}

	protected function getBookResult() {
		return array('<p style="text-indent:-2em;margin-left:2em">Azevedo, Mario Antonio. <i>Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil.</i> São Paulo: Iglu, 2001. Print.', '</p>');
	}

	protected function getBookChapterResult() {
		return array('<p style="text-indent:-2em;margin-left:2em">Azevedo, Mario Antonio, and Vitor Guerra. "Psicologia genética e lógica." <i>Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil.</i> São Paulo: Iglu, 2001. 15-25. Print.', '</p>');
	}

	protected function getBookChapterWithEditorResult() {
		return array('<p style="text-indent:-2em;margin-left:2em">Azevedo, Mario Antonio, and Vitor Guerra. "Psicologia genética e lógica." <i>Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil.</i> Ed. Lorena Banks-Leite. São Paulo: Iglu, 2001. 15-25. Print.', '</p>');
	}

	protected function getBookChapterWithEditorsResult() {
		return array('<p style="text-indent:-2em;margin-left:2em">Azevedo, Mario Antonio, and Vitor Guerra. "Psicologia genética e lógica." <i>Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil.</i> Ed. Lorena Banks-Leite and Mariano Velado Jr. 2nd ed. São Paulo: Iglu, 2001. 15-25. Print.', '</p>');
	}

	protected function getJournalArticleResult() {
		return array('<p style="text-indent:-2em;margin-left:2em">Silva, Vitor Antonio, and Pedro dos Santos. "Etinobotânica Xucuru: espécies místicas." <i>Biotemas</i> 15.1 (Jun 2000): 45-57. Print. pmid:12140307 doi:10146:55793-493', '</p>');
	}

	protected function getJournalArticleWithMoreThanSevenAuthorsResult() {
		return array('<p style="text-indent:-2em;margin-left:2em">Silva, Vitor Antonio, et al. "Etinobotânica Xucuru: espécies místicas." <i>Biotemas</i> 15.1 (Jun 2000): 45-57. Print. pmid:12140307 doi:10146:55793-493', '</p>');
	}

	protected function getConfProcResult() {
		return array('<p style="text-indent:-2em;margin-left:2em">Liu, Sen. "Defending against business crises with the help of intelligent agent based early warning solutions." <i>Conference Proceedings of The Seventh International Conference on Enterprise Information Systems.</i> Miami, FL: 2005. Web. 12 Aug. 2006. &lt;http://www.iceis.org/iceis2005/abstracts_2005.htm&gt;', '</p>');
	}
}
?>
