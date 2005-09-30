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

	/** @var $fp int file handle */
	var $fp;
	
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
	 * Open the file.
	 * @return boolean
	 */
	function open() {
		$this->fp = @fopen($this->filePath, 'r');
		return $this->fp ? true : false;
	}
	
	/**
	 * Close the file.
	 */
	function close() {
		fclose($this->fp);
	}
	
	/**
	 * Read and return the next block/line of text.
	 * @return string (false on EOF)
	 */
	function read() {
		if (!$this->fp || feof($this->fp)) {
			return false;
		}
		return $this->doRead();
	}
	
	/**
	 * Read from the file pointer.
	 * @return string
	 */
	function doRead() {
		return fgets($this->fp, 4096);
	}
	
	
	//
	// Static methods
	//
	
	/**
	 * Create a text parser for an article file.
	 * @param $file ArticleFile
	 * @return SearchFileParser
	 */
	function &fromFile(&$file) {
		$returner = &SearchFileParser::fromFileType($file->getFileType(), $file->getFilePath());
		return $returner;
	}
	
	/**
	 * Create a text parser for a file.
	 * @param $type string
	 * @param $path string
	 */
	function &fromFileType($type, $path) {
		switch ($type) {
			case 'text/plain':
				$returner = &new SearchFileParser($path);
				break;
			case 'text/html':
			case 'application/xhtml':
			case 'application/xml':
				$returner = &new SearchHTMLParser($path);
				break;
			default:
				$returner = &new SearchHelperParser($type, $path);
		}
		return $returner;
	}
	
}

?>
