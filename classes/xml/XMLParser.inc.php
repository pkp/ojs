<?php

/**
 * @file XMLParser.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package xml
 * @class XMLParser
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
			$handler =& new XMLParserDOMHandler();
			$this->setHandler($handler);
		}

		xml_set_object($parser, $this->handler);
		xml_set_element_handler($parser, "startElement", "endElement");
		xml_set_character_data_handler($parser, "characterData");

		import('file.FileWrapper');
		$wrapper = &FileWrapper::wrapper($file);

		while (is_object($newWrapper = $wrapper->open())) if (is_object($newWrapper)) { // Handle redirects
			unset($wrapper);
			$wrapper =& $newWrapper;
			unset ($newWrapper);
		}

		if (!$wrapper) {
			$result = false;
			return $result;
		}

		while ($data = $wrapper->read()) {
			if (!xml_parse($parser, $data, $wrapper->eof())) {
				echo xml_error_string(xml_get_error_code($parser));
				$this->destroyParser($parser);
				$result = false;
				$wrapper->close();
				return $result;
			}
		}

		$wrapper->close();
		$this->destroyParser($parser);

		$result = &$this->handler->getResult();
		return $result;
	}

	/**
	 * Set the handler to use for parse(...).
	 * @param $handler XMLParserHandler
	 */
	function setHandler(&$handler) {
		$this->handler =& $handler;
	}

	/**
	 * Parse an XML file using xml_parse_into_struct and return data in an array.
	 * This is best suited for XML documents with fairly simple structure.
	 * @param $file string full path to the XML file
	 * @param $tagsToMatch array optional, if set tags not in the array will be skipped
	 * @return array a struct of the form ($TAG => array('attributes' => array( ... ), 'value' => $VALUE), ... )
	 */
	function &parseStruct($file, $tagsToMatch = array()) {
		$parser = &$this->createParser();
		import('file.FileWrapper');
		$wrapper = &FileWrapper::wrapper($file);
		$fileContents = $wrapper->contents();
		if (!$fileContents) {
			$result = false;
			return $result;
		}
		xml_parse_into_struct($parser, $fileContents, $values, $tags);
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
		// Default: Return null (must be by ref).
		$nullVar = null;
		return $nullVar;
	}

}

?>
