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

class XMLDAO {

	/**
	 * Constructor.
	 */
	function XMLDAO() {
	}
	
	/**
	 * Parse an XML file and return data in an array.
	 * @param $file string full path to the file
	 * @param $tagsToMatch array optional, if set tags not in the array will be skipped
	 * @return array a struct of the form ($TAG => array('attributes' => array( ... ), 'value' => $VALUE), ... )
	 */
	function &parse($file, $tagsToMatch = array()) {
		if (!file_exists($file)) {
			return false;
		}
		
		$data = array();
		
		// Parse XML file into PHP-style struct (see http://php.net/xml_parse_into_struct)
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, join('', file($file)), $values, $tags);
		xml_parser_free($parser);

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
	
}

?>
