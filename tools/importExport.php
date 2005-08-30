<?php

/**
 * importExport.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package tools
 *
 * CLI tool to perform import/export tasks
 *
 * $Id$
 */

require(dirname(__FILE__) . '/includes/cliTool.inc.php');

class importExport extends CommandLineTool {

	var $command;
	var $plugin;
	var $parameters;
	
	/**
	 * Constructor.
	 * @param $argv array command-line arguments (see usage)
	 */
	function importExport($argv = array()) {
		parent::CommandLineTool($argv);
		$this->command = array_shift($this->argv);
		$this->parameters = $this->argv;
	}
	
	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Command-line tool for import/export tasks\n"
			. "Usage:\n"
			. "\t{$this->scriptName} list: List available plugins\n"
			. "\t{$this->scriptName} [pluginName] usage: Display usage information for a plugin\n"
			. "\t{$this->scriptName} [pluginName] [params...]: Invoke a plugin\n";
	}
	
	/**
	 * Parse and execute the scheduled tasks.
	 */
	function execute() {
		$plugins = PluginRegistry::loadCategory('importexport');
		if ($this->command === 'list') {
			echo "Available plugins:\n";
			if (empty($plugins)) echo "\t(None)\n";
			else foreach ($plugins as $plugin) {
				echo "\t" . $plugin->getName() . "\n";
			}
			return;
		}
		if ($this->command == 'usage' || $this->command == 'help' || $this->command == '' || ($plugin = PluginRegistry::getPlugin('importexport', $this->command))===null) {
			$this->usage();
			return;
		}

		return $plugin->executeCLI($this->scriptName, $this->parameters);
	}
	
}

$tool = &new importExport(isset($argv) ? $argv : array());
$tool->execute();
?>
