<?php

/**
 * ConfigParser.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package config
 *
 * Class for parsing and modifying php.ini style configuration files.
 *
 * $Id$
 */

class ConfigParser {

	/** Contents of the config file currently being parsed */
	var $content;

	/**
	 * Constructor.
	 */
	function ConfigParser() {
	}
	
	/**
	 * Read a configuration file and update variables.
	 * This method stores the updated configuration but does not write it out.
	 * Use writeConfig() or getFileContents() afterwards to do something with the new config.
	 * @param $file string full path to the config file
	 * @param $params array an associative array of configuration parameters to update. If the value is an associative array (of variable name/value pairs) instead of a scalar, the key is treated as a section instead of a variable. Parameters not in $params remain unchanged
	 * @return boolean true if file could be read, false otherwise
	 */
	function updateConfig($file, $params) {
		if (!file_exists($file) || !is_readable($file)) {
			return false;
		}
		
		$this->content = '';
		$lines = file($file);
				
		// Parse each line of the configuration file
		for ($i=0, $count=count($lines); $i < $count; $i++) {
			$line = $lines[$i];
			
			if (preg_match('/^;/', $line) || preg_match('/^\s*$/', $line)) {
				// Comment or empty line
				$this->content .= $line;
				
			} else if (preg_match('/^\s*\[(\w+)\]/', $line, $matches)) {
				// Start of new section
				$currentSection = $matches[1];
				$this->content .= $line;
				
			} else if (preg_match('/^\s*(\w+)\s*=/', $line, $matches)) {
				// Variable definition
				$key = $matches[1];
				
				if (!isset($currentSection) && array_key_exists($key, $params) && !is_array($params[$key])) {
					// Variable not in a section
					$value = $params[$key];
					
				} else if (isset($params[$currentSection]) && is_array($params[$currentSection]) && array_key_exists($key, $params[$currentSection])) {
					// Variable in a section
					$value = $params[$currentSection][$key];
					
				} else {
					// Variable not to be changed, do not modify line
					$this->content .= $line;
					continue;
				}
								
				$this->content .= "$key = $value\n";
				
			} else {
				$this->content .= $line;
			}
		}
		
		return true;
	}
	
	/**
	 * Write contents of current config file
	 * @param $file string full path to output file
	 * @return boolean file write is successful
	 */
	function writeConfig($file) {
		if (!(file_exists($file) && is_writable($file))
			&& !(is_dir(dirname($file)) && is_writable(dirname($file)))) {
			// File location cannot be written to
			return false;
		}
		
		$fp = fopen($file, 'w');
		if (!$fp) {
			return false;
		}
		
		fwrite($fp, $this->content);
		fclose($fp);
		return true;
	}
	
	/**
	 * Return the contents of the current config file.
	 * @return string
	 */
	function getFileContents() {
		return $this->content;
	}
	
}

?>
