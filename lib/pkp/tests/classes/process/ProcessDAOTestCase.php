<?php

/**
 * @file tests/classes/process/ProcessDAOTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProcessDAOTest
 * @ingroup tests_classes_process
 * @see ProcessDAO
 *
 * @brief Test class for ProcessDAO.
 *
 * We cannot test ProcessDAO::spawnProcess() as this would
 * actually call external web URLs which we want to avoid.
 */

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.process.ProcessDAO');
import('lib.pkp.classes.process.Process');

class ProcessDAOTest extends DatabaseTestCase {
	private
		$processDao,
		$testProcessType = -5; // Use a process type that doesn't conflict.


	/**
	 * @see DatabaseTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array('processes');
	}

	/**
	 * @see DatabaseTestCase::setUp()
	 */
	protected function setUp() {
		$this->processDao = DAORegistry::getDAO('ProcessDAO');
		parent::setUp();
	}

	/**
	 * @covers ProcessDAO
	 * @covers Process
	 */
	public function testProcessCrud() {
		$this->markTestIncomplete();
		// Create two test processes
		$processes = array();
		for ($i = 0; $i < 2; $i++) {
			$process =& $this->processDao->insertObject($this->testProcessType, 2);
			// self::assertType() has been removed from PHPUnit 3.6
			// but self::assertInstanceOf() is not present in PHPUnit 3.4
			// which is our current test server version.
			// FIXME: change this to assertInstanceOf() after upgrading the
			// test server.
			self::assertTrue(is_a($process, 'Process'));
			self::assertEquals(23, strlen($process->getId()));
			self::assertTrue(((integer)$process->getTimeStarted()) > 0);
			$processes[] = $process;
		}

		// Inserting a third process should not be allowed
		// due to the parallelism constraint.
		$process =& $this->processDao->insertObject($this->testProcessType, 2);
		self::assertFalse($process);
		self::assertEquals(2, $this->processDao->getNumberOfObjectsByProcessType($this->testProcessType));

		// Retrieve one process.
		$processById = $this->processDao->getObjectById($processes[0]->getId());
		self::assertEquals($processes[0], $processById);

		// Manually turn one process into a zombie process.
		$this->processDao->update('UPDATE processes SET time_started = 0 WHERE process_id = ?', $processes[0]->getId());

		// Zombie removal has been called during insertObject(). As it should
		// only be executed once per request it should remain without effect if
		// we don't call it with the force-parameter set to true.
		$this->processDao->deleteZombies();
		self::assertEquals(2, $this->processDao->getNumberOfObjectsByProcessType($this->testProcessType));

		// Test forced zombie removal.
		$this->processDao->deleteZombies(true);
		self::assertEquals(1, $this->processDao->getNumberOfObjectsByProcessType($this->testProcessType));

		// Remove the remaining process.
		$this->processDao->deleteObject($processes[1]);
		self::assertNull($this->processDao->getObjectById($processes[1]->getId()));
		self::assertEquals(0, $this->processDao->getNumberOfObjectsByProcessType($this->testProcessType));
	}

	/**
	 * @covers ProcessDAO::authorizeProcess
	 */
	public function testAuthorization() {
		$this->markTestIncomplete();
		// Insert a test process.
		$process =& $this->processDao->insertObject($this->testProcessType, 2);
		self::assertInstanceOf('Process', $process);
		$processId = $process->getId();

		// Try to authorize with an incorrect process id.
		self::assertFalse($this->processDao->authorizeProcess('some invalid id'));

		// Authorize with a correct process id.
		self::assertTrue($this->processDao->authorizeProcess($processId));

		// Trying to authorize a second time with the same process id shouldn't work
		// but also should leave the process entry in the table.
		self::assertFalse($this->processDao->authorizeProcess($processId));
		self::assertEquals(1, $this->processDao->getNumberOfObjectsByProcessType($this->testProcessType));

		// Clean up.
		$this->processDao->deleteObjectById($processId);

		// Insert another test process.
		$process =& $this->processDao->insertObject($this->testProcessType, 2);
		self::assertInstanceOf('Process', $process);
		$processId = $process->getId();

		// Artificially change the start time.
		$this->processDao->update('UPDATE processes SET time_started = ? WHERE process_id = ?',
				array(time() - PROCESS_MAX_KEY_VALID - 1, $processId));

		// Trying to authorize with a correct but expired process id shouldn't work.
		// The process entry should be automatically removed in this case.
		self::assertFalse($this->processDao->authorizeProcess($processId));
		self::assertEquals(0, $this->processDao->getNumberOfObjectsByProcessType($this->testProcessType));
	}

	/**
	 * @covers ProcessDAO::canContinue
	 */
	public function testCanContinue() {
		$this->markTestIncomplete();
		// Insert a test process.
		$process =& $this->processDao->insertObject($this->testProcessType, 2);
		self::assertInstanceOf('Process', $process);
		$processId = $process->getId();

		self::assertTrue($this->processDao->canContinue($processId));
		self::assertEquals(1, $this->processDao->getNumberOfObjectsByProcessType($this->testProcessType));

		// Artificially change the start time.
		$this->processDao->update('UPDATE processes SET time_started = ? WHERE process_id = ?',
				array(time() - PROCESS_MAX_EXECUTION_TIME - 1, $processId));

		// Now the process should no longer be able to continue
		// and it should have been removed from the process entry list.
		self::assertFalse($this->processDao->canContinue($processId));
		self::assertEquals(0, $this->processDao->getNumberOfObjectsByProcessType($this->testProcessType));

		// Trying to check a non-existent process also should not work.
		self::assertFalse($this->processDao->canContinue($processId));
	}
}
?>
