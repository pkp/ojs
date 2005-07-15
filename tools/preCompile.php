<?php

/**
 * preCompile.php
 *
 * Copyright (c) 2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package tools
 *
 * CLI tool to precompile templates and cache files.
 *
 * $Id$
 */

require(dirname(__FILE__) . '/includes/cliTool.inc.php');

class preCompile extends CommandLineTool {

	/** @var $templateMgr TemplateManager */
	var $templateMgr;
	var $helpTopicDao;
	var $helpTocDao;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 */
	function preCompile($argv = array()) {
		parent::CommandLineTool($argv);
		
		if (isset($this->argv[0]) && $this->argv[0] == '-h') {
			$this->usage();
			exit(0);
		}
	}
	
	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Script to precompile all templates and localization and help cache files\n"
			. "Usage: {$this->scriptName}\n";
	}
	
	function execute() {
		$this->compileTemplates();
		$this->compileLocales();
		$this->compileHelp();
	}
	
	function compileTemplates() {
		import('issue.IssueAction');
		import('form.Form');
		$this->templateMgr = &TemplateManager::getManager();
		$this->templateMgr->register_function('print_issue_id', array(new IssueAction(), 'smartyPrintIssueId'));
		$this->templateMgr->register_function('fieldLabel', array(new Form(null), 'smartyFieldLabel'));
		$this->_findFiles('templates', '_compileTemplate', create_function('$f', 'return preg_match(\'/\.tpl$/\', $f);'));
		$this->_findFiles('plugins', '_compilePluginTemplate', create_function('$f', 'return preg_match(\'/\.tpl$/\', $f);'));
	}
	
	function _compileTemplate($file) {
		$this->templateMgr->compile(preg_replace('|^templates/|', '', $file));
	}
	
	function _compilePluginTemplate($file) {
		$this->templateMgr->compile('file:' . getcwd() . '/' . $file);
	}
	
	function compileLocales() {
		$locales = &Locale::getAllLocales();
		foreach ($locales as $key => $name) {
			Locale::loadLocale($key);
		}
	}
	
	function compileHelp() {
		import('help.HelpToc');
		import('help.HelpTocDAO');
		import('help.HelpTopic');
		import('help.HelpTopicDAO');
		import('help.HelpTopicSection');
		$this->helpTopicDao = &DAORegistry::getDAO('HelpTopicDAO');
		$this->helpTocDao = &DAORegistry::getDAO('HelpTocDAO');
		$this->_findFiles('help', '_compileHelp', create_function('$f', 'return preg_match(\'/[\d]+\.xml$/\', $f);'));
	}
	
	function _compileHelp($file) {
		preg_match('|help/([\w]+)/(.+)\.xml|', $file, $matches);
		Request::setCookieVar('currentLocale', $matches[1]); // FIXME kludge
		
		if (strstr($matches[2], '/topic/')) {
			$this->helpTopicDao->getTopic($matches[2]);
		} else {
			$this->helpTocDao->getToc($matches[2]);
		}
	}
	
	function _findFiles($baseDir, $func, $filter = null) {
		$dir = opendir($baseDir);
		while(($file = readdir($dir)) !== false) {
			if ($file != '.' && $file != '..') {
				$path = $baseDir . '/' . $file;
				if (is_file($path)) {
					if (!isset($filter) || $filter($path)) {
						call_user_func(array($this, $func), $path);
					}
				} else {
					$this->_findFiles($path, $func, $filter);
				}
			}
		}
		
	}
	
}

$tool = &new preCompile(isset($argv) ? $argv : array());
$tool->execute();

?>
