<?php

/**
 * @file tests/plugins/generic/lucene/classes/EmbeddedServerTest.inc.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmbeddedServerTest
 * @ingroup tests_plugins_generic_lucene_classes
 * @see EmbeddedServer
 *
 * @brief Test class for the EmbeddedServer class
 */


import('lib.pkp.tests.PKPTestCase');
import('plugins.generic.lucene.classes.EmbeddedServer');

class EmbeddedServerTest extends PKPTestCase {
	/** @var EmbeddedServer */
	private $embeddedServer;

	//
	// Implementing protected template methods from PKPTestCase
	//
	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();

		// Instantiate the class for testing.
		$this->embeddedServer = new EmbeddedServer();
	}


	//
	// Unit tests
	//
	/**
	 * @covers EmbeddedServer
	 */
	public function testStartStopIsRunning() {
		$this->markTestSkipped('Not currently working in CI environment.');

		// Check whether the server is currently running.
		$running = $this->embeddedServer->isRunning();
		if ($running) {
			// If the server is running we stop it, then start it.

			// Stop the server.
			self::assertTrue($this->embeddedServer->stopAndWait());

			// Restart the server.
			self::assertTrue($this->embeddedServer->start());
			self::assertTrue($this->embeddedServer->isRunning());
		} else {
			// If the server is stopped, we start it, then stop it.

			// Start the server.
			self::assertTrue($this->embeddedServer->start());
			self::assertTrue($this->embeddedServer->isRunning());

			// Stop the server.
			self::assertTrue($this->embeddedServer->stop());
			// Give the server time to actually go down.
			while($this->embeddedServer->isRunning()) sleep(1);
		}
	}
}
?>
