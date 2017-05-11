<?php

/**
 * @file plugins/metadata/mods34/tests/filter/Mods34SchemaSubmissionAdapterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Mods34SchemaSubmissionAdapterTest
 * @ingroup plugins_metadata_mods34_tests_filter
 * @see Mods34SchemaSubmissionAdapter
 *
 * @brief Test class for Mods34SchemaSubmissionAdapter.
 */

import('lib.pkp.plugins.metadata.mods34.tests.filter.Mods34DescriptionTestCase');
import('lib.pkp.classes.submission.Submission');
import('lib.pkp.plugins.metadata.mods34.filter.Mods34SchemaSubmissionAdapter');

class Mods34SchemaSubmissionAdapterTest extends Mods34DescriptionTestCase {

	/**
	 * @see DatabaseTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array('authors', 'author_settings');
	}

	/**
	 * @covers Mods34SchemaSubmissionAdapter
	 */
	public function testMods34SchemaSubmissionAdapter() {
		$this->markTestSkipped('This test is currently broken (bug #5231)');

		// Test constructor.
		$adapter = new Mods34SchemaSubmissionAdapter(PersistableFilter::tempGroup(
				'metadata::plugins.metadata.mods34.schema.Mods34Schema(CITATION)',
				'class::lib.pkp.classes.submission.Submission'));
		self::assertEquals(ASSOC_TYPE_CITATION, $adapter->getAssocType());
		self::assertInstanceOf('Mods34Schema', $adapter->getMetadataSchema());
		self::assertEquals('Submission', $adapter->getDataObjectClass());

		// Instantiate a test description.
		$submissionDescription =& $this->getMods34Description();

		// Instantiate test submission.
		$submission = new Submission();
		$submission->setTitle('previous submission title', 'en_US');
		$submission->setAbstract('previous abstract', 'en_US');
		// Remove the abstract to test whether the injection into existing data works.
		// (The abstract should not be deleted.)
		$submissionDescription->removeStatement('abstract');

		// Test metadata injection (no replace).
		$resultSubmission =& $adapter->injectMetadataIntoDataObject($submissionDescription, $submission);
		$expectedResult = array(
			'cleanTitle' => array('en_US' => 'new submission title', 'de_DE' => 'neuer Titel'),
			'title' => array('en_US' => 'new submission title', 'de_DE' => 'neuer Titel'),
			'abstract' => array('en_US' => 'previous abstract'),
			'sponsor' => array('en_US' => 'Some Sponsor'),
			'dateSubmitted' => '2010-07-07',
			'language' => 'en',
			'pages' => 215,
			'coverage' => array('en_US' => 'some geography'),
			'mods34:titleInfo/nonSort' => array('en_US' => 'the', 'de_DE' => 'ein'),
			'mods34:titleInfo/subTitle' => array('en_US' => 'subtitle', 'de_DE' => 'Subtitel'),
			'mods34:titleInfo/partNumber' => array('en_US' => 'part I', 'de_DE' => 'Teil I'),
			'mods34:titleInfo/partName' => array('en_US' => 'introduction', 'de_DE' => 'Einführung'),
			'mods34:note' => array(
				'en_US' => array('0' => 'some note', '1' => 'another note'),
				'de_DE' => array('0' => 'übersetzte Anmerkung')
			),
			'mods34:subject/temporal[@encoding="w3cdtf" @point="start"]' => '1950',
			'mods34:subject/temporal[@encoding="w3cdtf" @point="end"]' => '1954'
		);
		self::assertEquals($expectedResult, $resultSubmission->getAllData());

		// Test meta-data extraction.
		$adapter = new Mods34SchemaSubmissionAdapter(PersistableFilter::tempGroup(
				'class::lib.pkp.classes.submission.Submission',
				'metadata::plugins.metadata.mods34.schema.Mods34Schema(CITATION)'));
		$extractedDescription = $adapter->extractMetadataFromDataObject($submission);
		$submissionDescription->removeStatement('recordInfo/recordCreationDate[@encoding="w3cdtf"]');
		self::assertTrue($submissionDescription->addStatement('recordInfo/recordCreationDate[@encoding="w3cdtf"]', date('Y-m-d')));
		self::assertTrue($submissionDescription->addStatement('abstract', $abstract = 'previous abstract'));

		$missingMappings = array(
			// The following properties must be mapped via
			// application-specific subclasses.
			'genre[@authority="marcgt"]',
			'originInfo/place/placeTerm[@type="text"]',
			'originInfo/place/placeTerm[@type="code" @authority="iso3166"]',
			'originInfo/publisher',
			'originInfo/dateIssued[@keyDate="yes" @encoding="w3cdtf"]',
			'originInfo/edition',
			'physicalDescription/form[@authority="marcform"]',
			'physicalDescription/internetMediaType',
			'identifier[@type="isbn"]',
			'identifier[@type="doi"]',
			'identifier[@type="uri"]',
			'location/url[@usage="primary display"]',

			// Impossible to be correctly mapped right now, see
			// corresponding comments in the adapter.
			'recordInfo/recordIdentifier[@source="pkp"]',
			'subject/topic',
		);
		foreach($missingMappings as $missingMapping) {
			$submissionDescription->removeStatement($missingMapping);
		}
		self::assertEquals($submissionDescription, $extractedDescription);
	}
}
?>
