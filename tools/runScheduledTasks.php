<?php

/**
 * runScheduledTasks.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package tools
 *
 * CLI tool to execute a set of scheduled tasks.
 *
 * $Id$
 */

require(dirname(__FILE__) . '/includes/cliTool.inc.php');

/** Default XML tasks file to parse if none is specified */
define('TASKS_REGISTRY_FILE', Config::getVar('general', 'registry_dir') . '/scheduledTasks.xml');

import('scheduledTask.ScheduledTask');
import('scheduledTask.ScheduledTaskDAO');

class runScheduledTasks extends CommandLineTool {

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
	function runScheduledTasks($argv = array()) {
		parent::CommandLineTool($argv);
		
		if (isset($this->argv[0])) {
			$this->file = $this->argv[0];
		} else {
			$this->file = TASKS_REGISTRY_FILE;
		}
		
		if (!file_exists($this->file) || !is_readable($this->file)) {
			printf("Tasks file \"%s\" does not exist or is not readable!\n", $this->file);
			exit(1);
		}
		
		$this->taskDao = &DAORegistry::getDAO('ScheduledTaskDAO');
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
		$xmlParser = &new XMLParser();
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
				$canExecute = $this->checkFrequency($className, $frequency);
			} else {
				// Always execute if no frequency is specified
				$canExecute = true;
			}
			
			if ($canExecute) {
				$this->executeTask($className, $this->getTaskArgs($task));
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
		// Strip off the package name(s) to get the base class name
		$pos = strrpos($className, '.');
		if ($pos === false) {
			$baseClassName = $className;
		} else {
			$baseClassName = substr($className, $pos+1);
		}
		
		// Load and execute the task
		import($className);
		$task = &new $baseClassName($args);
		$task->execute();
		$this->taskDao->updateLastRunTime($className);
	}
	
	/**
	 * Get the arguments for a task from the parsed XML.
	 * @param XMLNode
	 * @return array
	 */
	function getTaskArgs($task) {
		$args = array();
		$index = 0;
		
		while(($arg = $task->getChildByName('arg', $index)) != null) {
			array_push($args, $arg->getValue());
			$index++;
		}
		
		return $args;
	}
	
	/**
	 * Check if the specified task should be executed according to the specified
	 * frequency and its last run time.
	 * @param $className string
	 * @param $frequency XMLNode
	 * @return string
	 */
	function checkFrequency($className, $frequency) {
		$isValid = true;
		$lastRunTime = $this->taskDao->getLastRunTime($className);
		
		// Check day of week
		$dayOfWeek = $frequency->getAttribute('dayofweek');
		if (isset($dayOfWeek)) {
			$isValid = $this->isInRange($dayOfWeek, (int)date('w'), $lastRunTime, 'day', strtotime('-1 week'));
		}
		
		if ($isValid) {
			// Check month
			$month = $frequency->getAttribute('month');
			if (isset($month)) {
				$isValid = $this->isInRange($month, (int)date('n'), $lastRunTime, 'month', strtotime('-1 year'));
			}
		}
		
		if ($isValid) {
			// Check day
			$day = $frequency->getAttribute('day');
			if (isset($day)) {
				$isValid = $this->isInRange($day, (int)date('j'), $lastRunTime, 'day', strtotime('-1 month'));
			}
		}
		
		if ($isValid) {
			// Check hour
			$hour = $frequency->getAttribute('hour');
			if (isset($hour)) {
				$isValid = $this->isInRange($hour, (int)date('G'), $lastRunTime, 'hour', strtotime('-1 day'));
			}
		}
		
		if ($isValid) {
			// Check minute
			$minute = $frequency->getAttribute('minute');
			if (isset($minute)) {
				$isValid = $this->isInRange($minute, (int)date('i'), $lastRunTime, 'min', strtotime('-1 hour'));
			}
		}
		
		return $isValid;
	}

	/**
	 * Check if a value is within the specified range.
	 * @param $rangeStr string the range (e.g., 0, 1-5, *, etc.)
	 * @param $currentValue int value to check if its in the range
	 * @param $lastTimestamp int the last time the task was executed
	 * @param $timeCompareStr string value to use in strtotime("-X $timeCompareStr")
	 * @param $cutoffTimestamp int value will be considered valid if older than this
	 * @return boolean
	 */
	function isInRange($rangeStr, $currentValue, $lastTimestamp, $timeCompareStr, $cutoffTimestamp) {
		$isValid = false;
		$rangeArray = explode(',', $rangeStr);
		
		if ($cutoffTimestamp > $lastTimestamp) {
			// Execute immediately if the cutoff time period has past since the task was last run
			$isValid = true;
		}
		
		for ($i = 0, $count = count($rangeArray); !$isValid && ($i < $count); $i++) {
			if ($rangeArray[$i] == '*') {
				// Is wildcard
				$isValid = true;
				
			} if (is_numeric($rangeArray[$i])) {
				// Is just a value
				$isValid = ($currentValue == (int)$rangeArray[$i]);
				
			} else if (preg_match('/^(\d*)\-(\d*)$/', $rangeArray[$i], $matches)) {
				// Is a range
				$isValid = $this->isInNumericRange($currentValue, (int)$matches[1], (int)$matches[2]);
				
			} else if (preg_match('/^(.+)\/(\d+)$/', $rangeArray[$i], $matches)) {
				// Is a range with a skip factor
				$skipRangeStr = $matches[1];
				$skipFactor = (int)$matches[2];
				
				if ($skipRangeStr == '*') {
					$isValid = true;
					
				} else if (preg_match('/^(\d*)\-(\d*)$/', $skipRangeStr, $matches)) {
					$isValid = $this->isInNumericRange($currentValue, (int)$matches[1], (int)$matches[2]);
				}
				
				if ($isValid) {
					// Check against skip factor
					$isValid = (strtotime("-$skipFactor $timeCompareStr") > $lastTimestamp);
				}
			}
		}
		
		return $isValid;
	}
		
	/**
	 * Check if a numeric value is within the specified range.
	 * @param $value int
	 * @param $min int
	 * @param $max int
	 * @return boolean
	 */
	function isInNumericRange($value, $min, $max) {
		return ($value >= $min && $value <= $max);
	}
	
}

$tool = &new runScheduledTasks(isset($argv) ? $argv : array());
$tool->execute();

?>
