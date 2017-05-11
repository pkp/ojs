<?php

/**
 * @file classes/cliTool/UpgradeTool.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class upgradeTool
 * @ingroup tools
 *
 * @brief CLI tool for upgrading OJS.
 *
 * Note: Some functions require fopen wrappers to be enabled.
 */


define('RUNNING_UPGRADE', 1);

import('classes.install.Upgrade');
import('lib.pkp.classes.site.Version');
import('lib.pkp.classes.site.VersionCheck');

class UpgradeTool extends CommandLineTool {

	/** @var string command to execute (check|upgrade|download) */
	var $command;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function __construct($argv = array()) {
		parent::__construct($argv);

		if (!isset($this->argv[0]) || !in_array($this->argv[0], array('check', 'latest', 'upgrade', 'download'))) {
			$this->usage();
			exit(1);
		}

		$this->command = $this->argv[0];
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_INSTALLER);
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Upgrade tool\n"
			. "Usage: {$this->scriptName} command\n"
			. "Supported commands:\n"
			. "    check     perform version check\n"
			. "    latest    display latest version info\n"
			. "    upgrade   execute upgrade script\n"
			. "    download  download latest version (does not unpack/install)\n";
	}

	/**
	 * Execute the specified command.
	 */
	function execute() {
		$command = $this->command;
		$this->$command();
	}

	/**
	 * Perform version check against latest available version.
	 */
	function check() {
		$this->checkVersion(VersionCheck::getLatestVersion());
	}

	/**
	 * Print information about the latest available version.
	 */
	function latest() {
		$this->checkVersion(VersionCheck::getLatestVersion(), true);
	}

	/**
	 * Run upgrade script.
	 */
	function upgrade() {
		$installer = new Upgrade(array());
		$installer->setLogger($this);

		if ($installer->execute()) {
			if (count($installer->getNotes()) > 0) {
				printf("\nRelease Notes\n");
				printf("----------------------------------------\n");
				foreach ($installer->getNotes() as $note) {
					printf("%s\n\n", $note);
				}
			}

			$newVersion =& $installer->getNewVersion();
			printf("Successfully upgraded to version %s\n", $newVersion->getVersionString(false));

		} else {
			printf("ERROR: Upgrade failed: %s\n", $installer->getErrorString());
		}
	}

	/**
	 * Download latest package.
	 */
	function download() {
		$versionInfo = VersionCheck::getLatestVersion();
		if (!$versionInfo) {
			$application = PKPApplication::getApplication();
			printf("Failed to load version info from %s\n", $application->getVersionDescriptorUrl());
			exit(1);
		}

		$download = $versionInfo['package'];
		$outFile = basename($download);

		printf("Download: %s\n", $download);
		printf("File will be saved to: %s\n", $outFile);

		if (!$this->promptContinue()) {
			exit(0);
		}

		$out = fopen($outFile, 'wb');
		if (!$out) {
			printf("Failed to open %s for writing\n", $outFile);
			exit(1);
		}

		$in = fopen($download, 'rb');
		if (!$in) {
			printf("Failed to open %s for reading\n", $download);
			fclose($out);
			exit(1);
		}

		printf('Downloading file...');

		while(($data = fread($in, 4096)) !== '') {
			printf('.');
			fwrite($out, $data);
		}

		printf("done\n");

		fclose($in);
		fclose($out);
	}

	/**
	 * Perform version check.
	 * @param $versionInfo array latest version info
	 * @param $displayInfo boolean just display info, don't perform check
	 */
	function checkVersion($versionInfo, $displayInfo = false) {
		if (!$versionInfo) {
			$application = PKPApplication::getApplication();
			printf("Failed to load version info from %s\n", $application->getVersionDescriptorUrl());
			exit(1);
		}

		$dbVersion = VersionCheck::getCurrentDBVersion();
		$codeVersion = VersionCheck::getCurrentCodeVersion();
		$latestVersion = $versionInfo['version'];

		printf("Code version:      %s\n", $codeVersion->getVersionString(false));
		printf("Database version:  %s\n", $dbVersion->getVersionString(false));
		printf("Latest version:    %s\n", $latestVersion->getVersionString(false));

		$compare1 = $codeVersion->compare($latestVersion);
		$compare2 = $dbVersion->compare($codeVersion);

		if (!$displayInfo) {
			if ($compare2 < 0) {
				printf("Database version is older than code version\n");
				printf("Run \"{$this->scriptName} upgrade\" to update\n");
				exit(0);

			} else if($compare2 > 0) {
				printf("Database version is newer than code version!\n");
				exit(1);

			} else if ($compare1 == 0) {
				printf("Your system is up-to-date\n");

			} else if($compare1 < 0) {
				printf("A newer version is available:\n");
				$displayInfo = true;
			} else {
				printf("Current version is newer than latest!\n");
				exit(1);
			}
		}

		if ($displayInfo) {
			printf("         tag:     %s\n", $versionInfo['tag']);
			printf("         date:    %s\n", $versionInfo['date']);
			printf("         info:    %s\n", $versionInfo['info']);
			printf("         package: %s\n", $versionInfo['package']);
		}

		return $compare1;
	}

	/**
	 * Prompt user for yes/no input (default no).
	 * @param $prompt string
	 */
	function promptContinue($prompt = "Continue?") {
		printf("%s [y/N] ", $prompt);
		$continue = fread(STDIN, 255);
		return (strtolower(substr(trim($continue), 0, 1)) == 'y');
	}

	/**
	 * Log install message to stdout.
	 * @param $message string
	 */
	function log($message) {
		printf("[%s]\n", $message);
	}

}

?>
