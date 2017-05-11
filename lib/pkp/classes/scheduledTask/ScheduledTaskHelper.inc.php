<?php

/**
 * @file classes/scheduledTask/ScheduledTaskHelper.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ScheduledTaskHelper
 * @ingroup scheduledTask
 *
 * @brief Helper class for common scheduled tasks operations.
 */

define('SCHEDULED_TASK_MESSAGE_TYPE_COMPLETED', 'common.completed');
define('SCHEDULED_TASK_MESSAGE_TYPE_ERROR', 'common.error');
define('SCHEDULED_TASK_MESSAGE_TYPE_WARNING', 'common.warning');
define('SCHEDULED_TASK_MESSAGE_TYPE_NOTICE', 'common.notice');
define('SCHEDULED_TASK_EXECUTION_LOG_DIR', 'scheduledTaskLogs');

class ScheduledTaskHelper {

	/** @var string Contact email. */
	var $_contactEmail;

	/** @var string Contact name. */
	var $_contactName;

	/**
	 * Constructor.
	 * Ovewrites both parameters if one is not passed. 
	 * @param $email string (optional)
	 * @param $contactName string (optional)
	 */
	function __construct($email = '', $contactName = '') {
		if (!$email || !$contactName) {
			$siteDao = DAORegistry::getDAO('SiteDAO'); /* @var $siteDao SiteDAO */
			$site = $siteDao->getSite(); /* @var $site Site */
			$email = $site->getLocalizedContactEmail();
			$contactName = $site->getLocalizedContactName();
		}

		$this->_contactEmail = $email;
		$this->_contactName = $contactName;

	}

	/**
	 * Get mail object.
	 * @return Mail
	 */
	function getMail() {
		// Instantiate a mail object.
		import('lib.pkp.classes.mail.Mail');
		return new Mail();
	}

	/**
	 * Get the arguments for a task from the parsed XML.
	 * @param XMLNode
	 * @return array
	 */
	static function getTaskArgs($task) {
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
		$taskDao = DAORegistry::getDAO('ScheduledTaskDAO'); /* @var $taskDao ScheduledTaskDAO */
		$lastRunTime = $taskDao->getLastRunTime($className);

		// Check day of week
		$dayOfWeek = $frequency->getAttribute('dayofweek');
		if (isset($dayOfWeek)) {
			$isValid = ScheduledTaskHelper::_isInRange($dayOfWeek, (int)date('w'), $lastRunTime, 'day', strtotime('-1 week'));
		}

		if ($isValid) {
			// Check month
			$month = $frequency->getAttribute('month');
			if (isset($month)) {
				$isValid = ScheduledTaskHelper::_isInRange($month, (int)date('n'), $lastRunTime, 'month', strtotime('-1 year'));
			}
		}

		if ($isValid) {
			// Check day
			$day = $frequency->getAttribute('day');
			if (isset($day)) {
				$isValid = ScheduledTaskHelper::_isInRange($day, (int)date('j'), $lastRunTime, 'day', strtotime('-1 month'));
			}
		}

		if ($isValid) {
			// Check hour
			$hour = $frequency->getAttribute('hour');
			if (isset($hour)) {
				$isValid = ScheduledTaskHelper::_isInRange($hour, (int)date('G'), $lastRunTime, 'hour', strtotime('-1 day'));
			}
		}

		if ($isValid) {
			// Check minute
			$minute = $frequency->getAttribute('minute');
			if (isset($minute)) {
				$isValid = ScheduledTaskHelper::_isInRange($minute, (int)date('i'), $lastRunTime, 'min', strtotime('-1 hour'));
			}
		}

		return $isValid;
	}

	/**
	 * Notifies site administrator about the
	 * task execution result.
	 * @param $id int Task id.
	 * @param $name string Task name.
	 * @param $result boolean Whether or not the task
	 * execution was successful.
	 * @param $executionLogFile string Task execution log file path.
	 */
	function notifyExecutionResult($id, $name, $result, $executionLogFile = '') {
		$reportErrorOnly = Config::getVar('general', 'scheduled_tasks_report_error_only', true);

		if (!$result || !$reportErrorOnly) {
			$message = $this->getMessage($executionLogFile);
			
			if ($result) {
				// Success.
				$type = SCHEDULED_TASK_MESSAGE_TYPE_COMPLETED;
			} else {
				// Error.
				$type = SCHEDULED_TASK_MESSAGE_TYPE_ERROR;
			}

			$subject = $name . ' - ' . $id . ' - ' . __($type);
			return $this->_sendEmail($message, $subject);
		}

		return false;
	}

	/**
	 * Get execution log email message.
	 * @param $executionLogFile string
	 * @return string
	 */
	function getMessage($executionLogFile) {
		if (!$executionLogFile) {
			return __('admin.scheduledTask.noLog');
		}
		
		$application = Application::getApplication();
		$request = $application->getRequest();
		$router = $request->getRouter();
		$downloadLogUrl = $router->url($request, 'index', 'admin', 'downloadScheduledTaskLogFile', null, array('file' => basename($executionLogFile)));
		return __('admin.scheduledTask.downloadLog', array(
			'url' => $downloadLogUrl,
			'softwareName' => __(Application::getNameKey()),
		));
	}

	//
	// Static methods.
	//
	/**
	 * Clear tasks execution log files.
	 */
	static function clearExecutionLogs() {
		import('lib.pkp.classes.file.PrivateFileManager');
		$fileMgr = new PrivateFileManager();
	
		$fileMgr->rmtree($fileMgr->getBasePath() . DIRECTORY_SEPARATOR . SCHEDULED_TASK_EXECUTION_LOG_DIR);	
	}

	/**
	 * Download execution log file.
	 * @param $file string
	 */
	static function downloadExecutionLog($file) {
		import('lib.pkp.classes.file.PrivateFileManager');
		$fileMgr = new PrivateFileManager();

		$fileMgr->downloadFile($fileMgr->getBasePath() . DIRECTORY_SEPARATOR . SCHEDULED_TASK_EXECUTION_LOG_DIR . DIRECTORY_SEPARATOR . $file);	
	}


	//
	// Private helper methods.
	//
	/**
	 * Send email to the site administrator.
	 * @param $message string
	 * @param $subject string
	 * @return boolean
	 */
	private function _sendEmail($message, $subject) {
		$mail = $this->getMail();
		$mail->addRecipient($this->_contactEmail, $this->_contactName);
		$mail->setSubject($subject);
		$mail->setBody($message);

		return $mail->send();
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
	private function _isInRange($rangeStr, $currentValue, $lastTimestamp, $timeCompareStr, $cutoffTimestamp) {
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
				$isValid = ScheduledTaskHelper::_isInNumericRange($currentValue, (int)$matches[1], (int)$matches[2]);

			} else if (preg_match('/^(.+)\/(\d+)$/', $rangeArray[$i], $matches)) {
				// Is a range with a skip factor
				$skipRangeStr = $matches[1];
				$skipFactor = (int)$matches[2];

				if ($skipRangeStr == '*') {
					$isValid = true;

				} else if (preg_match('/^(\d*)\-(\d*)$/', $skipRangeStr, $matches)) {
					$isValid = ScheduledTaskHelper::_isInNumericRange($currentValue, (int)$matches[1], (int)$matches[2]);
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
	private function _isInNumericRange($value, $min, $max) {
		return ($value >= $min && $value <= $max);
	}

}

?>
