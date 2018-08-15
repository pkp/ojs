<?php

/**
 * @file plugins/metadata/mods34/tests/Mods34MetadataPluginTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Mods34MetadataPluginTest
 * @ingroup plugins_metadata_mods34_tests
 * @see Mods34MetadataPlugin
 *
 * @brief Test class for Mods34MetadataPlugin.
 */

import('lib.pkp.plugins.metadata.mods34.tests.PKPMods34MetadataPluginTest');

class Mods34MetadataPluginTest extends PKPMods34MetadataPluginTest {
	/**
	 * @covers Mods34MetadataPlugin
	 * @covers PKPMods34MetadataPlugin
	 */
	public function testMods34MetadataPlugin($appSpecificFilters = array('article=>mods34', 'mods34=>article')) {
		$this->markTestSkipped('Skipped because of weird class interaction with ControlledVocabDAO.');

		parent::testMods34MetadataPlugin($appSpecificFilters);
	}
}

