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
	}
}
?>