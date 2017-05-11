<?php

/**
 * @defgroup plugins_metadata_dc11_tests Dublin Core 1.1 Metadata Plugin Tests
 */

/**
 * @file plugins/metadata/dc11/tests/PKPDc11MetadataPluginTest.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPDc11MetadataPluginTest
 * @ingroup plugins_metadata_dc11_tests
 * @see PKPDc11MetadataPlugin
 *
 * @brief Test class for PKPDc11MetadataPlugin.
 */


import('lib.pkp.tests.plugins.metadata.MetadataPluginTestCase');

class PKPDc11MetadataPluginTest extends MetadataPluginTestCase {
	/**
	 * @covers Dc11MetadataPlugin
	 * @covers PKPDc11MetadataPlugin
	 */
	public function testDc11MetadataPlugin($appSpecificFilters) {
		$this->executeMetadataPluginTest(
			'dc11',
			'Dc11MetadataPlugin',
			$appSpecificFilters,
			array()
		);
	}
}
?>
