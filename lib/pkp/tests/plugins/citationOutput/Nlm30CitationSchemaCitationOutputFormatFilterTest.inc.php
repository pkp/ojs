<?php

/**
 * @defgroup tests_plugins_citationOutput Citation Output Plugin Tests
 */

/**
 * @file tests/plugins/citationOutput/Nlm30CitationSchemaCitationOutputFormatFilterTest.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationSchemaCitationOutputFormatFilterTest
 * @ingroup tests_plugins_citationOutput
 *
 * @brief Base tests class for citation output format filters.
 */

import('lib.pkp.tests.PKPTestCase');

import('lib.pkp.classes.core.PKPRouter');
import('lib.pkp.classes.core.PKPRequest');

import('lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema');
import('lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema');
import('lib.pkp.classes.metadata.MetadataDescription');

abstract class Nlm30CitationSchemaCitationOutputFormatFilterTest extends PKPTestCase {

	//
	// Implement template methods from PKPTestCase
	//
	/**
	 * @copydoc PKPTestCase::getMockedRegistryKeys()
	 */
	protected function getMockedRegistryKeys() {
		return array('request');
	}

	/**
	 * @copydoc PKPTestCase::setUp()
	 */
	protected function setUp() {
		$application = PKPApplication::getApplication();
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$request = $application->getRequest();
		if (is_null($request->getRouter())) {
			$router = new PKPRouter();
			$request->setRouter($router);
		}
	}

	public function testExecuteWithUnsupportedPublicationType() {
		$this->markTestSkipped('Weird class interaction with ControlledVocabEntryDAO leads to failure');

		$nameSchemaName = 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema';
		$citationSchemaName = 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema';
		// Create a description with an unsupported publication type
		$citationDescription = new MetadataDescription($citationSchemaName, ASSOC_TYPE_CITATION);
		$citationDescription->addStatement('[@publication-type]', $pubType = NLM30_PUBLICATION_TYPE_THESIS);
		$citationOutputFilter = $this->getFilterInstance();
		$result = $citationOutputFilter->execute($citationDescription);
		self::assertEquals('', $result);
		self::assertEquals(array('##submission.citations.filter.unsupportedPublicationType##'), $citationOutputFilter->getErrors());
	}

	public function testExecuteWithBook() {
		$this->markTestSkipped('Weird class interaction with ControlledVocabEntryDAO leads to failure');

		$nameSchemaName = 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema';
		$citationSchemaName = 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema';

		// Two representative authors
		$person1Description = new MetadataDescription($nameSchemaName, ASSOC_TYPE_AUTHOR);
		$person1Description->addStatement('surname', $surname = 'Azevedo');
		$person1Description->addStatement('given-names', $givenName1 = 'Mario');
		$person1Description->addStatement('given-names', $givenName2 = 'Antonio');
		$person2Description = new MetadataDescription($nameSchemaName, ASSOC_TYPE_AUTHOR);
		$person2Description->addStatement('surname', $surname2 = 'Guerra');
		$person2Description->addStatement('given-names', $givenName3 = 'Vitor');

		// Check a book with minimal data
		$citationDescription = new MetadataDescription($citationSchemaName, ASSOC_TYPE_CITATION);
		$citationDescription->addStatement('[@publication-type]', $pubType = NLM30_PUBLICATION_TYPE_BOOK);
		$citationDescription->addStatement('source', $source = 'Mania de bater: a punição corporal doméstica de crianças e adolescentes no Brasil');
		$citationDescription->addStatement('date', $date = '2001');
		$citationDescription->addStatement('publisher-loc', $pubLoc = 'São Paulo');
		$citationDescription->addStatement('publisher-name', $pubName = 'Iglu');
		$citationDescription->addStatement('size', $size = 368);
		$citationDescription->addStatement('series', $series = 'Edição Standard Brasileira das Obras Psicológicas');
		$citationDescription->addStatement('volume', $volume = '10');

		$citationOutputFilter = $this->getFilterInstance();

		// Book without author
		$result = $citationOutputFilter->execute($citationDescription);
		$expectedResult = $this->getBookResultNoAuthor();
		self::assertEquals($expectedResult[0].$this->getBookResultNoAuthorGoogleScholar().$expectedResult[1], $result);

		// Add an author
		$citationDescription->addStatement('person-group[@person-group-type="author"]', $person1Description);
		$result = $citationOutputFilter->execute($citationDescription);
		$expectedResult = $this->getBookResult();
		self::assertEquals($expectedResult[0].$this->getBookResultGoogleScholar().$expectedResult[1], $result);

		// Add a chapter title and a second author
		$citationDescription->addStatement('person-group[@person-group-type="author"]', $person2Description);
		$citationDescription->addStatement('chapter-title', $chapterTitle = 'Psicologia genética e lógica');
		$citationDescription->addStatement('fpage', $fpage = 15);
		$citationDescription->addStatement('lpage', $lpage = 25);
		$result = $citationOutputFilter->execute($citationDescription);
		$expectedResult = $this->getBookChapterResult();
		self::assertEquals($expectedResult[0].$this->getBookResultGoogleScholar().$expectedResult[1], $result);

		// Add editor
		$person3Description = new MetadataDescription($nameSchemaName, ASSOC_TYPE_EDITOR);
		$person3Description->addStatement('surname', $surname3 = 'Banks-Leite');
		$person3Description->addStatement('given-names', $givenName4 = 'Lorena');
		$citationDescription->addStatement('person-group[@person-group-type="editor"]', $person3Description);
		$result = $citationOutputFilter->execute($citationDescription);
		$expectedResult = $this->getBookChapterWithEditorResult();
		self::assertEquals($expectedResult[0].$this->getBookResultGoogleScholar().$expectedResult[1], $result);

		// Add another editor and an edition.
		$person4Description = new MetadataDescription($nameSchemaName, ASSOC_TYPE_EDITOR);
		$person4Description->addStatement('surname', $surname3 = 'Velado');
		$person4Description->addStatement('given-names', $givenName4 = 'Mariano');
		$person4Description->addStatement('suffix', $givenName4 = 'Jr');
		self::assertTrue($citationDescription->addStatement('person-group[@person-group-type="editor"]', $person4Description));
		self::assertTrue($citationDescription->addStatement('edition', $edition = '2nd ed'));
		$result = $citationOutputFilter->execute($citationDescription);
		$expectedResult = $this->getBookChapterWithEditorsResult();
		self::assertEquals($expectedResult[0].$this->getBookResultGoogleScholar().$expectedResult[1], $result);
	}

