<?php

/**
 * @file EditableEmailFile.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package i18n 
 * @class EditableEmailFile
 *
 * This class supports updating for email XML files.
 *
 * $Id$
 */

import('file.EditableFile');

class EditableEmailFile {
	var $locale;
	var $editableFile;

	function EditableEmailFile($locale, $filename) {
		$this->locale = $locale;
		$this->editableFile =& new EditableFile($filename);
	}

	function write() {
		$this->editableFile->write();
	}

	function &getContents() {
		return $this->editableFile->getContents();
	}

	function setContents(&$contents) {
		$this->editableFile->setContents($contents);
	}

	function update($key, $subject, $body, $description) {
		$matches = null;
		$quotedKey = String::regexp_quote($key);
		preg_match(
			"/<row>[\W]*<field name=\"email_key\">$quotedKey<\/field>/",
			$this->getContents(),
			$matches,
			PREG_OFFSET_CAPTURE
		);
		if (!isset($matches[0])) return false;

		$offset = $matches[0][1];
		$closeOffset = strpos($this->getContents(), '</row>', $offset);
		if ($closeOffset === FALSE) return false;

		$newContents = substr($this->getContents(), 0, $offset);
		$newContents .= '<row>
			<field name="email_key">' . $this->editableFile->xmlEscape($key) . '</field>
			<field name="subject">' . $this->editableFile->xmlEscape($subject) . '</field>
			<field name="body">' . $this->editableFile->xmlEscape($body) . '</field>
			<field name="description">' . $this->editableFile->xmlEscape($description) . '</field>
		';
		$newContents .= substr($this->getContents(), $closeOffset);
		$this->setContents($newContents);
		return true;
	}

	function delete($key) {
		$matches = null;
		$quotedKey = String::regexp_quote($key);
		preg_match(
			"/[ \t]*<row>[\W]*<field name=\"email_key\">$quotedKey<\/field>/",
			$this->getContents(),
			$matches,
			PREG_OFFSET_CAPTURE
		);
		if (!isset($matches[0])) return false;
		$offset = $matches[0][1];

		preg_match("/<\/row>[ \t]*[\r]?\n/", $this->getContents(), $matches, PREG_OFFSET_CAPTURE, $offset);
		if (!isset($matches[0])) return false;
		$closeOffset = $matches[0][1] + strlen($matches[0][0]);

		$newContents = substr($this->getContents(), 0, $offset);
		$newContents .= substr($this->getContents(), $closeOffset);
		$this->setContents($newContents);
		return true;
	}

	function insert($key, $subject, $body, $description) {
		$offset = strrpos($this->getContents(), '</table>');
		if ($offset === false) return false;
		$newContents = substr($this->getContents(), 0, $offset);
		$newContents .= '	<row>
			<field name="email_key">' . $this->editableFile->xmlEscape($key) . '</field>
			<field name="subject">' . $this->editableFile->xmlEscape($subject) . '</field>
			<field name="body">' . $this->editableFile->xmlEscape($body) . '</field>
			<field name="description">' . $this->editableFile->xmlEscape($description) . '</field>
		</row>
	';
		$newContents .= substr($this->getContents(), $offset);
		$this->setContents($newContents);
	}
}

?>
