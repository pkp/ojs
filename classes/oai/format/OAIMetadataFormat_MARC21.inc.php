<?php

/**
 * OAIMetadataFormat_MARC21.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package oai.format
 *
 * OAI metadata format class -- MARC21 (MARCXML).
 *
 * $Id$
 */

class OAIMetadataFormat_MARC21 extends OAIMetadataFormat {

	/**
	 * @see OAIMetadataFormat#toXML
	 */
	function toXML(&$record) {
		$response = "<record\n" .
			"\txmlns=\"http://www.loc.gov/MARC21/slim\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://www.loc.gov/MARC21/slim\n" .
			"\thttp://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd\">\n" .
			"\t<leader>     cam         3u     </leader>\n" .
			"\t<controlfield tag=\"008\">" . date('ymd', strtotime($record->date)) . " " . date("Y", strtotime($record->date)) ."                        eng  </controlfield>\n" .
			$this->formatElement('042', ' ', ' ', 'a', 'dc') .
			$this->formatElement('245', '0', '0', 'a', $record->title) .
			$this->formatElement('720', ' ', ' ', 'a', $record->creator) .
			$this->formatElement('653', ' ', ' ', 'a', $record->subject) .
			$this->formatElement('520', ' ', ' ', 'a', $record->description) .
			$this->formatElement('260', ' ', ' ', 'b', $record->publisher) .
			$this->formatElement('720', ' ', ' ', 'a', $record->contributor) .
			$this->formatElement('260', ' ', ' ', 'c', $record->date) .
			$this->formatElement('655', ' ', '7', 'a', $record->type) .
			$this->formatElement('856', ' ', ' ', 'q', $record->format) .
			$this->formatElement('856', '4', '0', 'u', $record->identifier) .
			$this->formatElement('786', '0', ' ', 'n', $record->source) .
			$this->formatElement('546', ' ', ' ', 'a', $record->language) .
			$this->formatElement('787', '0', ' ', 'n', $record->relation) .
			$this->formatElement('500', ' ', ' ', 'a', $record->coverage) .
			$this->formatElement('540', ' ', ' ', 'a', $record->rights) .
			"</record>\n";
			
		return $response;
	}
	
	/**
	 * Format XML for single MARC21 element.
	 * @param $tag string
	 * @param $ind1 string
	 * @param $ind2 string
	 * @param $code string
	 * @param $value mixed
	 */
	function formatElement($tag, $ind1, $ind2, $code, $value) {
		if (!is_array($value)) {
			$value = array($value);
		}
		
		$response = '';
		foreach ($value as $v) {
			$response .= "\t<dataField tag=\"$tag\" ind1=\"$ind1\" ind2=\"$ind2\">\n" .
				"\t\t<subfield code=\"$code\">$v</subfield>\n" .
				"\t</dataField>\n";
		}
		return $response;
	}
	
}

?>
