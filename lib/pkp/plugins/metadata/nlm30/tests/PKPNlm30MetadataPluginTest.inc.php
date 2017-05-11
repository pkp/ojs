<?php

/**
 * @defgroup plugins_metadata_nlm30_tests NLM 3.0 Metadata Plugin Tests
 */

/**
 * @file plugins/metadata/nlm30/tests/PKPNlm30MetadataPluginTest.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPNlm30MetadataPluginTest
 * @ingroup plugins_metadata_nlm30_tests
 * @see PKPNlm30MetadataPlugin
 *
 * @brief Test class for PKPNlm30MetadataPlugin.
 */


import('lib.pkp.tests.plugins.metadata.MetadataPluginTestCase');

class PKPNlm30MetadataPluginTest extends MetadataPluginTestCase {
	/**
	 * @covers Nlm30MetadataPlugin
	 * @covers PKPNlm30MetadataPlugin
	 */
	public function testNlm30MetadataPlugin() {
		$this->executeMetadataPluginTest(
			'nlm30',
			'Nlm30MetadataPlugin',
			array('citation=>nlm30', 'nlm30=>citation', 'plaintext=>nlm30-element-citation',
					'nlm30-element-citation=>nlm30-element-citation', 'nlm30-element-citation=>plaintext',
					'nlm30-element-citation=>nlm30-xml', 'submission=>nlm23-article-xml', 'submission=>nlm30-article-xml',
					'nlm30-article-xml=>nlm23-article-xml', 'submission=>reference-list'),
			array('nlm30-publication-types')
		);
	}
}
?>
