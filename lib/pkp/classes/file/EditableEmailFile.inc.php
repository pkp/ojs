<?php

/**
 * @file classes/file/EditableEmailFile.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditableEmailFile
 * @ingroup file
 *
 * @brief This class supports updating for email XML files.
 *
 */

import('lib.pkp.classes.file.EditableFile');

class EditableEmailFile {
	var $locale;
	var $editableFile;

	/**
	 * Constructor
	 * @param $locale string Locale code
	 * @param $filename string Filename
	 */
	function __construct($locale, $filename) {
		$this->locale = $locale;
		$this->editableFile = new EditableFile($filename);
	}

	/**
	 * Report whether or not the file exists.
	 * @return boolean
	 */
	function exists() {
		return $this->editableFile->exists();
	}

	/**
	 * Write the file to disk.
	 * @return boolean True iff success.
	 */
	function write() {
		return $this->editableFile->write();
	}

	/**
	 * Get the file contents.
	 * @return string File contents.
	 */
	function getContents() {
		return $this->editableFile->getContents();
	}

	/**
	 * Set the file contents buffer contents.
	 * @param $contents string
	 */
	function setContents($contents) {
		$this->editableFile->setContents($contents);
	}

	/**
	 * Update an email in the buffer.
	 * @param $key string Email key.
	 * @param $subject string Email subject.
	 * @param $body string Email body.
	 * @param $description string Email description.
	 * @return boolean True iff success.
	 */
	function update($key, $subject, $body, $description) {
		$matches = null;
		$quotedKey = PKPString::regexp_quote($key);
		preg_match(
			"/<email_text[\W]+key=\"$quotedKey\">/",
			$this->getContents(),
			$matches,
			PREG_OFFSET_CAPTURE
		);
		if (!isset($matches[0])) return false;

		$offset = $matches[0][1];
		$closeOffset = strpos($this->getContents(), '</email_text>', $offset);
		if ($closeOffset === FALSE) return false;

		$newContents = substr($this->getContents(), 0, $offset);
		$newContents .= '<email_text key="' . $this->editableFile->xmlEscape($key) . '">
		<subject>' . $this->editableFile->xmlEscape($subject) . '</subject>
		<body>' . $this->editableFile->xmlEscape($body) . '</body>
		<description>' . $this->editableFile->xmlEscape($description) . '</description>
	';
		$newContents .= substr($this->getContents(), $closeOffset);
		$this->setContents($newContents);
		return true;
	}

	/**
	 * Delete an email from the file buffer.
	 * @param $key string Email key.
	 * @return boolean True iff success.
	 */
	function delete($key) {
		$matches = null;
		$quotedKey = PKPString::regexp_quote($key);
		preg_match(
			"/<email_text[\W]+key=\"$quotedKey\">/",
			$this->getContents(),
			$matches,
			PREG_OFFSET_CAPTURE
		);
		if (!isset($matches[0])) return false;
		$offset = $matches[0][1];

		preg_match("/<\/email_text>[ \t]*[\r]?\n/", $this->getContents(), $matches, PREG_OFFSET_CAPTURE, $offset);
		if (!isset($matches[0])) return false;
		$closeOffset = $matches[0][1] + strlen($matches[0][0]);

		$newContents = substr($this->getContents(), 0, $offset);
		$newContents .= substr($this->getContents(), $closeOffset);
		$this->setContents($newContents);
		return true;
	}

	/**
	 * Insert an email into the file buffer.
	 * @param $key string Email key.
	 * @param $subject string Email subject.
	 * @param $body string Email body.
	 * @param $description string Email description.
	 * @return boolean True iff success.
	 */
	function insert($key, $subject, $body, $description) {
		$offset = strrpos($this->getContents(), '</email_texts>');
		if ($offset === false) return false;
		$newContents = substr($this->getContents(), 0, $offset);
		$newContents .= '	<email_text key="' . $this->editableFile->xmlEscape($key) . '">
		<subject>' . $this->editableFile->xmlEscape($subject) . '</subject>
		<body>' . $this->editableFile->xmlEscape($body) . '</body>
		<description>' . $this->editableFile->xmlEscape($description) . '</description>
	</email_text>
';
		$newContents .= substr($this->getContents(), $offset);
		$this->setContents($newContents);
		return true;
	}
}

?>
