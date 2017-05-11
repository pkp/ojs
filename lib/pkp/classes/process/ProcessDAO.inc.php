<?php
/**
 * @file classes/process/ProcessDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProcessDAO
 * @ingroup process
 * @see Process
 *
 * @brief Operations for retrieving and modifying process data.
 *
 * Parallel processes are pooled. This defines a given number
 * of process slots per pool. Once these slots are occupied, no
 * new processes can be spawned for a given process type.
 *
 * The process ID is not an integer but a globally unique string
 * identifier that has to fulfill the following additional functions:
 * 1) It is used as a one-time-key to authorize the the web
 *    request spawning a new process. It therefore has to be
 *    random enough to avoid it being guessed by an outsider.
 * 2) We also use the process ID as a unique token to implement
 *    an atomic locking strategy to avoid race conditions when
 *    executing processes in parallel.
 *
 * We use the uniqid() method to genereate one-time keys. This is not
 * really cryptographically secure but it probably makes it difficult
 * enough to guess the key to avoid abuse.
 * This assumes that we don't start using processes for more sensitive
 * tasks. If that happens we'd need to improve the randomness of the
 * process id (e.g. via /dev/urandom or similar).
 *
 * This usage of the processes table also explains why there is no
 * updateObject() method in this DAO. If you need a process with different
 * characteristics then insert a new one and delete stale processes.
 */


// Define the max number of seconds a process is allowed to run.
// We assume that no process should run longer than
// 15 minutes. So we clean all processes that have a time
// stamp of more than 15 minutes ago. Running processes should check
// regularly (about once per minute) whether "their" process entry
// is still there. If not they are required to halt immediately.
// NB: Don't set this timeout much shorter as this may
// potentially cause more parallel processes being spawned
// than allowed.
define('PROCESS_MAX_EXECUTION_TIME', 900);

// Cap the max. number of parallel process to avoid server
// flooding in case of an error.
define('PROCESS_MAX_PARALLELISM', 20);

// The max. number of seconds a one-time-key will be kept valid.
// This defines the potential window of attack if an attacker
// manages to guess a key. Defining this time too short can lead
// to problems when networks are slow.
define('PROCESS_MAX_KEY_VALID', 10);


import('lib.pkp.classes.process.Process');

class ProcessDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Insert a new process.
	 * @param $processType integer one of the PROCESS_TYPE_* constants
	 * @param $maxParallelism integer the max. number
	 *  of parallel processes allowed for the given
	 *  process type.
	 * @param $additionalData optional Optional data to store with process.
	 * @return Process the new process instance, boolean
	 *  false if there are too many parallel processes.
	 */
	function &insertObject($processType, $maxParallelism, $additionalData = null) {
		// Free processing slots occupied by zombie processes.
		$this->deleteZombies();

		// Cap the parallelism to the max. parallelism.
		$maxParallelism = min($maxParallelism, PROCESS_MAX_PARALLELISM);

		// Check whether we're allowed to spawn another process.
		$currentParallelism = $this->getNumberOfObjectsByProcessType($processType);
		if ($currentParallelism >= $maxParallelism) {
			$falseVar = false;
			return $falseVar;
		}

		// We create a process instance from the given data.
		$process = $this->newDataObject();
		$process->setProcessType($processType);

		// Generate a new process ID. See classdoc for process ID
		// requirements.
		$process->setId(uniqid('', true));

		// Generate the timestamp.
		$process->setTimeStarted(time());

		// Persist the process.
		$this->update(
			sprintf('INSERT INTO processes
				(process_id, process_type, time_started, obliterated, additional_data)
				VALUES
				(?, ?, ?, 0, ?)'),
			array(
				$process->getId(),
				(int) $process->getProcessType(),
				(int) $process->getTimeStarted(),
				serialize($additionalData)
			)
		);
		$process->setObliterated(false);
		return $process;
	}

	/**
	 * Get a process by ID.
	 * @param $processId string
	 * @return Process
	 */
	function getObjectById($processId) {
		$result = $this->retrieve(
			'SELECT process_id, process_type, time_started, obliterated, additional_data FROM processes WHERE process_id = ?',
			$processId
		);

		$process = null;
		if ($result->RecordCount() != 0) {
			$process = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();

		return $process;
	}

	/**
	 * Determine the number of currently running
	 * processes for a given process type.
	 * @param $processType
	 * @return integer
	 */
	function getNumberOfObjectsByProcessType($processType) {
		// Find the number of processes for the
		// given process type.
		$result = $this->retrieve(
			'SELECT COUNT(*) AS running_processes
			 FROM processes
			 WHERE process_type = ?',
			(int) $processType
		);

		$runningProcesses = 0;
		if ($result->RecordCount() != 0) {
			$row = $result->GetRowAssoc(false);
			$runningProcesses = (int)$row['running_processes'];
		}
		return $runningProcesses;
	}

	/**
	 * Delete a process.
	 * @param $process Process
	 */
	function deleteObject(&$process) {
		return $this->deleteObjectById($process->getId());
	}

	/**
	 * Delete a process by ID.
	 * @param $processId string
	 */
	function deleteObjectById($processId) {
		assert(!empty($processId));

		// Delete process
		return $this->update('DELETE FROM processes WHERE process_id = ?', $processId);
	}

	/**
	 * Delete stale processes.
	 *
	 * Zombie processes are remnants of process executions
	 * that for some reason died. We have to regularly remove
	 * them so that the process slots they occupy are freed
	 * for new processes.
	 * @param $force whether to force zombie removal, even
	 *  if they have been removed before.
	 *
	 * @see PROCESS_MAX_EXECUTION_TIME
	 */
	function deleteZombies($force = false) {
		static $zombiesDeleted = false;

		// For performance reasons don't delete zombies
		// more than once per request.
		if ($zombiesDeleted && !$force) {
			return;
		} else {
			$zombiesDeleted = true;
		}

		// Calculate the max timestamp that is considered ok.
		$maxTimestamp = time() - PROCESS_MAX_EXECUTION_TIME;

		// Delete all processes with a timestamp older than
		// the max. timestamp.
		return $this->update(
			'DELETE FROM processes
			WHERE time_started < ?',
			(int) $maxTimestamp
		);
	}

	/**
	 * Spawn new processes via web requests up to the
	 * given max. parallelism.
	 * @param $request Request
	 * @param $handler string a fully qualified handler class name
	 * @param $op string the operation to be called on the handler
	 * @param $processType integer one of the PROCESS_TYPE_* constants
	 * @param $noOfProcesses integer the number of processes to be spawned.
	 *  The actual number of processes can be lower if the max parallelism
	 *  is exceeded or if there are already processes of the same type
	 *  running.
	 * @param $additionalData optional Data to include with the processes
	 * @return integer the actual number of spawned processes.
	 */
	function spawnProcesses($request, $handler, $op, $processType, $noOfProcesses, $data = null) {
		// Generate the web URL to be called.
		$dispatcher = Application::getDispatcher();
		$processUrl = $dispatcher->url($request, ROUTE_COMPONENT, null, $handler, $op);

		// Parse the URL into parts to construct the fsockopen call.
		$urlParts = parse_url($processUrl);
		assert(isset($urlParts['scheme']) && isset($urlParts['host']) && isset($urlParts['path']) && !isset($urlParts['fragment']));
		if ($urlParts['scheme'] == 'https') {
			$port = 443;
			$transport = 'ssl://';
		} else {
			$port = 80;
			$transport = '';
		}

		// Delete process zombies for correct process slot calculation.
		$this->deleteZombies();

		// Calculate the number of max process slots for the given process type.
		$noOfProcesses = min($noOfProcesses, PROCESS_MAX_PARALLELISM);

		// Spawn new non-blocking (i.e. parallel) processes via
		// web requests until all process slots have been filled.
		$currentParallelism = $this->getNumberOfObjectsByProcessType($processType);
		$spawnedProcesses = 0;
		while ($currentParallelism < $noOfProcesses) {
			// Block a process slot.
			// NB: insertObject() re-checks the number of currently running
			// processes on each iteration to make sure that we don't exceed
			// the limit when there are concurrent requests.
			$process =& $this->insertObject($processType, $noOfProcesses, $data);
			if (!is_a($process, 'Process')) break;
			$oneTimeKey = $process->getId();

			// Make the request including the generated one-time-key.
			$stream = fsockopen($transport.$urlParts['host'], $port);
			if (!$stream) break;
			$processRequest =
				'GET '.$urlParts['path'].'?authToken='.urlencode($oneTimeKey)." HTTP/1.1\r\n"
				.'Host: '.$urlParts['host']."\r\n"
				."User-Agent: OJS\r\n"
				."Connection: Close\r\n\r\n";
			stream_set_blocking($stream, 0);
			fwrite($stream, $processRequest);
			fclose($stream);
			unset($stream);

			$currentParallelism++;
			$spawnedProcesses++;
		}

		return $spawnedProcesses;
	}

	/**
	 * Check the one-time-key of a process. If the
	 * key has not been checked before then this call
	 * will mark it as used.
	 * @param $processId string the unique process ID
	 *  which is being used as one-time-key.
	 * @return boolean
	 */
	function authorizeProcess($processId) {
		$process = $this->getObjectById($processId);
		if (is_a($process, 'Process') && $process->getObliterated() === false) {
			// The one time key has not been used yet.
			// Mark it as used.
			$success = $this->update(
				'UPDATE processes
				 SET obliterated = 1
				 WHERE process_id = ?',
				$processId
			);
			if (!$success) return false;

			// Only authorize the process if its one-time-key
			// has not expired yet.
			$minTimestamp = time() - PROCESS_MAX_KEY_VALID;
			$authorized = ($process->getTimeStarted() > $minTimestamp);

			// Delete the process entry if the process was
			// not authorized due to an expired key.
			if (!$authorized) $this->deleteObjectById($processId);

			return $authorized;
		}

		// Deny access if the process entry doesn't exist or
		// the one-time-key has already been marked used. But don't
		// delete the process entry in this case to avoid that
		// outsiders can stop processes if they guess a key.
		return false;
	}

	/**
	 * Check whether a process identified by its ID
	 * can continue to run. This should be called
	 * about once a minute by running processes.
	 * If this method returns false then the
	 * process is required to halt immediately.
	 * @param $processId string
	 * @return boolean
	 */
	function canContinue($processId) {
		// Calculate the max timestamp that is considered ok.
		$minTimestamp = time() - PROCESS_MAX_EXECUTION_TIME;

		// Check whether the process is still allowed to run.
		$process =& $this->getObjectById($processId);
		$canContinue = (is_a($process, 'Process') && $process->getTimeStarted() > $minTimestamp);

		// Delete the process entry if the process is
		// not allowed to continue.
		if (!$canContinue) $this->deleteObjectById($processId);

		return $canContinue;
	}

	/**
	 * Instantiate and return a new data object.
	 * @return DataObject
	 */
	function newDataObject() {
		return new Process();
	}

	//
	// Private helper methods
	//
	/**
	 * Internal function to return a process object
	 * from a row.
	 * @param $row array
	 * @return Process
	 */
	function _fromRow($row) {
		$process = $this->newDataObject();
		$process->setId($row['process_id']);
		$process->setProcessType((integer)$row['process_type']);
		$process->setTimeStarted((integer)$row['time_started']);
		$process->setObliterated((boolean)$row['obliterated']);
		$process->setAdditionalData(unserialize($row['additional_data']));
		return $process;
	}
}

?>
