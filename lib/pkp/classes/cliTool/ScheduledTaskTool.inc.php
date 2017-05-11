<?php

/**
 * @file classes/cliTool/ScheduledTaskTool.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ScheduledTaskTool
 * @ingroup tools
 *
 * @brief CLI tool to execute a set of scheduled tasks.
 */


/** Default XML tasks file to parse if none is specified */
define('TASKS_REGISTRY_FILE', 'registry/scheduledTasks.xml');

import('lib.pkp.classes.scheduledTask.ScheduledTask');
import('lib.pkp.classes.scheduledTask.ScheduledTaskHelper');
import('lib.pkp.classes.scheduledTask.ScheduledTaskDAO');

class ScheduledTaskTool extends CommandLineTool {
	/** @var string the XML file listing the tasks to be executed */
	var $file;

	/** @var ScheduledTaskDAO the DAO object */
	var $taskDao;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 * 		If specified, the first parameter should be the path to
	 *		a tasks XML descriptor file (other than the default)
	 */
	function __construct($argv = array()) {
		parent::__construct($argv);

		if (isset($this->argv[0])) {
			$this->file = $this->argv[0];
		} else {
			$this->file = TASKS_REGISTRY_FILE;
		}

		if (!file_exists($this->file) || !is_readable($this->file)) {
			printf("Tasks file \"%s\" does not exist or is not readable!\n", $this->file);
			exit(1);
		}

		$this->taskDao = DAORegistry::getDAO('ScheduledTaskDAO');
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Script to run a set of scheduled tasks\n"
			. "Usage: {$this->scriptName} [tasks_file]\n";
	}

	/**
	 * Parse and execute the scheduled tasks.
	 */
	function execute() {
		$this->parseTasks($this->file);
	}

	/**
	 * Parse and execute the scheduled tasks in the specified file.
	 * @param $file string
	 */
	function parseTasks($file) {
		$xmlParser = new XMLParser();
		$tree = $xmlParser->parse($file);

		if (!$tree) {
			$xmlParser->destroy();
			printf("Unable to parse file \"%s\"!\n", $file);
			exit(1);
		}

		foreach ($tree->getChildren() as $task) {
			$className = $task->getAttribute('class');

			$frequency = $task->getChildByName('frequency');
			if (isset($frequency)) {
				$canExecute = ScheduledTaskHelper::checkFrequency($className, $frequency);
			} else {
				// Always execute if no frequency is specified
				$canExecute = true;
			}

			if ($canExecute) {
				$this->executeTask($className, ScheduledTaskHelper::getTaskArgs($task));
			}
		}

		$xmlParser->destroy();
	}

	/**
	 * Execute the specified task.
	 * @param $className string the class name to execute
	 * @param $args array the array of arguments to pass to the class constructors
	 */
	function executeTask($className, $args) {
		// Load and execute the task
		if (!is_object($task = instantiate($className, null, null, 'execute', $args))) {
			fatalError('Cannot instantiate task class.');
		}
		$this->taskDao->updateLastRunTime($className);
		$task->execute();
	}
}

?>
