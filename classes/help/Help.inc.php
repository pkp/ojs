<?php

/**
 * Help.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package help
 * 
 * Provides methods for translating help topic keys to their respected topic help id
 *
 * $Id$
 */

class Help {

	/**
	 * Constructor.
	 */
	function Help() {
	}
	
	/**
	 * Translate a help topic key to its numerical id.
	 * @param $key string
	 * @return string
	 */
	function translate($key) {
		static $mappings;
		
		// load help mappings
		if (!isset($mappings)) {
			$mappings = Help::loadHelpMappings();
		}

		$key = trim($key);
		if (empty($key)) {
			return '';
		}

		if (isset($mappings[$key])) {
			$helpId = $mappings[$key];
			return $helpId;

		} else {
			// Add some octothorpes to missing keys to make them more obvious
			return '##' . $key . '##';
		}
	}
	
	/**
	 * Load mappings of help page keys and their ids from an XML file (or cache, if available).
	 * @return array associative array of page keys and ids
	 */
	function &loadHelpMappings() {
		$mappings = array();

		$helpFile = "help/help.xml";
		$cacheFile = "help/cache/help.inc.php";

		if (file_exists($cacheFile) && filemtime($helpFile) < filemtime($cacheFile)) {
			// Load cached help file
			require($cacheFile);
			
		} else {

			// Reload help XML file
			$xmlDao = &new XMLDAO();
			$data = $xmlDao->parseStruct($helpFile, array('topic'));

			// Build associative array of page keys and ids
			if (isset($data['topic'])) {
				foreach ($data['topic'] as $helpData) {
					$mappings[$helpData['attributes']['key']] = $helpData['attributes']['id'];
				}
			}

			// Cache array
			if ((file_exists($cacheFile) && is_writable($cacheFile)) || (!file_exists($cacheFile) && is_writable(dirname($cacheFile)))) {
				$fp = fopen($cacheFile, 'w');
				if (function_exists('var_export')) {
					fwrite($fp, '<?php $mappings = ' . var_export($mappings, true) . '; ?>');				
				} else {
					fwrite($fp, '<?php $mappings = ' . $xmlDao->custom_var_export($mappings, true) . '; ?>');
				}				
				fclose($fp);
			}
		}

		return $mappings;	
	}
}

?>
