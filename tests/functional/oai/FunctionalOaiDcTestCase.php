<?php

/**
 * @file tests/functional/oai/FunctionalOaiDcTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalOaiDcTest
 * @ingroup tests_functional_oai
 *
 * @brief Test DC OAI output.
 */


import('lib.pkp.tests.functional.oai.FunctionalOaiBaseTestCase');

class FunctionalOaiDcTest extends FunctionalOaiBaseTestCase {

	/**
	 * SCENARIO OUTLINE: Export article in DC format over OAI
	 *   GIVEN a DOI and a URN have been assigned for a given {publishing object}
	 *    WHEN I export the corresponding article in DC format over OAI
	 *    THEN DOI and URN specific {DC fields} will be present in the OAI-message.
	 *
	 * EXAMPLES:
	 *   publishing object | DC fields
	 *   ==================|================================================
	 *   issue             | <dc:source>10.4321/t.v1i1</dc:source>
	 *   issue             | <dc:source>urn:nbn:de:0000-t.v1i19</dc:source>
	 *   article           | <dc:identifier>10.4321/t.v1i1.1</dc:identifier>
	 *   article           | <dc:identifier>urn:nbn:de:0000-t.v1i1.18</dc:identifier>
	 *   galley            | <dc:relation>10.4321/t.v1i1.g1</dc:relation>
	 *   galley            | <dc:relation>urn:nbn:de:0000-t.v1i1.1.g17</dc:relation>
	 */
	public function testDOIAndURN() {
		// Configure the web service request
		$params = array(
			'verb' => 'GetRecord',
			'metadataPrefix' => 'oai_dc',
			'identifier' => 'oai:'.Config::getVar('oai', 'repository_id').':article/1'
		);
		$this->webServiceRequest->setParams($params);

		// Check DOI node with XPath.
		$namespaces = array(
			'oai_dc' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
			'dc' => 'http://purl.org/dc/elements/1.1/'
		);
		$domXPath = $this->getXPath($namespaces);
		$testCases = array(
			'/oai:OAI-PMH/oai:GetRecord/oai:record/oai:metadata/oai_dc:dc/dc:source' => array('urn:nbn:de:0000-t.v1i19', '10.1234/t.v1i1'),
			'/oai:OAI-PMH/oai:GetRecord/oai:record/oai:metadata/oai_dc:dc/dc:identifier' => array('urn:nbn:de:0000-t.v1i1.18', '10.1234/t.v1i1.1'),
			'/oai:OAI-PMH/oai:GetRecord/oai:record/oai:metadata/oai_dc:dc/dc:relation' => array(
				'urn:nbn:de:0000-t.v1i1.1.g17', 'urn:nbn:de:0000-t.v1i1.1.s19', '10.1234/t.v1i1.1.g1', '10.1234/t.v1i1.1.s1'
			)
		);
		foreach($testCases as $xPath => $expectedDoiList) {
			$nodeList = $domXPath->query($xPath);
			self::assertGreaterThan(1, $nodeList->length, "Error while checking $xPath: No nodes found.");
			foreach($expectedDoiList as $expectedDoi) {
				for ($index = 1; $index <= $nodeList->length; $index++) {
					$node = $nodeList->item($index-1);
					// self::assertType() has been removed from PHPUnit 3.6
					// but self::assertInstanceOf() is not present in PHPUnit 3.4
					// which is our current test server version.
					// FIXME: change this to assertInstanceOf() after upgrading the
					// test server.
					self::assertTrue(is_a($node, 'DOMNode'));
					if ($node->textContent == $expectedDoi) break;
				}
				if ($index > $nodeList->length) {
					self::fail("Error while checking $xPath: Node with $expectedDoi not found.");
				}
			}
		}
	}
}

