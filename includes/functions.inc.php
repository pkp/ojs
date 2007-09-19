<?php

/**
 * @file functions.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package index
 *
 * Contains definitions for common functions used system-wide.
 * Any frequently-used functions that cannot be put into an appropriate class should be added here.
 *
 * $Id$
 */

/**
 * Emulate a Java-style import statement.
 * Simply includes the associated PHP file (using require_once so multiple calls to include the same file have no effect).
 * @param $class string the complete name of the class to be imported (e.g. "core.Core")
 */
function import($class) {
	require_once(str_replace('.', '/', $class) . '.inc.php');
}

/**
 * Check if request is for a page that requires the system to be installed.
 * Any pages that can be accessed from an uninstalled system should be allowed here.
 * @return boolean
 */
function pageRequiresInstall() {
	$page = Request::getRequestedPage();
	return ($page != 'install' && $page != 'help');
}

/**
 * Perform basic system initialization.
 * Initializes configuration variables, database connection, and user session.
 */
function initSystem() {
	$microTime = Core::microtime();

	Registry::set('system.debug.startTime', $microTime);
	$notes = array();
	Registry::set('system.debug.notes', $notes);

	if (Config::getVar('general', 'installed')) {
		// Initialize database connection
		$conn = &DBConnection::getInstance();

		if (!$conn->isConnected()) {
			if (Config::getVar('database', 'debug')) {
				$dbconn = &$conn->getDBConn();
				fatalError('Database connection failed: ' . $dbconn->errorMsg());

			} else {
				fatalError('Database connection failed!');
			}
		}
	}
}

if (!function_exists('file_get_contents')) {
	// For PHP < 4.3.0
	function file_get_contents($file) {
		return join('', file($file));
	}
}

/**
 * Wrapper around die() to pretty-print an error message with an optional stack trace.
 */
function fatalError($reason) {
	// Because this method may be called when checking the value of the show_stacktrace
	// configuration string, we need to ensure that we don't get stuck in an infinite loop.
	static $isErrorCondition = null;
	static $showStackTrace = false;

	if ($isErrorCondition === null) {
		$isErrorCondition = true;
		$showStackTrace = Config::getVar('debug', 'show_stacktrace');
		$isErrorCondition = false;
	}

	echo "<h1>$reason</h1>";

	if ($showStackTrace && checkPhpVersion('4.3.0')) {
		echo "<h4>Stack Trace:</h4>\n";
		$trace = debug_backtrace();

		// Remove the call to fatalError from the call trace.
		array_shift($trace);

		// Back-trace pretty-printer adapted from the following URL:
		// http://ca3.php.net/manual/en/function.debug-backtrace.php
		// Thanks to diz at ysagoon dot com

		// FIXME: Is there any way to localize this when the localization
		// functions may have caused the failure in the first place?
		foreach ($trace as $bt) {
			$args = '';
			if (isset($bt['args'])) foreach ($bt['args'] as $a) {
				if (!empty($args)) {
					$args .= ', ';
				}
				switch (gettype($a)) {
					case 'integer':
					case 'double':
						$args .= $a;
						break;
					case 'string':
						$a = htmlspecialchars(substr($a, 0, 64)).((strlen($a) > 64) ? '...' : '');
						$args .= "\"$a\"";
						break;
					case 'array':
						$args .= 'Array('.count($a).')';
						break;
					case 'object':
						$args .= 'Object('.get_class($a).')';
						break;
					case 'resource':
						$args .= 'Resource('.strstr($a, '#').')';
						break;
					case 'boolean':
						$args .= $a ? 'True' : 'False';
						break;
					case 'NULL':
						$args .= 'Null';
						break;
					default:
						$args .= 'Unknown';
				}
			}
			$class = isset($bt['class'])?$bt['class']:'';
			$type = isset($bt['type'])?$bt['type']:'';
			$function = isset($bt['function'])?$bt['function']:'';
			$file = isset($bt['file'])?$bt['file']:'(unknown)';
			$line = isset($bt['line'])?$bt['line']:'(unknown)';

			echo "<b>File:</b> {$file} line {$line}<br />\n";
			echo "<b>Function:</b> {$class}{$type}{$function}($args)<br />\n";
			echo "<br/>\n";
		}
	}

	error_log("OJS: $reason");
	die();
}

/**
 * Check to see if the server meets a minimum version requirement for PHP.
 * @param $version Name of version (see version_compare documentation)
 * @return boolean
 */
function checkPhpVersion($version) {
	return (version_compare(PHP_VERSION, $version) !== -1);
}

?>
