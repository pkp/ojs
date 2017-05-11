<?php

/**
 * @defgroup tools Tools
 * Implements command-line management tools for PKP software.
 */

/**
 * @file classes/cliTool/CliTool.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CommandLineTool
 * @ingroup tools
 *
 * @brief Initialization code for command-line scripts.
 *
 * FIXME: Write a PKPCliRequest and PKPCliRouter class and use the dispatcher
 *  to bootstrap and route tool requests.
 */


/** Initialization code */
define('PWD', getcwd());
chdir(dirname(INDEX_FILE_LOCATION)); /* Change to base directory */
if (!defined('STDIN')) {
	define('STDIN', fopen('php://stdin','r'));
}
define('SESSION_DISABLE_INIT', 1);
require('./lib/pkp/includes/bootstrap.inc.php');

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
		// Initialize the request object with a page router
		$application = PKPApplication::getApplication();
		$request = $application->getRequest();

		// FIXME: Write and use a CLIRouter here (see classdoc)
		import('classes.core.PageRouter');
		$router = new PageRouter();
		$router->setApplication($application);
		$request->setRouter($router);

		// Initialize the locale and load generic plugins.
		AppLocale::initialize($request);
		PluginRegistry::loadCategory('generic');

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
