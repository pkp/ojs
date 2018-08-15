<?php

/**
 * @file plugins/generic/lucene/tests/classes/EmbeddedServerTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmbeddedServerTest
 * @ingroup plugins_generic_lucene_classes_tests
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
	 * @covers EmbeddedServer::isAvailable()
	 * @covers EmbeddedServer::isInstalled()
	 * @covers EmbeddedServer::_canExecScripts()
	 */
	public function testAvailability() {
		$this->markTestSkipped('Needs fixing');

		// On the test instance the embedded server should
		// be available.
		self::assertTrue($this->embeddedServer->isAvailable());
	}

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
			self::assertTrue($this->embeddedServer->stopAndWait());
		}
	}
}

