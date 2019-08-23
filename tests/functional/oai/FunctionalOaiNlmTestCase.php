<?php

/**
 * @file tests/functional/oai/FunctionalOaiNlmTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalOaiNlmTest
 * @ingroup tests_functional_oai
 *
 * @brief Test NLM OAI output.
 */


import('lib.pkp.tests.functional.oai.FunctionalOaiBaseTestCase');

class FunctionalOaiNlmTest extends FunctionalOaiBaseTestCase {
	public function testDoi() {
		// Configure the web service request
		$params = array(
			'verb' => 'GetRecord',
			'metadataPrefix' => 'nlm',
			'identifier' => 'oai:'.Config::getVar('oai', 'repository_id').':article/1'
		);
		$this->webServiceRequest->setParams($params);

		// Check DOI node with XPath.
		$namespaces = array(
			'nlm' => 'http://dtd.nlm.nih.gov/publishing/2.3'
		);
		$xPath = $this->getXPath($namespaces);
		self::assertEquals('10.1234/t.v1i1.1', $xPath->evaluate('string(/oai:OAI-PMH/oai:GetRecord/oai:record/oai:metadata/nlm:article/nlm:front/nlm:article-meta/nlm:article-id[@pub-id-type="doi"])'));
	}
}

