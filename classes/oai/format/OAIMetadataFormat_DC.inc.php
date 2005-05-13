<?php

/**
 * OAIMetadataFormat_DC.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package oai.format
 *
 * OAI metadata format class -- Dublin Core.
 *
 * $Id$
 */

class OAIMetadataFormat_DC extends OAIMetadataFormat {

	/**
	 * @see OAIMetadataFormat#toXML
	 */
	function toXML(&$record) {
		$response = "<oai_dc:dc\n" .
			"\txmlns:oai_dc=\"http://www.openarchives.org/OAI/2.0/oai_dc/\"\n" .
			"\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/\n" .
			"\thttp://www.openarchives.org/OAI/2.0/oai_dc.xsd\">\n" .
			$this->formatElement('title', $record->title) .
			$this->formatElement('creator', $record->creator) .
			$this->formatElement('subject', $record->subject) .
			$this->formatElement('description', $record->description) .
			$this->formatElement('publisher', $record->publisher) .
			$this->formatElement('contributor', $record->contributor) .
			$this->formatElement('date', $record->date) .
			$this->formatElement('type', $record->type) .
			$this->formatElement('format', $record->format) .
			$this->formatElement('identifier', $record->identifier) .
			$this->formatElement('source', $record->source) .
			$this->formatElement('language', $record->language) .
			$this->formatElement('relation', $record->relation) .
			$this->formatElement('coverage', $record->coverage) .
			$this->formatElement('rights', $record->rights) .
			"</oai_dc:dc>\n";
			
		return $response;
	}
	
	/**
	 * Format XML for single DC element.
	 * @param $name string
	 * @param $value mixed
	 */
	function formatElement($name, $value) {
		if (!is_array($value)) {
			$value = array($value);
		}
		
		$response = '';
		foreach ($value as $v) {
			$response .= "\t<dc:$name>" . $this->oai->prepOutput($v) . "</dc:$name>\n";
		}
		return $response;
	}
	
}

?>
