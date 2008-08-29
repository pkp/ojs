<?php

/**
 * @file tools/dbXMLtoSQL.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class dbXMLtoSQL
 * @ingroup tools
 *
 * @brief CLI tool to output the SQL statements corresponding to an XML database schema.
 */

// $Id$


define('INDEX_FILE_LOCATION', dirname(dirname(__FILE__)) . '/index.php');
require(dirname(dirname(__FILE__)) . '/lib/pkp/classes/cliTool/CliTool.inc.php');

import('cliTool.XmlToSqlTool');

/** Default XML file to parse if none is specified */
define('DATABASE_XML_FILE', 'dbscripts/xml/ojs_schema.xml');

class dbXMLtoSQL extends XmlToSqlTool {
	/**
	 * Constructor.
	 * @param $argv array command-line arguments
	 * 	If specified, the first argument should be the file to parse
	 */
	function dbXMLtoSQL($argv = array()) {
		parent::XmlToSqlTool($argv);
	}
}

$tool =& new dbXMLtoSQL(isset($argv) ? $argv : array());
$tool->execute();

?>
