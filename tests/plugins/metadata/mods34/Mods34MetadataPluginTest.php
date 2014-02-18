<?php

/**
 * @file tests/plugins/metadata/mods34/Mods34MetadataPluginTest.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Mods34MetadataPluginTest
 * @ingroup tests_plugins_metadata_mods34
 * @see Mods34MetadataPlugin
 *
 * @brief Test class for Mods34MetadataPlugin.
 */


import('lib.pkp.tests.plugins.metadata.mods34.PKPMods34MetadataPluginTest');

class Mods34MetadataPluginTest extends PKPMods34MetadataPluginTest {
	/**
	 * @covers Mods34MetadataPlugin
	 * @covers PKPMods34MetadataPlugin
	 */
	public function testMods34MetadataPlugin() {
		parent::testMods34MetadataPlugin(
				array('article=>mods34', 'mods34=>article'));
	}
}
?>
