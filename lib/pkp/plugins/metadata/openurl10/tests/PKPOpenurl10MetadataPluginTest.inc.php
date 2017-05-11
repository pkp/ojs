<?php

/**
 * @defgroup plugins_metadata_openurl10_tests OpenURL 1.0 Metadata Plugin Tests
 */

/**
 * @file plugins/metadata/openurl10/tests/PKPOpenurl10MetadataPluginTest.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPOpenurl10MetadataPluginTest
 * @ingroup plugins_metadata_openurl10_tests
 * @see PKPOpenurl10MetadataPlugin
 *
 * @brief Test class for PKPOpenurl10MetadataPlugin.
 */


import('lib.pkp.tests.plugins.metadata.MetadataPluginTestCase');

class PKPOpenurl10MetadataPluginTest extends MetadataPluginTestCase {
	/**
	 * @covers Openurl10MetadataPlugin
	 * @covers PKPOpenurl10MetadataPlugin
	 */
	public function testOpenurl10MetadataPlugin() {
		$this->executeMetadataPluginTest(
			'openurl10',
			'Openurl10MetadataPlugin',
			array(),
			array('openurl10-journal-genres', 'openurl10-book-genres')
		);
	}
}
?>
