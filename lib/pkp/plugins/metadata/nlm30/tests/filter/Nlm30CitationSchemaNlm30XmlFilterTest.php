<?php

/**
 * @file plugins/metadata/nlm30/tests/filter/Nlm30CitationSchemaNlm30XmlFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationSchemaNlm30XmlFilterTest
 * @ingroup plugins_metadata_nlm30_tests_filter
 * @see Nlm30CitationSchemaNlm30XmlFilter
 *
 * @brief Tests for the Nlm30CitationSchemaNlm30XmlFilter class.
 */

import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30CitationSchemaNlm30XmlFilter');
import('lib.pkp.tests.plugins.citationOutput.Nlm30CitationSchemaCitationOutputFormatFilterTest');

class Nlm30CitationSchemaNlm30XmlFilterTest extends Nlm30CitationSchemaCitationOutputFormatFilterTest {
	/*
	 * Implements abstract methods from Nlm30CitationSchemaCitationOutputFormatFilter
	 */
	protected function getFilterInstance() {
		// FIXME: Add NLM citation-element + name validation (requires partial NLM DTD, XSD or RelaxNG), see #5648.
		return new Nlm30CitationSchemaNlm30XmlFilter(PersistableFilter::tempGroup(
				'metadata::lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema(CITATION)',
				'xml::*'));
	}

	protected function addGoogleScholar() {
		return false;
	}

	protected function getBookResultNoAuthor() {
		return array('<element-citation publication-type="book"><source>Mania de bater: a punição corporal doméstica de crianças e adolescentes no Brasil</source><year>2001</year><volume>10</volume><series>Edição Standard Brasileira das Obras Psicológicas</series><size>368</size><publisher-loc>São Paulo</publisher-loc><publisher-name>Iglu</publisher-name></element-citation>', '');
	}

	protected function getBookResult() {
		return array('<element-citation publication-type="book"><person-group person-group-type="author"><name><surname>Azevedo</surname><given-names>Mario Antonio</given-names></name></person-group><source>Mania de bater: a punição corporal doméstica de crianças e adolescentes no Brasil</source><year>2001</year><volume>10</volume><series>Edição Standard Brasileira das Obras Psicológicas</series><size>368</size><publisher-loc>São Paulo</publisher-loc><publisher-name>Iglu</publisher-name></element-citation>', '');
	}

	protected function getBookChapterResult() {
		return array('<element-citation publication-type="book"><person-group person-group-type="author"><name><surname>Azevedo</surname><given-names>Mario Antonio</given-names></name><name><surname>Guerra</surname><given-names>Vitor</given-names></name></person-group><source>Mania de bater: a punição corporal doméstica de crianças e adolescentes no Brasil</source><year>2001</year><volume>10</volume><chapter-title>Psicologia genética e lógica</chapter-title><series>Edição Standard Brasileira das Obras Psicológicas</series><fpage>15</fpage><lpage>25</lpage><size>368</size><publisher-loc>São Paulo</publisher-loc><publisher-name>Iglu</publisher-name></element-citation>', '');
	}

	protected function getBookChapterWithEditorResult() {
		return array('<element-citation publication-type="book"><person-group person-group-type="author"><name><surname>Azevedo</surname><given-names>Mario Antonio</given-names></name><name><surname>Guerra</surname><given-names>Vitor</given-names></name></person-group><person-group person-group-type="editor"><name><surname>Banks-Leite</surname><given-names>Lorena</given-names></name></person-group><source>Mania de bater: a punição corporal doméstica de crianças e adolescentes no Brasil</source><year>2001</year><volume>10</volume><chapter-title>Psicologia genética e lógica</chapter-title><series>Edição Standard Brasileira das Obras Psicológicas</series><fpage>15</fpage><lpage>25</lpage><size>368</size><publisher-loc>São Paulo</publisher-loc><publisher-name>Iglu</publisher-name></element-citation>', '');
	}

	protected function getBookChapterWithEditorsResult() {
		return array('<element-citation publication-type="book"><person-group person-group-type="author"><name><surname>Azevedo</surname><given-names>Mario Antonio</given-names></name><name><surname>Guerra</surname><given-names>Vitor</given-names></name></person-group><person-group person-group-type="editor"><name><surname>Banks-Leite</surname><given-names>Lorena</given-names></name><name><surname>Velado</surname><given-names>Mariano</given-names><suffix>Jr</suffix></name></person-group><source>Mania de bater: a punição corporal doméstica de crianças e adolescentes no Brasil</source><year>2001</year><volume>10</volume><chapter-title>Psicologia genética e lógica</chapter-title><edition>2nd ed</edition><series>Edição Standard Brasileira das Obras Psicológicas</series><fpage>15</fpage><lpage>25</lpage><size>368</size><publisher-loc>São Paulo</publisher-loc><publisher-name>Iglu</publisher-name></element-citation>', '');
	}

	protected function getJournalArticleResult() {
		return array('<element-citation publication-type="journal"><person-group person-group-type="author"><name><surname>Silva</surname><given-names>Vitor Antonio</given-names></name><name><surname>Santos</surname><given-names>Pedro</given-names><prefix>dos</prefix></name></person-group><article-title>Etinobotânica Xucuru: espécies místicas</article-title><source>Biotemas</source><month>6</month><year>2000</year><issue>1</issue><volume>15</volume><fpage>45</fpage><lpage>57</lpage><publisher-loc>Florianópolis</publisher-loc><pub-id pub-id-type="doi">10146:55793-493</pub-id><pub-id pub-id-type="pmid">12140307</pub-id></element-citation>', '');
	}

	protected function getJournalArticleWithMoreThanSevenAuthorsResult() {
		return array('<element-citation publication-type="journal"><person-group person-group-type="author"><name><surname>Silva</surname><given-names>Vitor Antonio</given-names></name><name><surname>Santos</surname><given-names>Pedro</given-names><prefix>dos</prefix></name><name><surname>Miller</surname><given-names>F H</given-names></name><name><surname>Choi</surname><given-names>M J</given-names></name><name><surname>Angeli</surname><given-names>L L</given-names></name><name><surname>Harland</surname><given-names>A A</given-names></name><name><surname>Stamos</surname><given-names>J A</given-names></name><name><surname>Thomas</surname><given-names>S T</given-names></name></person-group><article-title>Etinobotânica Xucuru: espécies místicas</article-title><source>Biotemas</source><month>6</month><year>2000</year><issue>1</issue><volume>15</volume><fpage>45</fpage><lpage>57</lpage><publisher-loc>Florianópolis</publisher-loc><pub-id pub-id-type="doi">10146:55793-493</pub-id><pub-id pub-id-type="pmid">12140307</pub-id></element-citation>', '');
	}

	protected function getConfProcResult() {
		return array('<element-citation publication-type="conf-proc"><person-group person-group-type="author"><name><surname>Liu</surname><given-names>Sen</given-names></name></person-group><article-title>Defending against business crises with the help of intelligent agent based early warning solutions</article-title><month>5</month><year>2005</year><date-in-citation content-type="access-date"><day>12</day><month>8</month><year>2006</year></date-in-citation><conf-loc>Miami, FL</conf-loc><conf-name>The Seventh International Conference on Enterprise Information Systems</conf-name><uri>http://www.iceis.org/iceis2005/abstracts_2005.htm</uri></element-citation>', '');
	}
}
?>
