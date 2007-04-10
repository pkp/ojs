<?php

/**
 * XMLDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
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
		$parser = &new XMLParser();
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
		$parser = &new XMLParser();
		$parser->setHandler($handler);
		$data = &$parser->parse($file);
		$parser->destroy();
		return $data;
	}
	
	/**
	 * Parse an XML file and return data in an array.
	 * @see xml.XMLParser::parseStruct()
	 */
	function &parseStruct($file, $tagsToMatch = array()) {
		$parser = &new XMLParser();
		$data = &$parser->parseStruct($file, $tagsToMatch);
		$parser->destroy();
		return $data;
	}
}

?>
