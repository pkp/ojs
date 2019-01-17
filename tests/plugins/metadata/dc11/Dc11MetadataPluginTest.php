<?php

/**
 * @defgroup tests_plugins_metadata_dc11
 */

/**
 * @file tests/plugins/metadata/dc11/Dc11MetadataPluginTest.inc.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Dc11MetadataPluginTest
 * @ingroup tests_plugins_metadata_dc11
 * @see Dc11MetadataPlugin
 *
 * @brief Test class for Dc11MetadataPlugin.
 */


import('lib.pkp.tests.plugins.metadata.dc11.PKPDc11MetadataPluginTest');

class Dc11MetadataPluginTest extends PKPDc11MetadataPluginTest {
	/**
	 * @covers Dc11MetadataPlugin
	 * @covers PKPDc11MetadataPlugin
	 */
	public function testDc11MetadataPlugin() {
		parent::testDc11MetadataPlugin(array('article=>dc11'));
	}
}
?>
