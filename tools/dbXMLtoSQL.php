<?php

/**
 * @file tools/dbXMLtoSQL.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class dbXMLtoSQL
 * @ingroup tools
 *
 * @brief CLI tool to output the SQL statements corresponding to an XML database schema.
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

import('lib.pkp.classes.cliTool.XmlToSqlTool');

/** Default XML file to parse if none is specified */
define('DATABASE_XML_FILE', 'dbscripts/xml/ojs_schema.xml');

class dbXMLtoSQL extends XmlToSqlTool {
	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 * 	If specified, the first argument should be the file to parse
	 */
	function __construct($argv = array()) {
		parent::__construct($argv);
	}
}

$tool = new dbXMLtoSQL(isset($argv) ? $argv : array());
$tool->execute();

?>
