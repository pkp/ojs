<?php

/**
 * @file tests/plugins/metadata/openurl10/Openurl10MetadataPluginTest.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Openurl10MetadataPluginTest
 * @ingroup tests_plugins_metadata_openurl10
 * @see Openurl10MetadataPlugin
 *
 * @brief Test class for Openurl10MetadataPlugin.
 */


import('lib.pkp.tests.plugins.metadata.openurl10.PKPOpenurl10MetadataPluginTest');

class Openurl10MetadataPluginTest extends PKPOpenurl10MetadataPluginTest {
	/**
	 * @covers Openurl10MetadataPlugin
	 * @covers PKPOpenurl10MetadataPlugin
	 */
	public function testOpenurl10MetadataPlugin() {
		$this->markTestSkipped('Skipped because of weird class interaction with ControlledVocabDAO.');

		parent::testOpenurl10MetadataPlugin();
	}
}
?>
