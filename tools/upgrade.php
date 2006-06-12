<?php

/**
 * upgrade.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package tools
 *
 * CLI tool for upgrading OJS.
 *
 * Note: Some functions require fopen wrappers to be enabled.
 *
 * $Id$
 */

require(dirname(__FILE__) . '/includes/cliTool.inc.php');

import('install.Upgrade');
import('site.Version');
import('site.VersionCheck');

class upgradeTool extends CommandLineTool {

	/** @var string command to execute (check|upgrade|patch|download) */
	var $command;
	
	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function upgradeTool($argv = array()) {
		parent::CommandLineTool($argv);
		
		if (!isset($this->argv[0]) || !in_array($this->argv[0], array('check', 'latest', 'upgrade', 'patch', 'download'))) {
			$this->usage();
			exit(1);
		}
		
		$this->command = $this->argv[0];
	}
	
	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Upgrade tool\n"
			. "Usage: {$this->scriptName} command\n"
			. "Supported commands:\n"
			. "    check                     perform version check\n"
			. "    latest                    display latest version info\n"
			. "    upgrade [pretend]         execute upgrade script\n"
			. "    patch                     download and apply patch for latest version\n"
			. "    download [package|patch]  download latest version (does not unpack/install)\n";
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
		$pretend = isset($this->argv[1]) && $this->argv[1] == 'pretend';
		$installer = &new Upgrade(array('manualInstall' => $pretend));
		$installer->setLogger($this);

		if ($installer->execute()) {
			if (count($installer->getNotes()) > 0) {
				printf("\nRelease Notes\n");
				printf("----------------------------------------\n");
				foreach ($installer->getNotes() as $note) {
					printf("%s\n\n", $note);
				}
			}
			
			if ($pretend) {
				if (count($installer->getSQL()) > 0) {
					printf("\nSQL\n");
					printf("----------------------------------------\n");
					foreach ($installer->getSQL() as $sql) {
						printf("%s\n\n", $sql);
					}
				}
				
			} else {
				$newVersion = &$installer->getNewVersion();
				printf("Successfully upgraded to version %s\n", $newVersion->getVersionString());
			}
			
		} else {
			printf("ERROR: Upgrade failed: %s\n", $installer->getErrorString());
		}
	}
	
	/**
	 * Apply patch to update code to latest version.
	 */
	function patch() {
		$versionInfo = VersionCheck::getLatestVersion();
		$check = $this->checkVersion($versionInfo);
		
		if ($check < 0) {
			$patch = VersionCheck::getPatch($versionInfo);
			if (!isset($patch)) {
				printf("No applicable patch available\n");
				return;
			}
				
			$outFile = $versionInfo['application'] . '-' . $versionInfo['release'] . '.patch';
			printf("Download patch: %s\n", $patch);
			printf("Patch will be saved to: %s\n", $outFile);

			if (!$this->promptContinue()) {
				exit(0);
			}
			
			$out = fopen($outFile, 'wb');
			if (!$out) {
				printf("Failed to open %s for writing\n", $outFile);
				exit(1);
			}
			
			$in = gzopen($patch, 'r');
			if (!$in) {
				printf("Failed to open %s for reading\n", $patch);
				fclose($out);
				exit(1);
			}
			
			printf('Downloading patch...');
			
			while(($data = gzread($in, 4096)) !== '') {
				printf('.');
				fwrite($out, $data);
			}
			
			printf("done\n");
			
			gzclose($in);
			fclose($out);
			
			$command = 'patch -p1 < ' . escapeshellarg($outFile);
			printf("Apply patch: %s\n", $command);

			if (!$this->promptContinue()) {
				exit(0);
			}
			
			system($command, $ret);
			if ($ret == 0) {
				printf("Successfully applied patch for version %s\n", $versionInfo['release']);
			} else {
				printf("ERROR: Failed to apply patch\n");
			}
		}
	}
	
	/**
	 * Download latest package/patch.
	 */
	function download() {
		$versionInfo = VersionCheck::getLatestVersion();
		if (!$versionInfo) {
			printf("Failed to load version info from %s\n", VersionCheck::getVersionCheckUrl());
			exit(1);
		}
		
		$type = isset($this->argv[1]) && $this->argv[1] == 'patch' ? 'patch' : 'package';
		if ($type == 'package') {
			$download = $versionInfo['package'];
		} else {
			$download = VersionCheck::getPatch($versionInfo);
		}
		if (!isset($download)) {
			printf("No applicable download available\n");
			return;
		}
		$outFile = basename($download);
		
		printf("Download %s: %s\n", $type, $download);
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
			printf("Failed to load version info from %s\n", VersionCheck::getVersionCheckUrl());
			exit(1);
		}
		
		$dbVersion = VersionCheck::getCurrentDBVersion();
		$codeVersion = VersionCheck::getCurrentCodeVersion();
		$latestVersion = $versionInfo['version'];
		
		printf("Code version:      %s\n", $codeVersion->getVersionString());
		printf("Database version:  %s\n", $dbVersion->getVersionString());
		printf("Latest version:    %s\n", $latestVersion->getVersionString());
		
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
			$patch = VersionCheck::getPatch($versionInfo, $codeVersion);
			printf("         tag:     %s\n", $versionInfo['tag']);
			printf("         date:    %s\n", $versionInfo['date']);
			printf("         info:    %s\n", $versionInfo['info']);
			printf("         package: %s\n", $versionInfo['package']);
			printf("         patch:   %s\n", isset($patch) ? $patch : 'N/A');
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

$tool = &new upgradeTool(isset($argv) ? $argv : array());
$tool->execute();
?>
