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

	/** XML file to parse */
	var $file;

	/**
	 * Constructor.
	 * @param $argv array command-line arguments. The first argument of should be the file to parse
	 */
	function dbXMLtoSQL($argv = array()) {
		parent::CommandLineTool($argv);
		
		$file = isset($this->argv[1]) ? $this->argv[1] : DATABASE_XML_FILE;
		
		if (!file_exists($file) && !file_exists(($file2 = PWD . '/' . $file))) {
			printf("File %s does not exist!\n", $file);
			exit(1);
		}
		
		$this->file = isset($file2) ? $file2 : $file;
	}
	
	/**
	 * Parse an XML database file and output the corresponding SQL statements.
	 * See dbscripts/xml/xmlschema.dtd for the format of the XML files.
	 * 
	 */
	function execute() {
		require('adodb/adodb-xmlschema.inc.php');
		
		$conn = &new DBConnection(
			Config::getVar('database', 'driver'),
			null,
			null,
			null,
			null
		);
					
		$schema = &new adoSchema($conn->getConn());
		$sql = @$schema->parseSchema($this->file);
		foreach($sql as $stmt) {
			echo $stmt . "\n\n";
		}
		
		$schema->destroy();
	}
	
}

$tool = &new dbXMLtoSQL(isset($argv) ? $argv : array());
$tool->execute();
?>
