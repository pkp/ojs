<?php

/**
 * @file classes/i18n/EditableLocaleFile.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditableLocaleFile
 * @ingroup i18n 
 *
 * @brief This extension of LocaleFile.inc.php supports updating.
 */

// $Id$


import('file.EditableFile');

class EditableLocaleFile extends LocaleFile {
	var $editableFile;

	function EditableLocaleFile($locale, $filename) {
		parent::LocaleFile($locale, $filename);
		$this->editableFile =& new EditableFile($this->filename);
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

	function update($key, $value) {
		$matches = null;
		$quotedKey = String::regexp_quote($key);
		preg_match(
			"/<message[\W]+key=\"$quotedKey\">/",
			$this->getContents(),
			$matches,
			PREG_OFFSET_CAPTURE
		);
		if (!isset($matches[0])) return false;

		$offset = $matches[0][1];
		$closeOffset = strpos($this->getContents(), '</message>', $offset);
		if ($closeOffset === FALSE) return false;

		$newContents = substr($this->getContents(), 0, $offset);
		$newContents .= "<message key=\"$key\">" . $this->editableFile->xmlEscape($value);
		$newContents .= substr($this->getContents(), $closeOffset);
		$this->setContents($newContents);
		return true;
	}

	function delete($key) {
		$matches = null;
		$quotedKey = String::regexp_quote($key);
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

	function insert($key, $value) {
		$offset = strrpos($this->getContents(), '</locale>');
		if ($offset === false) return false;
		$newContents = substr($this->getContents(), 0, $offset);
		$newContents .= "\t<message key=\"$key\">" . $this->editableFile->xmlEscape($value) . "</message>\n";
		$newContents .= substr($this->getContents(), $offset);
		$this->setContents($newContents);
	}
}
?>
