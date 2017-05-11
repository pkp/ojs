<?php

/**
 * @file tests/classes/citation/CitationDAOTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CitationDAOTest
 * @ingroup tests_classes_citation
 * @see CitationDAO
 *
 * @brief Test class for CitationDAO.
 */

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.citation.CitationDAO');
import('lib.pkp.classes.citation.Citation');
import('lib.pkp.classes.metadata.MetadataDescription');

if (!defined('ASSOC_TYPE_ARTICLE')) {
	define('ASSOC_TYPE_ARTICLE', 0x9999);
}

class CitationDAOTest extends DatabaseTestCase {

	protected function getAffectedTables() {
		return array('citations', 'citation_settings');
	}

	/**
	 * @covers CitationDAO
	 */
	public function testCitationCrud() {
		$citationDao = DAORegistry::getDAO('CitationDAO'); /* @var $citationDao CitationDAO */

		$nameSchemaName = 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema';
		$nameDescription = new MetadataDescription($nameSchemaName, ASSOC_TYPE_AUTHOR);
		$nameDescription->addStatement('given-names', $value = 'Peter');
		$nameDescription->addStatement('given-names', $value = 'B');
		$nameDescription->addStatement('surname', $value = 'Bork');
		$nameDescription->addStatement('prefix', $value = 'Mr.');

		$citationSchemaName = 'lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema';
		$citationDescription = new MetadataDescription($citationSchemaName, ASSOC_TYPE_CITATION);
		$citationDescription->addStatement('person-group[@person-group-type="author"]', $nameDescription);
		$citationDescription->addStatement('article-title', $value = 'PHPUnit in a nutshell', 'en_US');
		$citationDescription->addStatement('article-title', $value = 'PHPUnit in Kürze', 'de_DE');
		$citationDescription->addStatement('date', $value = '2009-08-17');
		$citationDescription->addStatement('size', $value = 320);
		$citationDescription->addStatement('uri', $value = 'http://phpunit.org/nutshell');

		// Add a simple source description.
		$sourceDescription = new MetadataDescription($citationSchemaName, ASSOC_TYPE_CITATION);
		$sourceDescription->setDisplayName('test');
		$sourceDescription->addStatement('article-title', $value = 'a simple source description', 'en_US');
		$sourceDescription->setSequence(0);

		$citation = new Citation('raw citation');
		$citation->setAssocType(ASSOC_TYPE_ARTICLE);
		$citation->setAssocId(999999);
		$citation->setSequence(50);
		$citation->addSourceDescription($sourceDescription);
		$citation->injectMetadata($citationDescription);

		// Create citation.
		$citationId = $citationDao->insertObject($citation);
		self::assertTrue(is_numeric($citationId));
		self::assertTrue($citationId > 0);

		// Retrieve citation.
		$citationById = $citationDao->getObjectById($citationId);
		// Fix state differences for comparison.
		$citation->removeSupportedMetadataAdapter($citationSchemaName);
		$citationById->removeSupportedMetadataAdapter($citationSchemaName);
		$citationById->_extractionAdaptersLoaded = true;
		$citationById->_injectionAdaptersLoaded = true;
		$sourceDescription->setAssocId($citationId);
		$sourceDescription->removeSupportedMetadataAdapter($citationSchemaName);
		$sourceDescriptions = $citationById->getSourceDescriptions();
		$sourceDescriptions['test']->getMetadataSchema(); // this will instantiate the meta-data schema internally.
		self::assertEquals($citation, $citationById);

		$citationsByAssocIdDaoFactory = $citationDao->getObjectsByAssocId(ASSOC_TYPE_ARTICLE, 999999);
		$citationsByAssocId = $citationsByAssocIdDaoFactory->toArray();
		self::assertEquals(1, count($citationsByAssocId));
		// Fix state differences for comparison.
		$citationsByAssocId[0]->_extractionAdaptersLoaded = true;
		$citationsByAssocId[0]->_injectionAdaptersLoaded = true;
		$citationsByAssocId[0]->removeSupportedMetadataAdapter($citationSchemaName);
		$sourceDescriptionsByAssocId = $citationsByAssocId[0]->getSourceDescriptions();
		$sourceDescriptionsByAssocId['test']->getMetadataSchema(); // this will instantiate the meta-data schema internally.
		self::assertEquals($citation, $citationsByAssocId[0]);

		// Update citation.
		$citationDescription->removeStatement('date');
		$citationDescription->addStatement('article-title', $value = 'PHPUnit rápido', 'pt_BR');

		// Update source descriptions.
		$sourceDescription->addStatement('article-title', $value = 'edited source description', 'en_US', true);

		$updatedCitation = new Citation('another raw citation');
		$updatedCitation->setId($citationId);
		$updatedCitation->setAssocType(ASSOC_TYPE_ARTICLE);
		$updatedCitation->setAssocId(999998);
		$updatedCitation->setSequence(50);
		$updatedCitation->addSourceDescription($sourceDescription);
		$updatedCitation->injectMetadata($citationDescription);

		$citationDao->updateObject($updatedCitation);
		$citationAfterUpdate = $citationDao->getObjectById($citationId);
		// Fix state differences for comparison.
		$updatedCitation->removeSupportedMetadataAdapter($citationSchemaName);
		$citationAfterUpdate->removeSupportedMetadataAdapter($citationSchemaName);
		$citationAfterUpdate->_extractionAdaptersLoaded = true;
		$citationAfterUpdate->_injectionAdaptersLoaded = true;
		$sourceDescriptionsAfterUpdate = $citationAfterUpdate->getSourceDescriptions();
		$sourceDescriptionsAfterUpdate['test']->getMetadataSchema(); // this will instantiate the meta-data schema internally.
		$sourceDescription->removeSupportedMetadataAdapter($citationSchemaName);
		self::assertEquals($updatedCitation, $citationAfterUpdate);

		// Delete citation
		$citationDao->deleteObjectsByAssocId(ASSOC_TYPE_ARTICLE, 999998);
		self::assertNull($citationDao->getObjectById($citationId));
	}
}
?>
