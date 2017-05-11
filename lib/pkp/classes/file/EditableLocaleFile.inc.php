<?php

/**
 * @file classes/file/EditableLocaleFile.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditableLocaleFile
 * @ingroup file
 *
 * @brief This extension of LocaleFile.inc.php supports updating.
 *
 */

import('lib.pkp.classes.file.EditableFile');

class EditableLocaleFile extends LocaleFile {
	var $editableFile;

	/**
	 * Constructor
	 * @param $locale string Locale code
	 * @param $filename string Filename
	 */
	function __construct($locale, $filename) {
		parent::__construct($locale, $filename);
		$this->editableFile = new EditableFile($this->filename);
	}

	/**
	 * Write the modified contents back to disk.
	 * @return boolean True indicates success
	 */
	function write() {
		return $this->editableFile->write();
	}

	/**
	 * Get the file contents.
	 * @return string
	 */
	function getContents() {
		return $this->editableFile->getContents();
	}

	/**
	 * Set the file contents.
	 * @param $contents string New file contents.
	 */
	function setContents($contents) {
		$this->editableFile->setContents($contents);
	}

	/**
	 * Update a locale key with a new value.
	 * @param $key string Locale key.
	 * @param $value string New value.
	 * @return boolean True iff the change was successful.
	 */
	function update($key, $value) {
		$matches = null;
		$quotedKey = PKPString::regexp_quote($key);
		preg_match(
			"/<message[\W]+key=\"$quotedKey\">/",
			$this->getContents(),
			$matches,
			PREG_OFFSET_CAPTURE
		);
		if (!isset($matches[0])) return false;

		$offset = $matches[0][1];
		$closeOffset = strpos($this->getContents(), '</message>', $offset);
		if ($closeOffset === false) return false;

		$newContents = substr($this->getContents(), 0, $offset);
		$newContents .= "<message key=\"$key\">" . $this->editableFile->xmlEscape($value);
		$newContents .= substr($this->getContents(), $closeOffset);
		$this->setContents($newContents);
		return true;
	}

	/**
	 * Delete a locale key from the file.
	 * @param $key string Locale key
	 * @return boolean True iff the deletion was successful.
	 */
	function delete($key) {
		$matches = null;
		$quotedKey = PKPString::regexp_quote($key);
		preg_match(
			"/[ \t]*<message[\W]+key=\"$quotedKey\">/",
			$this->getContents(),
			$matches,
			PREG_OFFSET_CAPTURE
		);
		if (!isset($matches[0])) return false;
		$offset = $matches[0][1];

		preg_match("/<\/message>[\W]*[\r]?\n/", $this->getContents(), $matches, PREG_OFFSET_CAPTURE, $offset);
		if (!isset($matches[0])) return false;
		$closeOffset = $matches[0][1] + strlen($matches[0][0]);

		$newContents = substr($this->getContents(), 0, $offset);
		$newContents .= substr($this->getContents(), $closeOffset);
		$this->setContents($newContents);
		return true;
	}

	/**
	 * Insert a new locale key and value.
	 * @param $key string Locale key
	 * @param $value string Translated value for this locale
	 * @return boolean True iff the addition was successful.
	 */
	function insert($key, $value) {
		$offset = strrpos($this->getContents(), '</locale>');
		if ($offset === false) return false;
		$newContents = substr($this->getContents(), 0, $offset);
		$newContents .= "\t<message key=\"$key\">" . $this->editableFile->xmlEscape($value) . "</message>\n";
		$newContents .= substr($this->getContents(), $offset);
		$this->setContents($newContents);
		return true;
	}
}

?>
