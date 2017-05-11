<?php

/**
 * @file plugins/metadata/nlm30/tests/filter/Nlm30Nlm23CrosswalkFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30Nlm23CrosswalkFilterTest
 * @ingroup plugins_metadata_nlm30_tests_filter
 * @see Nlm30Nlm23CrosswalkFilter
 *
 * @brief Tests for the Nlm30Nlm23CrosswalkFilterTest class.
 */

import('lib.pkp.plugins.metadata.nlm30.tests.filter.Nlm30XmlFilterTestCase');

class Nlm30Nlm23CrosswalkFilterTest extends Nlm30XmlFilterTestCase {
	/**
	 * @covers Nlm30Nlm23CrosswalkFilter
	 */
	public function testExecute() {
		$this->markTestSkipped('Weird class interaction with ControlledVocabEntryDAO leads to failure');

		// Instantiate test meta-data for a citation. This must use the complete
		// available schema (although in practice this doesn't make sense) so that
		// we can make sure all tags are correctly converted.
		import('lib.pkp.classes.metadata.MetadataDescription');
		$nameSchemaName = 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema';
		$nameDescription = new MetadataDescription($nameSchemaName, ASSOC_TYPE_AUTHOR);
		$nameDescription->addStatement('given-names', $value = 'Peter');
		$nameDescription->addStatement('given-names', $value = 'B');
		$nameDescription->addStatement('surname', $value = 'Bork');
		$nameDescription->addStatement('prefix', $value = 'Mr.');
		$nameDescription->addStatement('suffix', $value = 'Jr');

		$citationSchemaName = 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema';
		$citationDescription = new MetadataDescription($citationSchemaName, ASSOC_TYPE_CITATION);

		$citationDescription->addStatement('person-group[@person-group-type="author"]', $nameDescription);
		$citationDescription->addStatement('person-group[@person-group-type="editor"]', $nameDescription);
		$citationDescription->addStatement('article-title', $value = 'PHPUnit in a nutshell', 'en_US');
		$citationDescription->addStatement('source', $value = 'PHPUnit in a nutshell', 'en_US');
		$citationDescription->addStatement('date', $value = '2009-08-17');
		$citationDescription->addStatement('date-in-citation[@content-type="access-date"]', $value = '2009-08');
		$citationDescription->addStatement('issue', $value = 5);
		$citationDescription->addStatement('volume', $value = 6);
		$citationDescription->addStatement('season', $value = 'Summer');
		$citationDescription->addStatement('chapter-title', $value = 'Introduction');
		$citationDescription->addStatement('edition', $value = '2nd edition');
		$citationDescription->addStatement('series', $value = 7);
		$citationDescription->addStatement('supplement', $value = 'Summer Special');
		$citationDescription->addStatement('conf-date', $value = '2009-08-17');
		$citationDescription->addStatement('conf-loc', $value = 'Helsinki');
		$citationDescription->addStatement('conf-name', $value = 'PHPUnit Hackfest');
		$citationDescription->addStatement('conf-sponsor', $value = 'Basti himself');
		$citationDescription->addStatement('institution', $value = 'PKP');
		$citationDescription->addStatement('fpage', $value = 9);
		$citationDescription->addStatement('lpage', $value = 312);
		$citationDescription->addStatement('size', $value = 320);
		$citationDescription->addStatement('publisher-loc', $value = 'Vancouver');
		$citationDescription->addStatement('publisher-name', $value = 'SFU');
		$citationDescription->addStatement('isbn', $value = '123456789');
		$citationDescription->addStatement('issn[@pub-type="ppub"]', $value = '987654321');
		$citationDescription->addStatement('issn[@pub-type="epub"]', $value = '111111111');
		$citationDescription->addStatement('pub-id[@pub-id-type="doi"]', $value = '10420/39406');
		$citationDescription->addStatement('pub-id[@pub-id-type="publisher-id"]', $value = 'xyz');
		$citationDescription->addStatement('pub-id[@pub-id-type="coden"]', $value = 'abc');
		$citationDescription->addStatement('pub-id[@pub-id-type="sici"]', $value = 'def');
		$citationDescription->addStatement('pub-id[@pub-id-type="pmid"]', $value = '999999');
		$citationDescription->addStatement('uri', $value = 'http://phpunit.org/nutshell');
		$citationDescription->addStatement('comment', $value = 'just nonsense');
		$citationDescription->addStatement('annotation', $value = 'more nonsense');
		$citationDescription->addStatement('[@publication-type]', $value = 'conf-proc');

		$citation =& $this->getCitation($citationDescription);

		// Persist one copy of the citation for testing.
		$citationDao =& $this->getCitationDao();
		$citation->setSequence(1);
		$citation->setCitationState(CITATION_APPROVED);
		$citationId = $citationDao->insertObject($citation);
		self::assertTrue(is_numeric($citationId));
		self::assertTrue($citationId > 0);

		// Construct the expected output.
		$expectedOutput = '';

		// Prepare NLM 3.0 input.
		$mockSubmission =& $this->getTestSubmission();
		import('lib.pkp.plugins.metadata.nlm30.filter.PKPSubmissionNlm30XmlFilter');
		$nlm30Filter = new PKPSubmissionNlm30XmlFilter(PersistableFilter::tempGroup(
				'class::lib.pkp.classes.submission.Submission',
				'xml::*'));
		$nlm30Xml = $nlm30Filter->execute($mockSubmission);

		// Test the downgrade filter.
		import('lib.pkp.classes.xslt.XSLTransformationFilter');
		// FIXME: Add NLM 2.3 and 3.0 tag set schema validation as soon as we implement the full tag set, see #5648.
		$downgradeFilter = new XSLTransformationFilter(
			PersistableFilter::tempGroup('xml::*', 'xml::*'),
			'NLM 3.0 to 2.3 ref-list downgrade');
		$downgradeFilter->setXSLFilename('lib/pkp/plugins/metadata/nlm30/filter/nlm30-to-23-ref-list.xsl');
		$nlm30Xml = $downgradeFilter->execute($nlm30Xml);

		self::assertXmlStringEqualsXmlFile('./lib/pkp/tests/plugins/metadata/nlm30/filter/sample-nlm23-citation.xml', $nlm30Xml);
	}
}
?>
