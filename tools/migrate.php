<?php

/**
 * @file migrate.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class migrate
 * @ingroup tools
 *
 * @brief CLI tool for migrating OJS 1.x data to OJS 2.
 */

// $Id$


define('INDEX_FILE_LOCATION', dirname(dirname(__FILE__)) . '/index.php');
require(dirname(dirname(__FILE__)) . '/lib/pkp/classes/cliTool/CliTool.inc.php');

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
			. "                  redirect - generate files to map OJS 1 URLs to OJS 2 URLs.\n"
			. "                             Requires that the user running this tool has\n"
			. "                             write permission to the OJS 2 files directory.\n"
			. "                  verbose - print additional debugging information\n";
	}

	/**
	 * Execute the import command.
	 */
	function execute() {
		$importer = &new ImportOJS1();
		if ($importer->import($this->journalPath, $this->importPath, $this->options)) {
			$redirects = $importer->getRedirects();
			$conflicts = $importer->getConflicts();

			// Generate redirect files if redirect option enabled
			$redirectResults = $redirectSummary = '';
			if (in_array('redirect', $this->options) && !empty($redirects)) {
				$redirectFilesDir = Config::getVar('files', 'files_dir') . DIRECTORY_SEPARATOR . 'redirect' . DIRECTORY_SEPARATOR . $this->journalPath;
				$redirectSummary = "\n\nRedirect PHP files have been created in the following directory:\n\n$redirectFilesDir\n\nTo enable redirection, these files will need to be moved to either the OJS 1 filesystem path, or, for single journal installations, to the OJS 2 filesystem path. Once these files are moved, you can safely delete the redirect directory ($redirectFilesDir) created by this tool.\n\nSee $redirectFilesDir" . DIRECTORY_SEPARATOR . "README for more information.\n";
				$redirectReadme = "To enable redirection, the following files will need to be moved to either the OJS 1 filesystem path, or, for single journal installations, to the OJS 2 filesystem path. Once these files are moved, you can safely delete the redirect directory ($redirectFilesDir).\n\n";
				reset($redirects);
				$errors = false;

				while (list($key, $redirect) = each($redirects)) {
					$redirectFile = $redirect[0];
					$redirectDescKey = $redirect[1];
					$redirectContents = $redirect[2];

					$redirectFilePath = $redirectFilesDir . DIRECTORY_SEPARATOR . $redirectFile;
					if (FileManager::writeFile($redirectFilePath, $redirectContents) !== false) {
						$redirectReadme .= "$redirectFile\n";
						$redirectReadme .= "-- " . Locale::translate($redirectDescKey) . "\n\n";
					} else {
						$errors = true;
						$redirectSummary .= "\n\nError writing $redirectFilePath. Please ensure that the user running this script has write permission to the OJS 2 files directory.";
					}
				}

				if (!$errors) {
					FileManager::writeFile($redirectFilesDir . DIRECTORY_SEPARATOR . 'README', $redirectReadme);
				}
			}

			// Get conflicts from user import
			$conflictSummary = '';
			if (!empty($conflicts)) {
				$conflictSummary = "\n\n" . Locale::translate('admin.journals.importOJS1.conflict.desc') . "\n";
				while (list($key, $conflict) = each($conflicts)) {
					$firstUser = $conflict[0];
					$secondUser = $conflict[1];	
					$conflictSummary .= "\n* " . Locale::translate('admin.journals.importOJS1.conflict', array(
											"firstUsername" => $firstUser->getUsername(),
											"firstName" => $firstUser->getFullName(),
											"secondUsername" => $secondUser->getUsername(),
											"secondName" => $secondUser->getFullName()
										));
				}
			}

			printf("Import completed\n"
					. "Users imported:     %u\n"
					. "Issues imported:    %u\n"
					. "Articles imported:  %u\n"
					. "%s\n",
				$importer->userCount,
				$importer->issueCount,
				$importer->articleCount,
				$redirectSummary . $conflictSummary
			);
		} else {
			printf("Import failed!\nERROR: %s\n", $importer->error());
		}
	}

}

$tool = &new migrate(isset($argv) ? $argv : array());
$tool->execute();
?>
