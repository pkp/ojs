<?php

/**
 * @defgroup plugins_metadata_mods34_tests_filter MODS 3.4 Metadata Filter Plugin Tests
 */

/**
 * @file plugins/metadata/mods34/tests/filter/Mods34DescriptionTestCase.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Mods34DescriptionTestCase
 * @ingroup plugins_metadata_mods34_tests_filter
 * @see Mods34Schema
 *
 * @brief Base test case for tests that involve a MODS MetadataDescription.
 */

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.metadata.MetadataDescription');

class Mods34DescriptionTestCase extends DatabaseTestCase {
	/**
	 * Prepare a MODS description that covers as much data as possible.
	 * @return MetadataDescription
	 */
	public function getMods34Description() {
		// Author
		$authorDescription = new MetadataDescription('lib.pkp.plugins.metadata.mods34.schema.Mods34NameSchema', ASSOC_TYPE_AUTHOR);
		self::assertTrue($authorDescription->addStatement('[@type]', $nameType = 'personal'));
		self::assertTrue($authorDescription->addStatement('namePart[@type="family"]', $familyName = 'some family name'));
		self::assertTrue($authorDescription->addStatement('namePart[@type="given"]', $givenName = 'given names'));
		self::assertTrue($authorDescription->addStatement('namePart[@type="termsOfAddress"]', $terms = 'Jr'));
		self::assertTrue($authorDescription->addStatement('namePart[@type="date"]', $date = '1900-1988'));
		self::assertTrue($authorDescription->addStatement('affiliation', $affiliation = 'affiliation'));
		self::assertTrue($authorDescription->addStatement('role/roleTerm[@type="code" @authority="marcrelator"]', $authorRole = 'aut'));

		// Sponsor
		$sponsorDescription = new MetadataDescription('lib.pkp.plugins.metadata.mods34.schema.Mods34NameSchema', ASSOC_TYPE_AUTHOR);
		self::assertTrue($sponsorDescription->addStatement('[@type]', $nameType = 'corporate'));
		self::assertTrue($sponsorDescription->addStatement('namePart', $namePart = 'Some Sponsor'));
		self::assertTrue($sponsorDescription->addStatement('role/roleTerm[@type="code" @authority="marcrelator"]', $sponsorRole = 'spn'));

		$mods34Description = new MetadataDescription('plugins.metadata.mods34.schema.Mods34Schema', ASSOC_TYPE_CITATION);
		self::assertTrue($mods34Description->addStatement('titleInfo/nonSort', $titleNonSort = 'the'));
		self::assertTrue($mods34Description->addStatement('titleInfo/title', $title = 'new submission title'));
		self::assertTrue($mods34Description->addStatement('titleInfo/subTitle', $subTitle = 'subtitle'));
		self::assertTrue($mods34Description->addStatement('titleInfo/partNumber', $partNumber = 'part I'));
		self::assertTrue($mods34Description->addStatement('titleInfo/partName', $partName = 'introduction'));

		self::assertTrue($mods34Description->addStatement('titleInfo/nonSort', $titleNonSort = 'ein', 'de_DE'));
		self::assertTrue($mods34Description->addStatement('titleInfo/title', $title = 'neuer Titel', 'de_DE'));
		self::assertTrue($mods34Description->addStatement('titleInfo/subTitle', $subTitle = 'Subtitel', 'de_DE'));
		self::assertTrue($mods34Description->addStatement('titleInfo/partNumber', $partNumber = 'Teil I', 'de_DE'));
		self::assertTrue($mods34Description->addStatement('titleInfo/partName', $partName = 'Einführung', 'de_DE'));

		self::assertTrue($mods34Description->addStatement('name', $authorDescription));
		self::assertTrue($mods34Description->addStatement('name', $sponsorDescription));

		self::assertTrue($mods34Description->addStatement('typeOfResource', $typeOfResource = 'text'));

		self::assertTrue($mods34Description->addStatement('genre[@authority="marcgt"]', $marcGenre = 'book'));

		self::assertTrue($mods34Description->addStatement('originInfo/place/placeTerm[@type="text"]', $publisherPlace = 'Vancouver'));
		self::assertTrue($mods34Description->addStatement('originInfo/place/placeTerm[@type="code" @authority="iso3166"]', $publisherCountry = 'CA'));
		self::assertTrue($mods34Description->addStatement('originInfo/publisher', $publisherName = 'Public Knowledge Project'));
		self::assertTrue($mods34Description->addStatement('originInfo/dateIssued[@keyDate="yes" @encoding="w3cdtf"]', $publicationDate = '2010-09'));
		self::assertTrue($mods34Description->addStatement('originInfo/dateCreated[@encoding="w3cdtf"]', $publisherName = '2010-07-07'));
		self::assertTrue($mods34Description->addStatement('originInfo/copyrightDate[@encoding="w3cdtf"]', $publisherName = '2010'));
		self::assertTrue($mods34Description->addStatement('originInfo/edition', $edition = 'second revised edition'));
		self::assertTrue($mods34Description->addStatement('originInfo/edition', $edition = 'zweite überarbeitete Ausgabe', 'de_DE'));

		self::assertTrue($mods34Description->addStatement('language/languageTerm[@type="code" @authority="iso639-2b"]', $submissionLanguage = 'eng'));

		self::assertTrue($mods34Description->addStatement('physicalDescription/form[@authority="marcform"]', $publicationForm = 'electronic'));
		self::assertTrue($mods34Description->addStatement('physicalDescription/internetMediaType', $mimeType = 'application/pdf'));
		self::assertTrue($mods34Description->addStatement('physicalDescription/extent', $pages = 215));

		self::assertTrue($mods34Description->addStatement('abstract', $abstract1 = 'some abstract'));
		self::assertTrue($mods34Description->addStatement('abstract', $abstract2 = 'eine Zusammenfassung', 'de_DE'));

		self::assertTrue($mods34Description->addStatement('note', $note1 = 'some note'));
		self::assertTrue($mods34Description->addStatement('note', $note2 = 'another note'));
		self::assertTrue($mods34Description->addStatement('note', $note3 = 'übersetzte Anmerkung', 'de_DE'));

		self::assertTrue($mods34Description->addStatement('subject/topic', $topic1 = 'some subject'));
		self::assertTrue($mods34Description->addStatement('subject/topic', $topic2 = 'some other subject'));
		self::assertTrue($mods34Description->addStatement('subject/topic', $topic3 = 'ein Thema', 'de_DE'));
		self::assertTrue($mods34Description->addStatement('subject/geographic', $geography = 'some geography'));
		self::assertTrue($mods34Description->addStatement('subject/temporal[@encoding="w3cdtf" @point="start"]', $timeStart = '1950'));
		self::assertTrue($mods34Description->addStatement('subject/temporal[@encoding="w3cdtf" @point="end"]', $timeEnd = '1954'));

		self::assertTrue($mods34Description->addStatement('identifier[@type="isbn"]', $isbn = '01234567890123'));
		self::assertTrue($mods34Description->addStatement('identifier[@type="doi"]', $doi = '40/2010ff'));
		self::assertTrue($mods34Description->addStatement('identifier[@type="uri"]', $uri = 'urn://xyz.resolver.org/12345'));

		self::assertTrue($mods34Description->addStatement('location/url[@usage="primary display"]', $url = 'http://www.sfu.ca/test-article'));

		self::assertTrue($mods34Description->addStatement('recordInfo/recordCreationDate[@encoding="w3cdtf"]', $recordDate = '2010-12-24'));
		self::assertTrue($mods34Description->addStatement('recordInfo/recordIdentifier[@source="pkp"]', $articleId = '3049'));
		self::assertTrue($mods34Description->addStatement('recordInfo/languageOfCataloging/languageTerm[@authority="iso639-2b"]', $languageOfCataloging = 'eng'));

		return $mods34Description;
	}
}
?>
