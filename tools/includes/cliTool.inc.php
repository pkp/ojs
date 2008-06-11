<?php

/**
 * @file cliTool.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package tools
 * @class CommandLineTool
 *
 * Initialization code for command-line scripts.
 *
 * $Id$
 */

/** Initialization code */
define('PWD', getcwd());
chdir(dirname(dirname(dirname(__FILE__)))); /* Change to base directory */
if (!defined('STDIN')) {
	define('STDIN', fopen('php://stdin','r'));
}
define('SESSION_DISABLE_INIT', 1);
require('includes/driver.inc.php');

if (!isset($argc)) {
	// In PHP < 4.3.0 $argc/$argv are not automatically registered
	if (isset($_SERVER['argc'])) {
		$argc = $_SERVER['argc'];
		$argv = $_SERVER['argv'];
	} else {
		$argc = $argv = null;
	}
}

class CommandLineTool {

	/** @var string the script being executed */
	var $scriptName;

	/** @vary array Command-line arguments */
	var $argv;

	function CommandLineTool($argv = array()) {
		$this->argv = isset($argv) && is_array($argv) ? $argv : array();

		if (isset($_SERVER['SERVER_NAME'])) {
			die('This script can only be executed from the command-line');
		}

		$this->scriptName = isset($this->argv[0]) ? array_shift($this->argv) : '';

		if (isset($this->argv[0]) && $this->argv[0] == '-h') {
			$this->usage();
			exit(0);
		}
	}

	function usage() {
	}

}
?>
