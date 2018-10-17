<?php

/**
 * @file plugins/metadata/openurl10/tests/Openurl10MetadataPluginTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Openurl10MetadataPluginTest
 * @ingroup plugins_metadata_openurl10_tests
 * @see Openurl10MetadataPlugin
 *
 * @brief Test class for Openurl10MetadataPlugin.
 */

import('lib.pkp.plugins.metadata.openurl10.tests.PKPOpenurl10MetadataPluginTest');

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

