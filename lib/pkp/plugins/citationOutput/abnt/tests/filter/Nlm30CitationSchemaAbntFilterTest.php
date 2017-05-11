<?php
/**
 * @defgroup plugins_citationOutput_abnt_tests_filter ABNT Filter Test Suite
 */

/**
 * @file tests/plugins/citationOutput/abnt/filter/Nlm30CitationSchemaAbntFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationSchemaAbntFilterTest
 * @ingroup plugins_citationOutput_abnt_tests_filter
 * @see Nlm30CitationSchemaAbntFilter
 *
 * @brief Tests for the Nlm30CitationSchemaAbntFilter class.
 */


import('lib.pkp.plugins.citationOutput.abnt.filter.Nlm30CitationSchemaAbntFilter');
import('lib.pkp.tests.plugins.citationOutput.Nlm30CitationSchemaCitationOutputFormatFilterTest');

class Nlm30CitationSchemaAbntFilterTest extends Nlm30CitationSchemaCitationOutputFormatFilterTest {
	/*
	 * Implements abstract methods from Nlm30CitationSchemaCitationOutputFormatFilter
	 */
	protected function getFilterInstance() {
		return new Nlm30CitationSchemaAbntFilter(PersistableFilter::tempGroup(
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)',
				'primitive::string'));
	}

	protected function getBookResultNoAuthor() {
		return array('<p><i>Mania de bater:</i> A punição corporal doméstica de crianças e adolescentes no Brasil. São Paulo: Iglu, 2001. 368 p. (Edição Standard Brasileira das Obras Psicológicas, v.10)', '</p>');
	}

	protected function getBookResult() {
		return array('<p>AZEVEDO, M.A. <i>Mania de bater:</i> A punição corporal doméstica de crianças e adolescentes no Brasil. São Paulo: Iglu, 2001. 368 p. (Edição Standard Brasileira das Obras Psicológicas, v.10)', '</p>');
	}

	protected function getBookChapterResult() {
		return array('<p>AZEVEDO, M.A.; GUERRA, V. Psicologia genética e lógica. In: ________. <i>Mania de bater:</i> A punição corporal doméstica de crianças e adolescentes no Brasil. São Paulo: Iglu, 2001. 368 p. (Edição Standard Brasileira das Obras Psicológicas, v.10)', '</p>');
	}

	protected function getBookChapterWithEditorResult() {
		return array('<p>AZEVEDO, M.A.; GUERRA, V. Psicologia genética e lógica. In: BANKS-LEITE, L. (Ed.). <i>Mania de bater:</i> A punição corporal doméstica de crianças e adolescentes no Brasil. São Paulo: Iglu, 2001. 368 p. (Edição Standard Brasileira das Obras Psicológicas, v.10)', '</p>');
	}

	protected function getBookChapterWithEditorsResult() {
		return array('<p>AZEVEDO, M.A.; GUERRA, V. Psicologia genética e lógica. In: BANKS-LEITE, L.; VELADO, JR M. (Ed.). <i>Mania de bater:</i> A punição corporal doméstica de crianças e adolescentes no Brasil. São Paulo: Iglu, 2001. 368 p. (Edição Standard Brasileira das Obras Psicológicas, v.10)', '</p>');
	}

	protected function getJournalArticleResult() {
		return array('<p>SILVA, V.A.; DOS SANTOS, P. Etinobotânica Xucuru: espécies místicas. <i>Biotemas</i>, Florianópolis, v.15, n.1, p.45-57, jun 2000. pmid:12140307. doi:10146:55793-493.', '</p>');
	}

	protected function getJournalArticleWithMoreThanSevenAuthorsResult() {
		return array('<p>SILVA, V.A. et al. Etinobotânica Xucuru: espécies místicas. <i>Biotemas</i>, Florianópolis, v.15, n.1, p.45-57, jun 2000. pmid:12140307. doi:10146:55793-493.', '</p>');
	}

	protected function getConfProcResult() {
		$this->markTestIncomplete('ConfProc citations are not yet implemented');
	}
}
?>
