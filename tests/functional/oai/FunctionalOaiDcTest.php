<?php

/**
 * @file tests/functional/oai/FunctionalOaiDcTest.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalOaiDcTest
 * @ingroup tests_functional_oai
 *
 * @brief Test DC OAI output.
 */


import('lib.pkp.tests.functional.oai.FunctionalOaiBaseTestCase');

class FunctionalOaiDcTest extends OaiWebServiceTestCase {

	/**
	 * SCENARIO: Export article in DC format over OAI
	 *   GIVEN a DOI has been assigned for a given {publishing object}
	 *    WHEN I export the corresponding article in DC format over OAI
	 *    THEN DOI-specific {DC fields} will be present in the OAI-message.
	 *
	 * EXAMPLES:
	 *   publishing object | DC fields
	 *   ==================|================================================
	 *   issue             | <dc:source>10.4321/t.v1i1</dc:source>
	 *   article           | <dc:identifier>10.4321/t.v1i1.1</dc:identifier>
	 *   galley            | <dc:relation>10.4321/t.v1i1.g1</dc:relation>
	 *   supp-file         | <dc:relation>10.4321/t.v1i1.s1</dc:relation>
	 */
	public function testDoi() {
		// Configure the web service request
		$this->webServiceRequest->setParams($params = array(
			'verb' => 'GetRecord',
			'metadataPrefix' => 'oai_dc',
			'identifier' => 'oai:ojs.ojs-test.cedis.fu-berlin.de:article/1'
		));

		// Check DOI node with XPath.
		$namespaces = array(
			'oai_dc' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
			'dc' => 'http://purl.org/dc/elements/1.1/'
		);
		$xPath = $this->getXPath($namespaces);
		self::assertEquals('10.1234/t.v1i1.1', $xPath->evaluate('string(/oai:OAI-PMH/oai:GetRecord/oai:record/oai:metadata/oai_dc:dc/dc:identifier[2])'));

		$this->markTestIncomplete('Export article in DC format over OAI');
	}
}
?>