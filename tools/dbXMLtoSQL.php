<?php

/**
 * @file dbXMLtoSQL.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package tools
 * @class dbXMLtoSQL
 *
 * CLI tool to output the SQL statements corresponding to an XML database schema.
 *
 * $Id$
 */


require(dirname(__FILE__) . '/includes/cliTool.inc.php');

/** Default XML file to parse if none is specified */
define('DATABASE_XML_FILE', 'dbscripts/xml/ojs_schema.xml');

import('db.DBDataXMLParser');

class dbXMLtoSQL extends CommandLineTool {

	/** @var string type of file to parse (schema or data) */
	var $type;

	/** @var string command to execute (print|execute|upgrade) */
	var $command;
	
	/** @var string XML file to parse */
	var $inputFile;
	
	/** @var string file to save SQL statements in */
	var $outputFile;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 * 		If specified, the first argument should be the file to parse
	 */
	function dbXMLtoSQL($argv = array()) {
		parent::CommandLineTool($argv);
		
		if (isset($this->argv[0]) && in_array($this->argv[0], array('-schema', '-data'))) {
			$this->type = substr($this->argv[0], 1);
			$argOffset = 1;
		} else {
			$this->type = 'schema';
			$argOffset = 0;
		}
		
		if (!isset($this->argv[$argOffset]) || !in_array($this->argv[$argOffset], array('print', 'save', 'print_upgrade', 'save_upgrade', 'execute'))) {
			$this->usage();
			exit(1);
		}
		
		$this->command = $this->argv[$argOffset];
		
		$file = isset($this->argv[$argOffset+1]) ? $this->argv[$argOffset+1] : DATABASE_XML_FILE;
		
		if (!file_exists($file) && !file_exists(($file2 = PWD . '/' . $file))) {
			printf("Input file \"%s\" does not exist!\n", $file);
			exit(1);
		}
		
		$this->inputFile = isset($file2) ? $file2 : $file;
		
		$this->outputFile = isset($this->argv[$argOffset+2]) ? PWD . '/' . $this->argv[$argOffset+2] : null;
		if (in_array($this->command, array('save', 'save_upgrade')) && ($this->outputFile == null || (file_exists($this->outputFile) && (is_dir($this->outputFile) || !is_writeable($this->outputFile))) || !is_writable(dirname($this->outputFile)))) {
			printf("Invalid output file \"%s\"!\n", $this->outputFile);
			exit(1);
		}
	}
	
	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Script to convert and execute XML-formatted database schema and data files\n"
			. "Usage: {$this->scriptName} [-data|-schema] command [input_file] [output_file]\n"
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
		
		if ($this->type == 'schema') {
			// Parse XML schema files
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
			
		} else if ($this->type == 'data') {
			// Parse XML data files
			$dataXMLParser = &new DBDataXMLParser();
			$dataXMLParser->setDBConn($dbconn);
			$sql = $dataXMLParser->parseData($this->inputFile);
			
			switch ($this->command) {
				case 'execute':
					$schema->addSQL($sql);
					$schema->ExecuteSchema();
					break;
				case 'save':
				case 'save_upgrade':
					$schema->addSQL($sql);
					$schema->SaveSQL($this->outputFile);
					break;
				case 'print':
				case 'print_upgrade':
				default:
					$schema->addSQL($sql);
					echo @$schema->PrintSQL('TEXT') . "\n";
					break;
			}
			
			$schema->destroy();
			
			$dataXMLParser->destroy();
		}
	}
	
}

$tool = &new dbXMLtoSQL(isset($argv) ? $argv : array());
$tool->execute();
?>
