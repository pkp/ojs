<?php

/**
 * @file ScheduledTaskDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package scheduledTask
 * @class ScheduledTaskDAO
 *
 * Operations for retrieving and modifying Scheduled Task data.
 *
 * $Id$
 */

import('scheduledTask.ScheduledTask');

class ScheduledTaskDAO extends DAO {
	/**
	 * Get the last time a scheduled task was executed.
	 * @param $className string
	 * @return int
	 */
	function getLastRunTime($className) {
		$result = &$this->retrieve(
			'SELECT last_run FROM scheduled_tasks WHERE class_name = ?',
			$className
		);
		
		if ($result->RecordCount() == 0) {
			$returner = 0;
		} else {
			$returner = strtotime($this->datetimeFromDB($result->fields[0]));
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Update a scheduled task's last run time.
	 * @param $className string
	 * @param $timestamp int optional, if omitted the current time is used
	 */
	function updateLastRunTime($className, $timestamp = null) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM scheduled_tasks WHERE class_name = ?',
			$className
		);
		
		if (isset($result->fields[0]) && $result->fields[0] != 0) {
			if (isset($timestamp)) {
				$returner = $this->update(
					'UPDATE scheduled_tasks SET last_run = ' . $this->datetimeToDB($timestamp) . ' WHERE class_name = ?',
					array($className)
				);
			} else {
				$returner = $this->update('UPDATE scheduled_tasks SET last_run = NOW() WHERE class_name = ?', array($className));
			}
			
		} else {
			if (isset($timestamp)) {
				$returner = $this->update(
					sprintf('INSERT INTO scheduled_tasks (class_name, last_run)
					VALUES (?, %s)', $this->datetimeToDB($timestamp)),
					array($className)
				);
			} else {
				$returner = $this->update(
					'INSERT INTO scheduled_tasks (class_name, last_run)
					VALUES (?, NOW())',
					$className
				);
			}
		}

		$result->Close();
		unset($result);

		return $returner;
	}
}

?>
