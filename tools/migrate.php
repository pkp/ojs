<?php

/**
 * migrate.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package tools
 *
 * CLI tool for migrating OJS 1.x data to OJS 2.
 *
 * $Id$
 */

require(dirname(__FILE__) . '/includes/cliTool.inc.php');

import('site.ImportOJS1');

class migrate extends CommandLineTool {

	/** @var $journalPath string */
	var $journalPath;
	
	/** @var $importPath string */
	var $importPath;
	
	/** @var $options array */
	var $options;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function migrate($argv = array()) {
		parent::CommandLineTool($argv);
		
		if (!isset($this->argv[0]) || !isset($this->argv[1])) {
			$this->usage();
			exit(1);
		}
		
		$this->journalPath = $this->argv[0];
		$this->importPath = $this->argv[1];
		$this->options = array_slice($this->argv, 2);
	}
	
	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "OJS 1 -> OJS 2 migration tool (requires OJS >= 1.1.5 and OJS >= 2.0.1)\n"
			. "Use this tool to import data from an OJS 1 system into an OJS 2 system\n\n"
			. "Usage: {$this->scriptName} [journal_path] [ojs1_path] [options]\n"
			. "journal_path      Journal path to create (E.g., \"ojs\")\n"
			. "                  If path already exists, all content except journal settings\n"
			. "                  will be imported into the existing journal\n"
			. "ojs1_path         Complete local filesystem path to the OJS 1 installation\n"
			. "                  (E.g., \"/var/www/ojs\")\n"
			. "options           importSubscriptions - import subscription type and subscriber\n"
			. "                  data\n"
			. "                  transcode - convert journal metadata from Latin1 to UTF8\n"
			. "                  verbose - print additional debugging information\n";
	}
	
	/**
	 * Execute the import command.
	 */
	function execute() {
		$importer = &new ImportOJS1();
		if ($importer->import($this->journalPath, $this->importPath, $this->options)) {
			printf("Import completed\n"
					. "Users imported:     %u\n"
					. "Issues imported:    %u\n"
					. "Articles imported:  %u\n",
				$importer->userCount,
				$importer->issueCount,
				$importer->articleCount);
		} else {
			printf("Import failed!\nERROR: %s\n", $importer->error());
		}
	}
	
}

$tool = &new migrate(isset($argv) ? $argv : array());
$tool->execute();
?>
