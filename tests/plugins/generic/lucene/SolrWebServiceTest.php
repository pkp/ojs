<?php

/**
 * @file tests/plugins/generic/lucene/SolrWebServiceTest.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SolrWebServiceTest
 * @ingroup tests_plugins_generic_lucene
 * @see SolrWebService
 *
 * @brief Test class for the SolrWebService class
 */


import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.lucene.SolrWebService');
import('plugins.generic.lucene.EmbeddedServer');

class SolrWebServiceTest extends PKPTestCase {
	/** @var SolrWebService */
	private $solrWebService;

	//
	// Implementing protected template methods from PKPTestCase
	//
	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();

		// Instantiate our web service for testing.
		$this->solrWebService = new SolrWebService();
	}


	//
	// Unit tests
	//
	/**
	 * @covers SolrWebService
	 */
	public function testGetServerStatus() {
		// Make sure the server has been started.
		$embeddedServer = new EmbeddedServer();
		if (!$embeddedServer->isRunning()) {
			$embeddedServer->start();
		}
		do {
			sleep(1);
			$result = $this->solrWebService->getServerStatus();
		} while ($result['status'] != SOLR_STATUS_ONLINE);
		self::assertEquals(
			array(
				'status' => SOLR_STATUS_ONLINE,
				'message' => 'Index with 1464 documents online.'
			),
			$result
		);

		// Stop the server, then test again.
		$embeddedServer->stop();
		while($embeddedServer->isRunning()) sleep(1);
		self::assertEquals(
			array(
				'status' => SOLR_STATUS_OFFLINE,
				'message' => 'Solr server not reachable. Is the solr server running? Does the configured search handler point to the right URL?'
			),
			$this->solrWebService->getServerStatus()
		);
	}
}
?>