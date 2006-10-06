<?php

/**
 * EditableEmailFile.inc.php
 *
 * This extension of LocaleFile.inc.php supports updating.
 *
 * $Id$
 */

class EditableEmailFile {
	var $contents;
	var $locale;
	var $filename;

	function EditableEmailFile($locale, $filename) {
		$this->locale = $locale;
		$this->filename = $filename;
		$this->contents = file_get_contents($this->filename);
	}

	function write() {
		$fp = fopen($this->filename, 'w+');
		if ($fp === false) return false;
		fwrite($fp, $this->contents);
		fclose($fp);
		return true;
	}

	function update($key, $subject, $body, $description) {
		$matches = null;
		$quotedKey = String::regexp_quote($key);
	 	preg_match(
			"/<row>[\W]*<field name=\"email_key\">$quotedKey<\/field>/",
			$this->contents,
			$matches,
			PREG_OFFSET_CAPTURE
		);
		if (!isset($matches[0])) return false;

		$matchedString = $matches[0][0];
		$offset = $matches[0][1];
		$closeOffset = strpos($this->contents, '</row>', $offset);
		if ($closeOffset === FALSE) return false;

		$newContents = substr($this->contents, 0, $offset);
		$newContents .= '<row>
			<field name="email_key">' . $this->xmlEscape($key) . '</field>
			<field name="subject">' . $this->xmlEscape($subject) . '</field>
			<field name="body">' . $this->xmlEscape($body) . '</field>
			<field name="description">' . $this->xmlEscape($description) . '</field>
		';
		$newContents .= substr($this->contents, $closeOffset);
		$this->contents =& $newContents;
		return true;
	}

	function insert($key, $subject, $body, $description) {
		$offset = strrpos($this->contents, '</table>');
		if ($offset === false) return false;
		$newContents = substr($this->contents, 0, $offset);
		$newContents .= '	<row>
			<field name="email_key">' . $this->xmlEscape($key) . '</field>
			<field name="subject">' . $this->xmlEscape($subject) . '</field>
			<field name="body">' . $this->xmlEscape($body) . '</field>
			<field name="description">' . $this->xmlEscape($description) . '</field>
		</row>
	';
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
