<?php

/**
 * @file plugins/metadata/nlm30/tests/filter/Nlm30Openurl10CrosswalkFilterTest.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30Openurl10CrosswalkFilterTest
 * @ingroup plugins_metadata_nlm30_tests_filter
 * @see Nlm30CitationSchemaOpenurl10CrosswalkFilter
 *
 * @brief Tests for the Nlm30CitationSchemaOpenurl10CrosswalkFilter class.
 */


import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.metadata.MetadataDescription');
import('lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema');
import('lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema');
import('lib.pkp.plugins.metadata.openurl10.schema.Openurl10JournalSchema');

class Nlm30Openurl10CrosswalkFilterTest extends PKPTestCase {
	/**
	 * Creates a test description in NLM format
	 * @return MetadataDescription
	 */
	protected function getTestNlm30Description() {
		// Create an NLM citation test description
		// 1) Authors
		$authorData1 = array(
			'given-names' => array('Given1', 'P'),
			'prefix' => 'von',
			'surname' => 'Surname1',
			'suffix' => 'suff'
		);
		$authorDescription1 = new MetadataDescription('lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema', ASSOC_TYPE_AUTHOR);
		self::assertTrue($authorDescription1->setStatements($authorData1));

		$authorData2 = array(
			'given-names' => array('Given2'),
			'surname' => 'Surname2'
		);
		$authorDescription2 = new MetadataDescription('lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema', ASSOC_TYPE_AUTHOR);
		self::assertTrue($authorDescription2->setStatements($authorData2));

		// 2) Editor
		$editorData = array(
			'surname' => 'The Editor'
		);
		$editorDescription = new MetadataDescription('lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema', ASSOC_TYPE_EDITOR);
		self::assertTrue($editorDescription->setStatements($editorData));

		// 3) The citation itself
		$citationData = array(
			'person-group[@person-group-type="author"]' => array($authorDescription1, $authorDescription2),
			'person-group[@person-group-type="editor"]' => array($editorDescription),
			'[@publication-type]' => 'journal',
			'source' => array(
				'en_US' => 'Some Journal Title',
				'de_DE' => 'Irgendein Zeitschriftentitel'
			),
			'article-title' => array(
				'en_US' => 'Some Article Title',
				'de_DE' => 'Irgendein Titel'
			),
			'date' => '2005-07-03',
			'issn[@pub-type="ppub"]' => '0694760949645',
			'fpage' => 17,
			'lpage' => 33,
			'volume' => '7',
			'issue' => '5',
			'issn[@pub-type="epub"]' => '3049674960475',
			'publisher-loc' => 'Amsterdam',
			'publisher-name' => 'de Cooper',
			'pub-id[@pub-id-type="doi"]' => '10.1234.496',
			'pub-id[@pub-id-type="publisher-id"]' => '45',
			'pub-id[@pub-id-type="coden"]' => 'coden',
			'pub-id[@pub-id-type="sici"]' => 'sici',
			'pub-id[@pub-id-type="pmid"]' => '50696',
			'uri' => 'http://some-journal.org/test/article/view/30',
			'comment' => 'a comment',
			'annotation' => 'an annotation',
		);
		$nlm30Description = new MetadataDescription('lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema', ASSOC_TYPE_CITATION);
		self::assertTrue($nlm30Description->setStatements($citationData));

		return $nlm30Description;
	}

	/**
	 * Creates a test description in Openurl10 format
	 * @return MetadataDescription
	 */
	protected function getTestOpenurl10Description() {
		$citationData = array(
			'aulast' => 'von Surname1',
			'aufirst' => 'Given1 P',
			'auinit1' => 'G',
			'auinitm' => 'P',
			'auinit' => 'GP',
			'ausuffix' => 'suff',
			'au' => array(
				0 => 'Surname1 suff, P. (Given1) von',
				1 => 'Surname2, (Given2)'
			),
			'genre' => 'article',
			'jtitle' => 'Some Journal Title',
			'atitle' => 'Some Article Title',
			'date' => '2005-07-03',
			'issn' => '0694760949645',
			'spage' => 17,
			'epage' => 33,
			'volume' => '7',
			'issue' => '5',
			'eissn' => '3049674960475',
			'artnum' => '45',
			'coden' => 'coden',
			'sici' => 'sici'
		);
		$openurl10Description = new MetadataDescription('lib.pkp.plugins.metadata.openurl10.schema.Openurl10JournalSchema', ASSOC_TYPE_CITATION);
		self::assertTrue($openurl10Description->setStatements($citationData));

		return $openurl10Description;
	}
}
?>
