<?php

/**
 * @file OAIMetadataFormat_RFC1807.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package oai.format
 * @class OAIMetadataFormat_RFC1807
 *
 * OAI metadata format class -- RFC 1807.
 *
 * $Id$
 */

class OAIMetadataFormat_RFC1807 extends OAIMetadataFormat {

	/**
	 * @see OAIMetadataFormat#toXML
	 */
	function toXML(&$record) {
		$response = "<rfc1807\n" .
			"\txmlns=\"http://info.internet.isi.edu:80/in-notes/rfc/files/rfc1807.txt\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://info.internet.isi.edu:80/in-notes/rfc/files/rfc1807.txt\n" .
			"\thttp://www.openarchives.org/OAI/1.1/rfc1807.xsd\">\n" .
			"\t<bib-version>v2</bib-version>\n" .
			$this->formatElement('id', $record->url) .
			$this->formatElement('entry', $record->datestamp) .
			$this->formatElement('organization', $record->publisher) .
			$this->formatElement('organization', $record->source) .
			$this->formatElement('title', $record->title) .
			$this->formatElement('type', $record->type) .
			$this->formatElement('type', $record->relation) .
			$this->formatElement('author', $record->creator) .
			$this->formatElement('date', $record->date) .
			$this->formatElement('copyright', $record->rights) .
			$this->formatElement('other_access', 'url:' . $record->url) .
			$this->formatElement('keyword', $record->subject) .
			$this->formatElement('period', $record->coverage) .
			$this->formatElement('monitoring', $record->contributor) .
			$this->formatElement('language', $record->language) .
			$this->formatElement('abstract', $record->description) .
			"</rfc1807>\n";
			
		return $response;
	}
	
	/**
	 * Format XML for single RFC 1807 element.
	 * @param $name string
	 * @param $value mixed
	 */
	function formatElement($name, $value) {
		if (!is_array($value)) {
			$value = array($value);
		}
		
		$response = '';
		foreach ($value as $v) {
			$response .= "\t<$name>" . $this->oai->prepOutput($v) . "</$name>\n";
		}
		return $response;
	}
	
}

?>
