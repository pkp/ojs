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
		return $stripHtml ? htmlspecialchars(trim($var), ENT_NOQUOTES, Config::getVar('i18n', 'client_charset')) : trim($var);
	}
	
	/**
	 * Sanitize a value to be used in a file path.
	 * Removes any characters except alphanumeric characters, underscores, and dashes.
	 * @param $var string
	 * @return string
	 */
	function cleanFileVar($var) {
		return String::regexp_replace('/[^\w\-]/', '', $var);
	}
	
	/**
	 * Return the current date formatted for database insertion.
	 * @param $ts int optional, use specified timestamp instead of current time
	 * @return string
	 */
	function getCurrentDate($ts = null) {
		$dbconn = &DBConnection::getConn();
		$quotedTimestamp = $dbconn->DBTimeStamp($ts == null ? date('Y-m-d H:i:s') : $ts);

		/* Note that DBTimeStamp returns a quoted string, which cannot be
		   used with INSERT (a,b,c) values (?,?,?) because it is then
		   quoted a second time. Strip off the quotes. It's messy. */

		return substr($quotedTimestamp, 1, -1);
	}
	
	/**
	 * Return *nix timestamp with microseconds (in units of seconds).
	 * @return float
	 */
	function microtime() {
		list($usec, $sec) = explode(' ', microtime());
		return (float)$sec + (float)$usec;
	}
	
	/**
	 * Get the operating system of the server.
	 * @return string
	 */
	function serverPHPOS() {
		return PHP_OS;
	}
	
	/**
	 * Get the version of PHP running on the server.
	 * @return string
	 */
	function serverPHPVersion() {
		return phpversion();
	}
	
	/**
	 * Check if the server platform is Windows.
	 * @return boolean
	 */
	function isWindows() {
		return strtolower(substr(Core::serverPHPOS(), 0, 3)) == 'win';
	}
}

?>
