<?php

/**
 * @file plugins/metadata/mods34/tests/filter/Mods34DescriptionXmlFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Mods34DescriptionXmlFilterTest
 * @ingroup plugins_metadata_mods34_tests_filter
 * @see Mods34DescriptionXmlFilter
 *
 * @brief Test class for Mods34DescriptionXmlFilter.
 */

import('lib.pkp.plugins.metadata.mods34.tests.filter.Mods34DescriptionTestCase');
import('lib.pkp.plugins.metadata.mods34.filter.Mods34DescriptionXmlFilter');

class Mods34DescriptionXmlFilterTest extends Mods34DescriptionTestCase {
	/**
	 * @covers Mods34DescriptionXmlFilter
	 */
	public function testMods34DescriptionXmlFilter() {
		$this->markTestSkipped('Disabled because of weird interaction with ControlledVocabEntryDAO');

		// Get the test description.
		$submissionDescription = $this->getMods34Description();

		// Instantiate filter.
		$filter = new Mods34DescriptionXmlFilter(PersistableFilter::tempGroup(
				'metadata::plugins.metadata.mods34.schema.Mods34Schema(*)',
				'xml::schema(lib/pkp/plugins/metadata/mods34/filter/mods34.xsd)'));

		// Transform MODS description to XML.
		$output = $filter->execute($submissionDescription);
		self::assertXmlStringEqualsXmlFile('./lib/pkp/tests/plugins/metadata/mods34/filter/test.xml', $output);
	}
}
?>
