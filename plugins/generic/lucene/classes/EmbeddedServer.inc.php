<?php

/**
 * @file plugins/generic/lucene/classes/EmbeddedServer.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmbeddedServer
 * @ingroup plugins_generic_lucene_classes
 *
 * @brief Implements a PHP interface to administer the embedded solr server.
 */


class EmbeddedServer {

	/**
	 * Constructor
	 */
	function EmbeddedServer() {
	}


	//
	// Public API
	//
	/**
	 * Start the embedded server.
	 *
	 * NB: The web service can take quite a bit longer than the
	 * process to start. So if you want to be sure you should
	 * instantiate SolrWebService and wait until it's status is
	 * SOLR_STATUS_ONLINE.
	 *
	 * @return boolean true if the server started, otherwise false.
	 */
	function start() {
		// Run the start command.
		return $this->_runScript('start.sh');
	}

	/**
	 * Stop the embedded server.
	 *
	 * @return boolean true if the server stopped, otherwise false.
	 */
	function stop() {
		// Run the stop command.
		return $this->_runScript('stop.sh');
	}

	/**
	 * Stop the embedded server and wait until it actually exited.
	 *
	 * @return boolean true if the server stopped, otherwise false.
	 */
	function stopAndWait() {
		$running = $this->isRunning();
		if ($running) {
			// Stop the server.
			$success = $this->stop();
			if (!$success) return false;

			// Give the server time to actually go down.
			while($this->isRunning()) sleep(1);
		}
		return true;
	}

	/**
	 * Check whether the embedded server is currently running.
	 *
	 * @return boolean true, if the server is running, otherwise false.
	 */
	function isRunning() {
		$returnValue = $this->_runScript('check.sh');
		return ($returnValue === true);
	}


	//
	// Private helper methods
	//
	/**
	 * Find the script directory.
	 *
	 * @return string
	 */
	function _getScriptDirectory() {
		return dirname(dirname(__FILE__)) . '/embedded/bin/';
	}

	/**
	 * Run the given script.
	 *
	 * @param $command string The script to be executed.
	 *
	 * @return boolean true if the command executed successfully, otherwise false.
	 */
	function _runScript($command) {
		// Get the log file name.
		$logFile = Config::getVar('files', 'files_dir') . '/lucene/solr-php.log';

		// Construct the shell command.
		$scriptDirectory = $this->_getScriptDirectory();
		$command = $scriptDirectory . $command . ' 2>&1 >>"' . $logFile . '" </dev/null';

		// Execute the command.
		$workingDirectory = getcwd();
		chdir($scriptDirectory);
		exec($command, $dummy, $returnStatus);
		chdir($workingDirectory);

		// Return the result.
		return ($returnStatus === 0);
	}
}

?>
