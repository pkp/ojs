<?php

/**
 * @file tools/phpCompat.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PhpCompat
 * @ingroup tools
 *
 * @brief A small wrapper script around PEAR's PHP_CompatInfo package to
 *        test PHP version compatibility.
 *
 *        This script may be used standalone, as an external tool in your
 *        favourite development environment or within the build process.
 *
 *        Usage: <code>php phpcompat.inc.php input_file|input_directory</code>
 *
 *        Installation Requirements:
 *        <code>
 *            pear install PHP_CompatInfo
 *            pear install Console_ProgressBar
 *        </code>
 *
 *        To install as an Eclipse external tool:
 *          Main tab:
 *            Location: /path/to/your/php.exe
 *            Working Directory: ${workspace_loc:/your-project}
 *            Arguments: ${project_loc}/lib/pkp/tools/phpCompat.php "${resource_loc}"
 *
 *          Common tab:
 *            Check "Display in favorites menu" -> "External Tools"
 *            Check "Allocate Console"
 *
 *        Please see http://pear.php.net/manual/en/package.php.php-compatinfo.intro.php
 *        for information about detection accuracy.
 */


// FIXME: This doesn't work if lib/pkp is symlinked. realpath($_['SCRIPT_FILENAME'].'/../../index.php') could work but see http://bugs.php.net/bug.php?id=50366
define('INDEX_FILE_LOCATION', dirname(dirname(dirname(dirname(__FILE__)))) . '/index.php');
require_once(dirname(dirname(__FILE__)) . '/classes/cliTool/CliTool.inc.php');
require_once('PHP/CompatInfo.php');

class PhpCompat extends CommandLineTool {
	/** @var string the directory or file to be checked */
	var $input_file;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 *  The first argument must be the file to check
	 */
	function __construct($argv = array()) {
		parent::__construct($argv);

		// Show the usage screen if explicitly requested or wrong
		// number of command line arguments.
		$wrongArgCount = (count($this->argv) != 1 ? true : false);
		if ($wrongArgCount || $argv[0] == '-h') {
			$this->usage();
			if ($wrongArgCount) {
				printf("\nWrong number of arguments!", $this->input_file);
				exit(1);
			} else {
				exit(0);
			}
		}

		// Set the source file or directory to be parsed
		$this->input_file = $this->argv[0];

		// Check whether the source exists
		if (!file_exists($this->input_file)) {
			printf("Invalid source \"%s\"!\n", $this->input_file);
			exit(1);
		}
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Script to check the PHP version compatibility of a given source\n"
			. "file or directory.\n\n"
			. "Usage: {$this->scriptName} input_file|input_directory\n";
	}

	/**
	 * Parse the given file or directory and determine the
	 * minimum PHP version needed to execute the code.
	 */
	function execute() {
		// We render in text mode. We configure 40/12/40 columns for
		// filename/extension/constant rendering.
		$driverOptions = array(
			'silent' => false,
			'progress' => 'bar',
			'colwidth' => array(
				'f' => 50,
				'e' => 12,
				'c' => 40
			)
		);
		$info = new PHP_CompatInfo('text', $driverOptions);
		$info->parseData($this->input_file);
	}
}

$tool = new PhpCompat(isset($argv) ? $argv : array());
$tool->execute();
?>
