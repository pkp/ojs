<?php

/**
 * SearchFileParser.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package search
 *
 * Abstract class to extract search text from a given file.
 *
 * $Id$
 */

class SearchFileParser {

	/** @var $filePath string the complete path to the file */
	var $filePath;
	
	/**
	 * Constructor.
	 * @param $filePath string
	 */
	function SearchFileParser($filePath) {
		$this->filePath = $filePath;
	}
	
	/**
	 * Return the path to the file.
	 * @return string
	 */
	function getFilePath() {
		return $this->filePath;
	}
	
	/**
	 * Change the file path.
	 * @param $filePath string
	 */
	function setFilePath($filePath) {
		$this->filePath = $filePath;
	}

	/**
	 * Convert the file to a string containing the text content of the file.
	 * This function should be implemented by subclasses.
	 * @return string
	 */
	function toText($filePath) {
		return('');
	}
	
}

?>
