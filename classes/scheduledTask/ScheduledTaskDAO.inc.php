<?php

/**
 * ScheduledTaskDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package scheduledTask
 *
 * Operations for retrieving and modifying Scheduled Task data.
 *
 * $Id$
 */

class ScheduledTaskDAO extends DAO {

	/**
	 * Constructor.
	 */
	function ScheduledTaskDAO() {
		parent::DAO();
	}
	
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
			return 0;
		} else {
			return $this->_dataSource->UnixTimeStamp($result->fields[0]);
		}
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
				return $this->update(
					'UPDATE scheduled_tasks SET last_run = ?',
					$this->_dataSource->DBTimeStamp($timestamp)
				);
			} else {
				return $this->update('UPDATE scheduled_tasks SET last_run = NOW()');
			}
			
		} else {
			if (isset($timestamp)) {
				return $this->update(
					'INSERT INTO scheduled_tasks (class_name, last_run)
					VALUES (?, ?)',
					array($className, $this->_dataSource->DBTimeStamp($timestamp))
				);
			} else {
				return $this->update(
					'INSERT INTO scheduled_tasks (class_name, last_run)
					VALUES (?, NOW())',
					$className
				);
			}
		}
	}
	
}

?>