	public function testExecuteWithJournal() {
		$this->markTestSkipped('Weird class interaction with ControlledVocabEntryDAO leads to failure');

		$nameSchemaName = 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema';
		$citationSchemaName = 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema';

		// Two representative authors
		$person1Description = new MetadataDescription($nameSchemaName, ASSOC_TYPE_AUTHOR);
		$person1Description->addStatement('surname', $surname = 'Silva');
		$person1Description->addStatement('given-names', $givenName1 = 'Vitor');
		$person1Description->addStatement('given-names', $givenName2 = 'Antonio');
		$person2Description = new MetadataDescription($nameSchemaName, ASSOC_TYPE_AUTHOR);
		$person2Description->addStatement('surname', $surname2 = 'Santos');
		$person2Description->addStatement('prefix', $prefix1 = 'dos');
		$person2Description->addStatement('given-names', $givenName3 = 'Pedro');

		// Check a journal article
		$citationDescription = new MetadataDescription($citationSchemaName, ASSOC_TYPE_CITATION);
		$citationDescription->addStatement('[@publication-type]', $pubType = NLM30_PUBLICATION_TYPE_JOURNAL);
		$citationDescription->addStatement('person-group[@person-group-type="author"]', $person1Description);
		$citationDescription->addStatement('person-group[@person-group-type="author"]', $person2Description);
		$citationDescription->addStatement('article-title', $articleTitle = 'Etinobotânica Xucuru: espécies místicas');
		$citationDescription->addStatement('source', $source = 'Biotemas');
		$citationDescription->addStatement('publisher-loc', $pubLoc = 'Florianópolis');
		$citationDescription->addStatement('volume', $volume = '15');
		$citationDescription->addStatement('issue', $issue = '1');
		$citationDescription->addStatement('fpage', $fpage = 45);
		$citationDescription->addStatement('lpage', $lpage = 57);
		$citationDescription->addStatement('date', $date = '2000-06');
		$citationDescription->addStatement('pub-id[@pub-id-type="doi"]', $doi = '10146:55793-493');
		$citationDescription->addStatement('pub-id[@pub-id-type="pmid"]', $pmid = '12140307');
		$citationOutputFilter = $this->getFilterInstance();
		$result = $citationOutputFilter->execute($citationDescription);
		$expectedResult = $this->getJournalArticleResult();
		self::assertEquals($expectedResult[0].$this->getJournalArticleResultGoogleScholar().$expectedResult[1], $result);

		// Add 6 more authors
		$authors = array(
			array('Miller', array('F', 'H')),
			array('Choi', array('M', 'J')),
			array('Angeli', array('L', 'L')),
			array('Harland', array('A', 'A')),
			array('Stamos', array('J', 'A')),
			array('Thomas', array('S', 'T'))
		);
		foreach ($authors as $author) {
			$personDescription = new MetadataDescription($nameSchemaName, ASSOC_TYPE_AUTHOR);
			$personDescription->addStatement('surname', $author[0]);
			$personDescription->addStatement('given-names', $author[1][0]);
			$personDescription->addStatement('given-names', $author[1][1]);
			$citationDescription->addStatement('person-group[@person-group-type="author"]', $personDescription);
			unset($personDescription);
		}
		$result = $citationOutputFilter->execute($citationDescription);
		$expectedResult = $this->getJournalArticleWithMoreThanSevenAuthorsResult();
		self::assertEquals($expectedResult[0].$this->getJournalArticleResultGoogleScholar().$expectedResult[1], $result);
	}

