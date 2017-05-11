<?php

/**
 * @file classes/scheduledTask/ScheduledTask.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ScheduledTask
 * @ingroup scheduledTask
 * @see ScheduledTaskDAO
 *
 * @brief Base class for executing scheduled tasks.
 * All scheduled task classes must extend this class and implement execute().
 */

import('lib.pkp.classes.scheduledTask.ScheduledTaskHelper');

abstract class ScheduledTask {

	/** @var array task arguments */
	private $_args;

	/** @var string? This process id. */
	private $_processId = null;

	/** @var string File path in which execution log messages will be written. */
	private $_executionLogFile;

	/** @var ScheduledTaskHelper */
	private $_helper;


	/**
	 * Constructor.
	 * @param $args array
	 */
	function __construct($args = array()) {
		$this->_args = $args;
		$this->_processId = uniqid();

		// Ensure common locale keys are available
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_ADMIN, LOCALE_COMPONENT_PKP_COMMON);
		
		// Check the scheduled task execution log folder.
		import('lib.pkp.classes.file.PrivateFileManager');
		$fileMgr = new PrivateFileManager();

		$scheduledTaskFilesPath = realpath($fileMgr->getBasePath()) . DIRECTORY_SEPARATOR . SCHEDULED_TASK_EXECUTION_LOG_DIR;
		$this->_executionLogFile = $scheduledTaskFilesPath . DIRECTORY_SEPARATOR . str_replace(' ', '', $this->getName()) . 
			'-' . $this->getProcessId() . '-' . date('Ymd') . '.log';
		if (!$fileMgr->fileExists($scheduledTaskFilesPath, 'dir')) {
			$success = $fileMgr->mkdirtree($scheduledTaskFilesPath);
			if (!$success) {
				// files directory wrong configuration?
				assert(false);
				$this->_executionLogFile = null;
			}
		}
	}


	//
	// Protected methods.
	//
	/**
	 * Get this process id.
	 * @return int
	 */
	function getProcessId() {
		return $this->_processId;
	}

	/**
	 * Get scheduled task helper object.
	 * @return ScheduledTaskHelper
	 */
	function getHelper() {
		if (!$this->_helper) $this->_helper = new ScheduledTaskHelper();
		return $this->_helper;
	}

	/**
	 * Get the scheduled task name. Override to
	 * define a custom task name.
	 * @return string
	 */
	function getName() {
		return __('admin.scheduledTask');
	}

	/**
	 * Add an entry into the execution log.
	 * @param $message string A translated message.
	 * @param $type string (optional) One of the ScheduledTaskHelper
	 * SCHEDULED_TASK_MESSAGE_TYPE... constants.
	 */
	function addExecutionLogEntry($message, $type = null) {
		$logFile = $this->_executionLogFile;

		if (!$message) return;
		$date = '[' . Core::getCurrentDate() . '] ';

		if ($type) {
			$log = $date . '[' . __($type) . '] ' . $message;
		} else {
			$log = $date . $message;
		}

		$fp = fopen($logFile, 'ab');
		if (flock($fp, LOCK_EX)) {
			fwrite($fp, $log . PHP_EOL);
			flock($fp, LOCK_UN);
		} else {
			fatalError("Couldn't lock the file.");
		}
		fclose($fp);	
	}


	//
	// Protected abstract methods.
	//
	/**
	 * Implement this method to execute the task actions.
	 */
	abstract protected function executeActions();


	//
	// Public methods.
	//
	/**
	 * Make sure the execution process follow the required steps.
	 * This is not the method one should extend to implement the
	 * task actions, for this see ScheduledTask::executeActions().
	 * @param boolean $notifyAdmin optional Whether or not the task
	 * will notify the site administrator about errors, warnings or
	 * completed process.
	 * @return boolean Whether or not the task was succesfully
	 * executed.
	 */
	function execute() {
		$this->addExecutionLogEntry(Config::getVar('general', 'base_url'));
		$this->addExecutionLogEntry(__('admin.scheduledTask.startTime'), SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);

		$result = $this->executeActions();

		$this->addExecutionLogEntry(__('admin.scheduledTask.stopTime'), SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);

		$helper = $this->getHelper();
		$helper->notifyExecutionResult($this->_processId, $this->getName(), $result, $this->_executionLogFile);

		return $result;
	}
}

?>
