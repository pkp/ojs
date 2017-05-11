<?php

/**
 * @defgroup scheduledTask Scheduled Tasks
 * Implements a scheduled task mechanism allowing for the periodic execution
 * of maintenance tasks, notification, etc.
 */

/**
 * @file classes/scheduledTask/ScheduledTaskDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ScheduledTaskDAO
 * @ingroup scheduledTask
 * @see ScheduledTask
 *
 * @brief Operations for retrieving and modifying Scheduled Task data.
 */


import('lib.pkp.classes.scheduledTask.ScheduledTask');

class ScheduledTaskDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Get the last time a scheduled task was executed.
	 * @param $className string
	 * @return int
	 */
	function getLastRunTime($className) {
		$result = $this->retrieve(
			'SELECT last_run FROM scheduled_tasks WHERE class_name = ?',
			array($className)
		);

		if ($result->RecordCount() == 0) {
			$returner = 0;
		} else {
			$returner = strtotime($this->datetimeFromDB($result->fields[0]));
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Update a scheduled task's last run time.
	 * @param $className string
	 * @param $timestamp int optional, if omitted the current time is used.
	 * @return int
	 */
	function updateLastRunTime($className, $timestamp = null) {
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM scheduled_tasks WHERE class_name = ?',
			array($className)
		);

		if (isset($result->fields[0]) && $result->fields[0] != 0) {
			if (isset($timestamp)) {
				$this->update(
					'UPDATE scheduled_tasks SET last_run = ' . $this->datetimeToDB($timestamp) . ' WHERE class_name = ?',
					array($className)
				);
			} else {
				$this->update(
					'UPDATE scheduled_tasks SET last_run = NOW() WHERE class_name = ?',
					array($className)
				);
			}

		} else {
			if (isset($timestamp)) {
				$this->update(
					sprintf('INSERT INTO scheduled_tasks (class_name, last_run)
					VALUES (?, %s)', $this->datetimeToDB($timestamp)),
					array($className)
				);
			} else {
				$this->update(
					'INSERT INTO scheduled_tasks (class_name, last_run)
					VALUES (?, NOW())',
					array($className)
				);
			}
		}

		$result->Close();
		return $this->getAffectedRows();
	}
}

?>