	public function testExecuteWithConferenceProceeding() {
		$this->markTestSkipped('Weird class interaction with ControlledVocabEntryDAO leads to failure');

		$nameSchemaName = 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema';
		$citationSchemaName = 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema';

		// An author
		$personDescription = new MetadataDescription($nameSchemaName, ASSOC_TYPE_AUTHOR);
		$personDescription->addStatement('surname', $surname = 'Liu');
		$personDescription->addStatement('given-names', $givenName = 'Sen');

		// A conference paper found on the web
		$citationDescription = new MetadataDescription($citationSchemaName, ASSOC_TYPE_CITATION);
		$citationDescription->addStatement('[@publication-type]', $pubType = NLM30_PUBLICATION_TYPE_CONFPROC);
		$citationDescription->addStatement('person-group[@person-group-type="author"]', $personDescription);
		$citationDescription->addStatement('article-title', $articleTitle = 'Defending against business crises with the help of intelligent agent based early warning solutions');
		$citationDescription->addStatement('conf-name', $confName = 'The Seventh International Conference on Enterprise Information Systems');
		$citationDescription->addStatement('conf-loc', $confLoc = 'Miami, FL');
		$citationDescription->addStatement('date', $date = '2005-05');
		$citationDescription->addStatement('date-in-citation[@content-type="access-date"]', $accessDate = '2006-08-12');
		$citationDescription->addStatement('uri', $uri = 'http://www.iceis.org/iceis2005/abstracts_2005.htm');
		$citationOutputFilter = $this->getFilterInstance();
		$result = $citationOutputFilter->execute($citationDescription);
		$expectedResult = $this->getConfProcResult();
		self::assertEquals($expectedResult[0].$this->getConfProcResultGoogleScholar().$expectedResult[1], $result);
	}

	//
	// Private methods providing the Google Scholar link for all citations
	//
	private function getBookResultNoAuthorGoogleScholar() {
		if ($this->addGoogleScholar()) {
			return ' <a href="http://scholar.google.com/scholar?ie=UTF-8&oe=UTF-8&hl=en&q=%22Mania%20de%20bater%3A%20a%20puni%C3%A7%C3%A3o%20corporal%20dom%C3%A9stica%20de%20crian%C3%A7as%20e%20adolescentes%20no%20Brasil%22+" target="_blank">[Google Scholar]</a>';
		} else {
			return '';
		}
	}

	private function getBookResultGoogleScholar() {
		if ($this->addGoogleScholar()) {
			return ' <a href="http://scholar.google.com/scholar?ie=UTF-8&oe=UTF-8&hl=en&q=author:%22Azevedo%22+%22Mania%20de%20bater%3A%20a%20puni%C3%A7%C3%A3o%20corporal%20dom%C3%A9stica%20de%20crian%C3%A7as%20e%20adolescentes%20no%20Brasil%22+" target="_blank">[Google Scholar]</a>';
		} else {
			return '';
		}
	}

	private function getJournalArticleResultGoogleScholar() {
		if ($this->addGoogleScholar()) {
			return ' <a href="http://scholar.google.com/scholar?ie=UTF-8&oe=UTF-8&hl=en&q=author:%22Silva%22+%22Biotemas%22+Etinobot%C3%A2nica%20Xucuru%3A%20esp%C3%A9cies%20m%C3%ADsticas+10146%3A55793-493" target="_blank">[Google Scholar]</a>';
		} else {
			return '';
		}
	}

	private function getConfProcResultGoogleScholar() {
		if ($this->addGoogleScholar()) {
			return ' <a href="http://scholar.google.com/scholar?ie=UTF-8&oe=UTF-8&hl=en&q=author:%22Liu%22+%22The%20Seventh%20International%20Conference%20on%20Enterprise%20Information%20Systems%22+Defending%20against%20business%20crises%20with%20the%20help%20of%20intelligent%20agent%20based%20early%20warning%20solutions" target="_blank">[Google Scholar]</a>';
		} else {
			return '';
		}
	}


	//
	// Protected method that can be overridden by subclasses.
	//
	/**
	 * @return boolean
	 */
	protected function addGoogleScholar() {
		return true;
	}


	//
	// Abstract protected template methods to be implemented by subclasses
	//
	/**
	 * @return Filter
	 */
	abstract protected function getFilterInstance();

	/**
	 * @return string
	 */
	abstract protected function getBookResult();

	/**
	 * @return string
	 */
	abstract protected function getBookChapterResult();

	/**
	 * @return string
	 */
	abstract protected function getBookChapterWithEditorResult();

	/**
	 * @return string
	 */
	abstract protected function getJournalArticleResult();
}
?>
