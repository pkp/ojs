<?php

/**
 * @file plugins/metadata/nlm30/tests/filter/Nlm30NameSchemaPersonStringFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30NameSchemaPersonStringFilterTest
 * @ingroup plugins_metadata_nlm30_tests_filter
 * @see Nlm30NameSchemaPersonStringFilter
 *
 * @brief Tests for the Nlm30NameSchemaPersonStringFilter class.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.plugins.metadata.nlm30.filter.Nlm30NameSchemaPersonStringFilter');

class Nlm30NameSchemaPersonStringFilterTest extends PKPTestCase {
	/**
	 * @covers Nlm30NameSchemaPersonStringFilter
	 * @covers Nlm30PersonStringFilter
	 * @return MetadataDescription
	 */
	public function testExecuteWithSinglePersonDescription() {
		$personDescription = new MetadataDescription('lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema', ASSOC_TYPE_AUTHOR);
		$personDescription->addStatement('given-names', $givenNames = 'Machado');
		$personDescription->addStatement('prefix', $prefix = 'de');
		$personDescription->addStatement('surname', $surname = 'Assis');
		$personDescription->addStatement('suffix', $suffix = 'Jr');

		$nlm30NameSchemaPersonStringFilter = new Nlm30NameSchemaPersonStringFilter();
		self::assertEquals('Assis Jr, (Machado) de', $nlm30NameSchemaPersonStringFilter->execute($personDescription));
		return $personDescription;
	}

	/**
	 * @covers Nlm30NameSchemaPersonStringFilter
	 * @covers Nlm30PersonStringFilter
	 * @depends testExecuteWithSinglePersonDescription
	 */
	public function testExecuteWithMultiplePersonDescriptions($personDescription1) {
		$personDescription2 = new MetadataDescription('lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema', ASSOC_TYPE_AUTHOR);
		$personDescription2->addStatement('given-names', $givenNames1 = 'Bernardo');
		$personDescription2->addStatement('given-names', $givenNames2 = 'Antonio');
		$personDescription2->addStatement('surname', $surname = 'Elis');

		$personDescriptions = array($personDescription1, $personDescription2, PERSON_STRING_FILTER_ETAL);

		$nlm30NameSchemaPersonStringFilter = new Nlm30NameSchemaPersonStringFilter(PERSON_STRING_FILTER_MULTIPLE);

		self::assertEquals('Assis Jr, (Machado) de; Elis, A. (Bernardo); et al', $nlm30NameSchemaPersonStringFilter->execute($personDescriptions));

		// Test template and delimiter
		$nlm30NameSchemaPersonStringFilter->setDelimiter(':');
		$nlm30NameSchemaPersonStringFilter->setTemplate('%firstname%%initials%%prefix% %surname%%suffix%');
		self::assertEquals('Machado de Assis Jr:Bernardo A. Elis:et al', $nlm30NameSchemaPersonStringFilter->execute($personDescriptions));
	}
}
?>
