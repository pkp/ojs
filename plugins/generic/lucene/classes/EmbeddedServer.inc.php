<?php

/**
 * @file plugins/generic/lucene/classes/EmbeddedServer.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmbeddedServer
 * @ingroup plugins_generic_lucene_classes
 *
 * @brief Implements a PHP interface to administer the embedded solr server.
 */


class EmbeddedServer {

	//
 	// Constructor
 	//
	function __construct() {
	}


	//
	// Public API
	//
	/**
	 * Check whether the embedded server is
	 * installed.
	 * @return boolean
	 */
	function isInstalled() {
		// Check solr installation.
		$solrWar = $this->_getPluginDirectory() . DIRECTORY_SEPARATOR . 'embedded' . DIRECTORY_SEPARATOR . 'webapps' . DIRECTORY_SEPARATOR . 'solr.war';
		if (!is_readable($solrWar)) return false;

		// Check jetty installation.
		$jettyJar = $this->_getPluginDirectory() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'jetty' . DIRECTORY_SEPARATOR . 'start.jar';
		if (!is_readable($jettyJar))	return false;

		return true;
	}

	/**
	 * Check whether an embedded server is installed
	 * and we can manipulate it through PHP.
	 * @return boolean
	 */
	function isAvailable() {
		return ($this->isInstalled() && $this->_canExecScripts());
	}

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
		return $this->_runScript('start');
	}

	/**
	 * Stop the embedded server.
	 *
	 * @return boolean true if the server stopped, otherwise false.
	 */
	function stop() {
		// Run the stop command.
		return $this->_runScript('stop');
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
			// But not more than 10 second.
			$logFile = $this->_getLogFileName();
			file_put_contents($logFile, 'Waiting for server to stop ', FILE_APPEND);
			$maxWait = 10;
			while($this->isRunning() && $maxWait>0) {
				file_put_contents($logFile, '.', FILE_APPEND);
				sleep(1);
				$maxWait--;
			}
			if ($maxWait == 0) {
				file_put_contents($logFile, ' timeout' . PHP_EOL, FILE_APPEND);
				return false;
			} else {
				file_put_contents($logFile, PHP_EOL, FILE_APPEND);
			}
		}
		return true;
	}

	/**
	 * Check whether the embedded server is currently running.
	 *
	 * @return boolean true, if the server is running, otherwise false.
	 */
	function isRunning() {
		$returnValue = $this->_runScript('check', false);
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
		return $this->_getPluginDirectory() . DIRECTORY_SEPARATOR . 'embedded' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Find the plugin directory.
	 *
	 * @return string
	 */
	function _getPluginDirectory() {
		return dirname(dirname(__FILE__));
	}

	/**
	 * Get the embedded server log.
	 *
	 * @return string
	 */
	function _getLogFileName() {
		return Config::getVar('files', 'files_dir') . DIRECTORY_SEPARATOR . 'lucene' . DIRECTORY_SEPARATOR . 'solr-php.log';
	}

	/**
	 * Get platform-specific script name with extension.
	 *
	 * @param $script string The script name without
	 *  extension.
	 *
	 * @return string script name with platform specific
	 *  extension.
	 */
	function _getScriptPath($script) {
		$scriptPath = $this->_getScriptDirectory() . $script;
		if (Core::isWindows()) {
			$scriptPath .= '.bat';
		} else {
			$scriptPath .= '.sh';
		}
		return $scriptPath;
	}

	/**
	 * Check whether script execution is
	 * enabled.
	 * @return boolean
	 */
	function _canExecScripts() {
		// Script execution is not allowed in safe mode.
		if (ini_get('safe_mode')) return false;

		// Check whether the exec() function is disabled.
		$disabled_functions = explode(',', ini_get('disable_functions'));
		$disabled_functions = array_map('trim', $disabled_functions);
		if (in_array('exec', $disabled_functions)) return false;

		// Check whether the management scripts are executable.
		$scriptDir = $this->_getScriptDirectory();
		foreach(array('start', 'stop', 'check') as $script) {
			$scriptPath = $this->_getScriptPath($script);
			if (Core::isWindows()) {
				if (!is_readable($scriptPath)) return false;
			} else {
				if (!is_executable($scriptPath)) return false;
			}
		}

		// Check whether crucial files are writable.
		$filesDir = Config::getVar('files', 'files_dir');
		foreach(array('data', 'solr-java.log', 'solr-php.log', 'solr.pid') as $fileName) {
			$filePath = $filesDir . DIRECTORY_SEPARATOR . 'lucene' . DIRECTORY_SEPARATOR . $fileName;
			if (file_exists($filePath) && !is_writable($filePath)) {
				return false;
			}
		}

		// Check whether there is an existing solr process, and if so, whether
		// it is running under the same user id as PHP. Otherwise we cannot
		// manipulate the process.
		if (function_exists('posix_getuid') && $this->isRunning()) {
			$phpUid = posix_getuid();
			if (!$this->_runScript('check', false, $phpUid)) return false;
		}

		return true;
	}

	/**
	 * Run the given script.
	 *
	 * @param $script string The script to be executed (without platform
	 *  specific extension).
	 * @param $log boolean Whether to log the script execution. Logging is NOT
	 *  supported on Windows due to locking issues with the log file when
	 *  being started through 'start /b'.
	 * @param $parameters string Optional script parameters.
	 *
	 * @return boolean true if the command executed successfully, otherwise false.
	 */
	function _runScript($script, $log = true, $parameters = '') {
		// Assemble the shell command.
		$command = $this->_getScriptPath($script);
		if (!empty($parameters)) {
			$command .= ' ' . $parameters;
		}

		// Long running background processes cause
		// locking issues on Windows.
		$allowLock = true;
		if (Core::isWindows() && $script === 'start') {
			$allowLock = false;
		}

		// Configure logging (not supported on Windows
		// due to locking issues).
		if ($allowLock && $log) {
			$logFile = $this->_getLogFileName();
			$command .= " 2>&1 >>\"$logFile\"";
		} else {
			if (Core::isWindows()) {
				$command .= ' 2>&1 >NUL';
			} else {
				$command .= ' 2>&1 >/dev/null';
			}
		}
		if (Core::isWindows()) {
			$command .= ' <NUL';
		} else {
			$command .= ' </dev/null';
		}

		// Execute the command.
		if ($allowLock) {
			exec($command, $dummy, $returnStatus);
		} else {
			// Do not lock up PHP on Windows.
			pclose(popen($command, 'r'));
			$returnStatus = 0;
		}

		// Return the result.
		return ($returnStatus === 0);
	}
}


