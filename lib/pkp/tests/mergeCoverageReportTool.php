<?php
/**
 * @file tests/mergeCoverageReportTool.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup tests
 *
 * @brief This script merges several coverage report files into one and
 * generates the HTML code coverage report
 *
 * @see tools/runAllTests.sh
 */

define('INDEX_FILE_LOCATION', dirname(dirname(dirname(dirname(__FILE__)))) . '/index.php');
require_once(dirname(dirname(__FILE__)) . '/classes/cliTool/CliTool.inc.php');

class MergeCoverageReportTool extends CommandLineTool {
	var $target = '';
	var $script = '';
	var $coverageFiles = array();
	var $phpUnit = '';

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 *  The first argument must be the file to check
	 */
	function MergeCoverageReportTool($argv = array()) {
		parent::CommandLineTool($argv);

		// Show the usage screen if explicitly requested or wrong
		// number of command line arguments.
		$wrongArgCount = (count($this->argv) < 2 ? true : false);
		if ($wrongArgCount || $argv[0] == '-h') {
			$this->usage();
			if ($wrongArgCount) {
				printf(PHP_EOL . 'Wrong number of arguments!' . PHP_EOL);
				exit(1);
			} else {
				exit(0);
			}
		}

		// Parse the command line arguments
		$this->script = array_shift($argv);
		$this->target = array_shift($argv);
		if (!is_dir($this->target)) {
			echo "Target directory $this->target dosn't exist" . PHP_EOL;
			exit(1);
		}
		if (!is_writable($this->target)) {
			echo "Target directory $this->target is not writable" . PHP_EOL;
			exit(1);
		}
		$this->coverageFiles = $argv;
		foreach ($this->coverageFiles as $file) {
			if (!is_readable($file)) {
				echo "Coverage file $file is not readable" . PHP_EOL;
				exit(1);
			}
		}

		// Verify that $phpunit is a file
		if (!($this->phpunit = exec('which phpunit') and is_readable($this->phpunit))) {
			echo 'Couldn\'t find phpunit in $PATH' . PHP_EOL;
			exit(1);
		}
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo 'Merges two or more phpunit coverage report files into one.' . PHP_EOL
			. "Usage: {$this->scriptName} [target_directory] [coverage_report1] [coverage_report2] ..." . PHP_EOL;
	}

	/**
	 * Merge coverage report files into one
	 */
	function execute() {
		// Include phpunit; use output buffering to prevent the output of '#!/usr/bin/env php'
		ob_start();
		require $this->phpunit;
		ob_end_clean();

		// Merge the coverage report files
		echo 'Merging code coverage reports' . PHP_EOL;
		foreach ($this->coverageFiles as $file) {
			if (!is_readable($file)) {
				echo "Code coverage file $file dosn't exist" . PHP_EOL;
				exit(1);
			}
			echo 'Merging ' . $file . '...' . PHP_EOL;

			// Include the code coverage report; this will populate $coverage
			include($file);

			if (isset($codeCoverage)) {
				$codeCoverage->filter()->addFilesToWhitelist($coverage->filter()->getWhitelist());
				$codeCoverage->merge($coverage);
			} else {
				$codeCoverage = $coverage;
			}
		}

		// Generate the HTML output
		if (isset($codeCoverage)) {
			echo 'Generating HTML coverage report' . PHP_EOL;
			$writer = new PHP_CodeCoverage_Report_HTML();
			$writer->process($codeCoverage, $this->target);
			echo 'Finished merging coverage reports' . PHP_EOL;
		}
	}
}

$tool = new MergeCoverageReportTool(isset($argv) ? $argv : array());
$tool->execute();
