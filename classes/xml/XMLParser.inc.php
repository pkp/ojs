<?php

/**
 * XMLParser.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package xml
 *
 * Generic class for parsing an XML document into a data structure.
 *
 * $Id$
 */

// The default character encodings
define('XML_PARSER_SOURCE_ENCODING', Config::getVar('i18n', 'client_charset'));
define('XML_PARSER_TARGET_ENCODING', Config::getVar('i18n', 'client_charset'));

import('xml.XMLParserDOMHandler');

class XMLParser {

	/** @var int original magic_quotes_runtime setting */
	var $magicQuotes;
	
	/** @var object instance of XMLParserHandler */
	var $handler;

	/**
	 * Constructor.
	 * Initialize parser and set parser options.
	 */
	function XMLParser() {
		// magic_quotes_runtime must be disabled for XML parsing
		$this->magicQuotes = get_magic_quotes_runtime();
		set_magic_quotes_runtime(0);
	}
	
	/**
	 * Parse an XML file using the specified handler.
	 * If no handler has been specified, XMLParserDOMHandler is used by default, returning a tree structure representing the document.
	 * @param $file string full path to the XML file
	 * @return object actual return type depends on the handler
	 */
	function &parse($file) {
		$parser = &$this->createParser();
		
		if (!isset($this->handler)) {
			// Use default handler for parsing
			$this->setHandler(new XMLParserDOMHandler());
		}
		
		xml_set_object($parser, $this->handler);
		xml_set_element_handler($parser, "startElement", "endElement");
		xml_set_character_data_handler($parser, "characterData");
		
		$fp = fopen($file, 'r');
		if (!$fp) {
			return false;
		}
		
		while ($data = fread($fp, 4096)) {
			if (!xml_parse($parser, $data, feof($fp))) {
				echo xml_error_string(xml_get_error_code($parser));
				$this->destroyParser($parser);
				return false;
			}
		}
		
		fclose($fp);
		$this->destroyParser($parser);
		
		return $this->handler->getResult();
	}
	
	/**
	 * Set the handler to use for parse(...).
	 * @param $handler XMLParserHandler
	 */
	function setHandler(&$handler) {
		$this->handler = $handler;
	}
	
	/**
	 * Parse an XML file using xml_parse_into_struct and return data in an array.
	 * This is best suited for XML documents with fairly simple structure.
	 * @param $file string full path to the XML file
	 * @param $tagsToMatch array optional, if set tags not in the array will be skipped
	 * @return array a struct of the form ($TAG => array('attributes' => array( ... ), 'value' => $VALUE), ... )
	 */
	function &parseStruct($file, $tagsToMatch = array()) {
		// Parse file into a struct
		$parser = &$this->createParser();
		$fileContents = @file($file);
		if (!$fileContents) {
			return false;
		}
		xml_parse_into_struct($parser, join('', $fileContents), $values, $tags);
		$this->destroyParser($parser);

		// Clean up data struct, removing undesired tags if necessary
		foreach ($tags as $key => $indices) {
			if (!empty($tagsToMatch) && !in_array($key, $tagsToMatch)) {
				continue;
			}
			
			$data[$key] = array();
			
			foreach ($indices as $index) {
				if (!isset($values[$index]['type']) || ($values[$index]['type'] != 'open' && $values[$index]['type'] != 'complete')) {
					continue;
				}
				
				$data[$key][] = array(
					'attributes' => isset($values[$index]['attributes']) ? $values[$index]['attributes'] : array(),
					'value' => isset($values[$index]['value']) ? trim($values[$index]['value']) : ''
				);
			}
		}
		
		return $data;
	}
	
	/**
	 * Initialize a new XML parser.
	 * @return resource
	 */
	function &createParser() {
		$parser = xml_parser_create(XML_PARSER_SOURCE_ENCODING);
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, XML_PARSER_TARGET_ENCODING);
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
		return $parser;
	}
	
	/**
	 * Destroy XML parser.
	 * @param $parser resource
	 */
	function destroyParser(&$parser) {
		xml_parser_free($parser);
		unset($parser);
	}
	
	/**
	 * Perform required clean up for this object.
	 */
	function destroy() {
		// Set magic_quotes_runtime back to original setting
		set_magic_quotes_runtime($this->magicQuotes);
		unset($this);
	}
	
}

/**
 * Interface for handler class used by XMLParser.
 * All XML parser handler classes must implement these methods.
 */
class XMLParserHandler {

	/**
	 * Callback function to act as the start element handler.
	 */
	function startElement(&$parser, $tag, $attributes) {
	}
	
	/**
	 * Callback function to act as the end element handler.
	 */
	function endElement(&$parser, $tag) {
	}
	
	/**
	 * Callback function to act as the character data handler.
	 */
	function characterData(&$parser, $data) {
	}
	
	/**
	 * Returns a resulting data structure representing the parsed content.
	 * The format of this object is specific to the handler.
	 * @return mixed
	 */
	function &getResult() {
	}

}

?>
