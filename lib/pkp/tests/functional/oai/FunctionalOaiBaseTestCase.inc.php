<?php

/**
 * @file lib/pkp/tests/functional/oai/FunctionalOaiBaseTestCase.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalOaiBaseTestCase
 * @ingroup tests
 *
 * @brief Base test class for OAI functional tests.
 */


import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.xslt.XSLTransformer');
import('lib.pkp.classes.webservice.WebServiceRequest');
import('lib.pkp.classes.webservice.XmlWebService');

class FunctionalOaiBaseTestCase extends DatabaseTestCase {
	protected $baseUrl, $webService, $webServiceRequest;

	protected function getAffectedTables() {
		return array(
			'issue_settings', 'submission_settings',
			'submission_galley_settings', 'article_supp_file_settings'
		);
	}

	public function setUp() {
		// Retrieve and check configuration.
		$webtestBaseUrl = getenv('BASEURL');
		if (empty($webtestBaseUrl)) {
			$this->markTestSkipped(
				'Please set the BASEURL environment variable ' .
				'to the base url of your test server.'
			);
		}
		$this->baseUrl = $webtestBaseUrl . '/index.php/test/oai';;

		// Instantiate a web service.
		$this->webService = new XmlWebService();
		$this->webService->setReturnType(XSL_TRANSFORMER_DOCTYPE_DOM);

		// Instantiate a web service request.
		$this->webServiceRequest = new WebServiceRequest($this->baseUrl);

		parent::setUp();
	}

	protected function &getXPath($namespaces) {
		$namespaces['oai'] = 'http://www.openarchives.org/OAI/2.0/';

		// Call the web service
		$dom =& $this->webService->call($this->webServiceRequest);

		// Instantiate and configure XPath object.
		$xPath = new DOMXPath($dom);
		foreach ($namespaces as $prefix => $uri) {
			$xPath->registerNamespace($prefix, $uri);
		}

		return $xPath;
	}
}
?>
