<?php

/**
 * @file plugins/metadata/nlm30/tests/Nlm30MetadataPluginTest.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30MetadataPluginTest
 * @ingroup plugins_metadata_nlm30_tests
 * @see Nlm30MetadataPlugin
 *
 * @brief Test class for Nlm30MetadataPlugin.
 */

import('lib.pkp.plugins.metadata.nlm30.tests.PKPNlm30MetadataPluginTest');

class Nlm30MetadataPluginTest extends PKPNlm30MetadataPluginTest {
	/**
	 * @covers Nlm30MetadataPlugin
	 * @covers PKPNlm30MetadataPlugin
	 */
	public function testNlm30MetadataPlugin() {
		$this->markTestSkipped('Skipped because of weird class interaction with ControlledVocabDAO.');

		parent::testNlm30MetadataPlugin();
	}
}
?>
