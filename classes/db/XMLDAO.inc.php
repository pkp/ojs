<?php

/**
 * XMLDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 *
 * XML Data Access Object base class.
 * Operations for retrieving and modifying objects from an XML data source.
 *
 * $Id$
 */

import('xml.XMLParser');

class XMLDAO {

	/**
	 * Constructor.
	 */
	function XMLDAO() {
	}
	
	/**
	 * Parse an XML file and return data in an object.
	 * @see xml.XMLParser::parse()
	 */
	function &parse($file) {
		$parser = new XMLParser();
		$data = &$parser->parse($file);
		$parser->destroy();
		return $data;
	}
	
	/**
	 * Parse an XML file with the specified handler and return data in an object.
	 * @see xml.XMLParser::parse()
	 * @param $handler reference to the handler to use with the parser.
	 */
	function &parseWithHandler($file, &$handler) {
		$parser = new XMLParser();
		$parser->setHandler(&$handler);
		$data = &$parser->parse($file);
		$parser->destroy();
		return $data;
	}
	
	/**
	 * Parse an XML file and return data in an array.
	 * @see xml.XMLParser::parseStruct()
	 */
	function &parseStruct($file, $tagsToMatch = array()) {
		$parser = new XMLParser();
		$data = &$parser->parseStruct($file, $tagsToMatch);
		$parser->destroy();
		return $data;
	}
	
	/**
	 * custom function similar to PHP var_export().
	 * used for all versions of PHP 
	 * @returns a parsable string representation of a variable
	 */	
	function &custom_var_export($inputArray, $varRep = false, $indent="  ") {
		$output .= "array (\n";
		foreach($inputArray as $thisKey => $thisValue) {
			$output .= "$indent'" . "$thisKey" . "'" . " => ";
				if (! is_array($thisValue)) {
					$output .= "'" . addslashes($thisValue) . "'";
				} else {
					$output .= "\n$indent" . $this->custom_var_export($thisValue, true, "$indent  ");
				}
			$output .= ",\n";
		}
		$indent = substr($indent,0,-2);
		$output .= "$indent)";
		if ($varRep) {
			return $output;
		} else {
			echo $output;
		}
	}	
}

?>
