<?php

/**
 * dbXMLtoSQL.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package tools
 *
 * CLI tool to output the SQL statements corresponding to an XML database schema.
 *
 * $Id$
 */


require(dirname(__FILE__) . '/includes/cliTool.inc.php');

/** Default XML file to parse if none is specified */
define('DATABASE_XML_FILE', 'dbscripts/xml/ojs_schema.xml');

class dbXMLtoSQL extends CommandLineTool {

	/** @var string command to execute (print|execute|upgrade) */
	var $command;
	
	/** @var string XML file to parse */
	var $inputFile;
	
	/** @var string file to save SQL statements in */
	var $outputFile;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments. The first argument of should be the file to parse
	 */
	function dbXMLtoSQL($argv = array()) {
		parent::CommandLineTool($argv);
		
		if (!isset($this->argv[1]) || !in_array($this->argv[1], array('print', 'save', 'print_upgrade', 'save_upgrade', 'execute'))) {
			$this->usage();
			exit(1);
		}
		
		$this->command = $this->argv[1];
		
		$file = isset($this->argv[2]) ? $this->argv[2] : DATABASE_XML_FILE;
		
		if (!file_exists($file) && !file_exists(($file2 = PWD . '/' . $file))) {
			printf("Input file \"%s\" does not exist!\n", $file);
			exit(1);
		}
		
		$this->inputFile = isset($file2) ? $file2 : $file;
		
		$this->outputFile = isset($this->argv[3]) ? PWD . '/' . $this->argv[3] : null;
		if (in_array($this->command, array('save', 'save_upgrade')) && ($this->outputFile == null || (file_exists($this->outputFile) && (is_dir($this->outputFile) || !is_writeable($this->outputFile))) || !is_writable(dirname($this->outputFile)))) {
			printf("Invalid output file \"%s\"!\n", $this->outputFile);
			exit(1);
		}
	}
	
	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Usage: dbXMLtoSQL.php command [input_file] [output_file]\n"
			. "Supported commands:\n"
			. "    print - print SQL statements\n"
			. "    save - save SQL statements to output_file\n"
			. "    print_upgrade - print upgrade SQL statements for current database\n"
			. "    save_upgrade - save upgrade SQL statements to output_file\n"
			. "    execute - execute SQL statements on current database\n";
	}
	
	/**
	 * Parse an XML database file and output the corresponding SQL statements.
	 * See dbscripts/xml/xmlschema.dtd for the format of the XML files.
	 */
	function execute() {
		require('adodb/adodb-xmlschema.inc.php');
		
		if (in_array($this->command, array('print', 'save'))) {
			// Don't connect to actual database (so parser won't build upgrade XML)
			$conn = &new DBConnection(
				Config::getVar('database', 'driver'),
				null,
				null,
				null,
				null,
				true,
				Config::getVar('i18n', 'connection_charset')
			);
			$dbconn = $conn->getDBConn();
			
		} else {
			// Create or upgrade existing database
			$dbconn = &DBConnection::getConn();
		}
					
		$schema = &new adoSchema($dbconn, Config::getVar('i18n', 'database_charset'));
		$sql = $schema->parseSchema($this->inputFile);
		
		switch ($this->command) {
			case 'execute':
				$schema->ExecuteSchema();
				break;
			case 'save':
			case 'save_upgrade':
				$schema->SaveSQL($this->outputFile);
				break;
			case 'print':
			case 'print_upgrade':
			default:
				echo @$schema->PrintSQL('TEXT') . "\n";
				break;
		}
		
		$schema->destroy();
	}
	
}

$tool = &new dbXMLtoSQL(isset($argv) ? $argv : array());
$tool->execute();
?>
