<?php

/**
 * EditableLocaleFile.inc.php
 *
 * This extension of LocaleFile.inc.php supports updating.
 *
 * $Id$
 */

class EditableLocaleFile extends LocaleFile {
	var $contents;

	function EditableLocaleFile($locale, $filename) {
		parent::LocaleFile($locale, $filename);
		$this->contents = file_get_contents($this->filename);
	}

	function write() {
		$fp = fopen($this->filename, 'w+');
		if ($fp === false) return false;
		fwrite($fp, $this->contents);
		fclose($fp);
		return true;
	}

	function update($key, $value) {
		$matches = null;
		$quotedKey = String::regexp_quote($key);
	 	preg_match(
			"/<message[\W]+key=\"$quotedKey\">/",
			$this->contents,
			$matches,
			PREG_OFFSET_CAPTURE
		);
		if (!isset($matches[0])) return false;

		$offset = $matches[0][1];
		$closeOffset = strpos($this->contents, '</message>', $offset);
		if ($closeOffset === FALSE) return false;

		$newContents = substr($this->contents, 0, $offset);
		$newContents .= "<message key=\"$key\">" . $this->xmlEscape($value);
		$newContents .= substr($this->contents, $closeOffset);
		$this->contents =& $newContents;
		return true;
	}

	function delete($key) {
		$matches = null;
		$quotedKey = String::regexp_quote($key);
	 	preg_match(
			"/[ \t]*<message[\W]+key=\"$quotedKey\">/",
			$this->contents,
			$matches,
			PREG_OFFSET_CAPTURE
		);
		if (!isset($matches[0])) return false;
		$offset = $matches[0][1];

		preg_match("/<\/message>[\W]*[\r]?\n/", $this->contents, $matches, PREG_OFFSET_CAPTURE, $offset);
		if (!isset($matches[0])) return false;
		$closeOffset = $matches[0][1] + strlen($matches[0][0]);

		$newContents = substr($this->contents, 0, $offset);
		$newContents .= substr($this->contents, $closeOffset);
		$this->contents =& $newContents;
		return true;
	}

	function insert($key, $value) {
		$offset = strrpos($this->contents, '</locale>');
		if ($offset === false) return false;
		$newContents = substr($this->contents, 0, $offset);
		$newContents .= "\t<message key=\"$key\">" . $this->xmlEscape($value) . "</message>\n";
		$newContents .= substr($this->contents, $offset);
		$this->contents =& $newContents;
	}

	function xmlEscape($value) {
		$escapedValue = XMLNode::xmlentities($value);
		if ($value !== $escapedValue) return "<![CDATA[$value]]>";
		return $value;
	}
}
?>
