<?php

/**
 * cli.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package tools
 *
 * Initialization code for command-line scripts.
 *
 * $Id$
 */

/** Initialization code */
define('PWD', getcwd());
chdir(dirname(dirname(dirname(__FILE__)))); /* Change to base directory */
require('includes/driver.inc.php');
define('SESSION_DISABLE_INIT', 1);

class CommandLineTool {
	
	/** Command-line arguments */
	var $argv;

	function CommandLineTool($argv = array()) {
		$this->argv = isset($argv) && is_array($argv) ? $argv : array();
	}

}
?>
