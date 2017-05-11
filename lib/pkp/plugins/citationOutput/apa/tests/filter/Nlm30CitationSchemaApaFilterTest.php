<?php
/**
 * @defgroup plugins_citationOutput_apa_tests_filter APA Filter Test Suite
 */

/**
 * @file plugins/citationOutput/apa/tests/filter/Nlm30CitationSchemaApaFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationSchemaApaFilterTest
 * @ingroup plugins_citationOutput_apa_tests_filter
 * @see Nlm30CitationSchemaApaFilter
 *
 * @brief Tests for the Nlm30CitationSchemaApaFilter class.
 */

import('lib.pkp.plugins.citationOutput.apa.filter.Nlm30CitationSchemaApaFilter');
import('lib.pkp.tests.plugins.citationOutput.Nlm30CitationSchemaCitationOutputFormatFilterTest');

class Nlm30CitationSchemaApaFilterTest extends Nlm30CitationSchemaCitationOutputFormatFilterTest {
	/*
	 * Implements abstract methods from Nlm30CitationSchemaCitationOutputFormatFilter
	 */
	protected function getFilterInstance() {
		return new Nlm30CitationSchemaApaFilter(PersistableFilter::tempGroup(
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)',
				'primitive::string'));
	}

	protected function getBookResultNoAuthor() {
		return array('<p style="text-indent:-2em;margin-left:2em">Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil (2001). São Paulo: Iglu.', '</p>');
	}

	protected function getBookResult() {
		return array('<p style="text-indent:-2em;margin-left:2em">Azevedo, M. A. (2001). <i>Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil</i>. São Paulo: Iglu.', '</p>');
	}

	protected function getBookChapterResult() {
		return array('<p style="text-indent:-2em;margin-left:2em">Azevedo, M. A., & Guerra, V. (2001) Psicologia genética e lógica. In <i>Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil</i> (pp. 15-25). São Paulo: Iglu.', '</p>');
	}

	protected function getBookChapterWithEditorResult() {
		return array('<p style="text-indent:-2em;margin-left:2em">Azevedo, M. A., & Guerra, V. (2001) Psicologia genética e lógica. In L. Banks-Leite (Ed.), <i>Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil</i> (pp. 15-25). São Paulo: Iglu.', '</p>');
	}

	protected function getBookChapterWithEditorsResult() {
		return array('<p style="text-indent:-2em;margin-left:2em">Azevedo, M. A., & Guerra, V. (2001) Psicologia genética e lógica. In L. Banks-Leite & M. Velado, Jr (Eds.), <i>Mania de bater: A punição corporal doméstica de crianças e adolescentes no Brasil</i> (pp. 15-25). São Paulo: Iglu.', '</p>');
	}

	protected function getJournalArticleResult() {
		return array('<p style="text-indent:-2em;margin-left:2em">Silva, V. A., & dos Santos, P. (2000). Etinobotânica Xucuru: espécies místicas. <i>Biotemas, </i>15(1), 45-57. pmid:12140307 doi:10146:55793-493', '</p>');
	}

	protected function getJournalArticleWithMoreThanSevenAuthorsResult() {
		return array('<p style="text-indent:-2em;margin-left:2em">Silva, V. A., dos Santos, P., Miller, F. H., Choi, M. J., Angeli, L. L., Harland, A. A., . . . Thomas, S. T. (2000). Etinobotânica Xucuru: espécies místicas. <i>Biotemas, </i>15(1), 45-57. pmid:12140307 doi:10146:55793-493', '</p>');
	}

	protected function getConfProcResult() {
		return array('<p style="text-indent:-2em;margin-left:2em">Liu, S. (2005). <i>Defending against business crises with the help of intelligent agent based early warning solutions. </i>Paper presented at The Seventh International Conference on Enterprise Information Systems, Miami, FL. Retrieved from http://www.iceis.org/iceis2005/abstracts_2005.htm', '</p>');
	}
}
?>
