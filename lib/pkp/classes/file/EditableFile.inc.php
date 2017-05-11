<?php

/**
 * @file classes/file/EditableFile.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditableFile
 * @ingroup file
 *
 * @brief Helper for editing XML files without losing formatting and comments
 * (i.e. unparsed editing).
 */

class EditableFile {
	/** @var string File contents */
	var $contents;

	/** @var string Filename */
	var $filename;

	/**
	 * Constructor
	 * @param $filename string Filename
	 */
	function __construct($filename) {
		import('lib.pkp.classes.file.FileWrapper');
		$this->filename = $filename;
		$wrapper = FileWrapper::wrapper($this->filename);
		$this->setContents($wrapper->contents());
	}

	/**
	 * Determine whether the file exists.
	 * @return boolean
	 */
	function exists() {
		return file_exists($this->filename);
	}

	/**
	 * Get the file contents.
	 * @return string
	 */
	function getContents() {
		return $this->contents;
	}

	/**
	 * Set the file contents.
	 * @param $contents string
	 */
	function setContents($contents) {
		$this->contents = $contents;
	}

	/**
	 * Write the file.
	 * @return boolean True iff success
	 */
	function write() {
		$fp = fopen($this->filename, 'w+');
		if ($fp === false) return false;
		fwrite($fp, $this->getContents());
		fclose($fp);
		return true;
	}

	/**
	 * Perform XML escaping. (This should not be used for anything outside
	 * of locale file editing in a fairly trusted environment. It is done
	 * this way to preserve formatting as much as possible.)
	 * @param $value string
	 * @return string "Escaped" string for inclusion in XML file.
	 */
	function xmlEscape($value) {
		$escapedValue = XMLNode::xmlentities($value, ENT_NOQUOTES);
		if ($value !== $escapedValue) return "<![CDATA[$value]]>";
		return $value;
	}
}

?>
