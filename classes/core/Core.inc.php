<?php

/**
 * Core.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package core
 *
 * Class containing system-wide functions.
 *
 * $Id$
 */

class Core {
	
	/**
	 * Get the path to the base OJS directory.
	 * @return string
	 */
	function getBaseDir() {
		static $baseDir;
		
		if (!isset($baseDir)) {
			// Need to change if this file moves from classes/core
			$baseDir = dirname(dirname(dirname(__FILE__)));
		}
		
		return $baseDir;
	}
	
	/**
	 * Sanitize a variable.
	 * Removes leading and trailing whitespace, optionally removes HTML.
	 * @param $var string
	 * @param $stripHtml boolean optional, will encode HTML if set to true
	 * @return string
	 */
	function cleanVar($var, $stripHtml = false) {
		return $stripHtml ? htmlspecialchars(trim($var)) : trim($var);
	}
	
	/**
	 * Sanitize a value to be used in a file path.
	 * Removes any characters except alphanumeric characters, underscores, and dashes.
	 * @param $var string
	 * @return string
	 */
	function cleanFileVar($var) {
		return preg_replace('/[^\w\-]/', '', $var);
	}
	
	/**
	 * Return the current date formatted for database insertion.
	 * @param $ts int optional, use specified timestamp instead of current time
	 * @return string
	 */
	function getCurrentDate($ts = null) {
		$dbconn = &DBConnection::getConn();
		return $dbconn->DBTimeStamp($ts == null ? time() : $ts);
	}
	
}

?>
