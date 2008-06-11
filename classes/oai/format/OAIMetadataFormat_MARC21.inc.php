<?php

/**
 * @file OAIMetadataFormat_MARC21.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package oai.format
 * @class OAIMetadataFormat_MARC21
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
			"\t<controlfield tag=\"008\">\"" . date('ymd', strtotime($record->date)) . " " . date("Y", strtotime($record->date)) ."                        eng  \"</controlfield>\n" .
			$this->formatElement('042', ' ', ' ', 'a', 'dc') .
			$this->formatElement('245', '0', '0', 'a', $record->titles[$record->primaryLocale]) .
			$this->formatElement('720', ' ', ' ', 'a', $record->creator) .
			$this->formatElement('653', ' ', ' ', 'a', $this->getLocalizedData($record->subjects, $record->primaryLocale)) .
			$this->formatElement('520', ' ', ' ', 'a', $this->getLocalizedData($record->descriptions, $record->primaryLocale)) .
			$this->formatElement('260', ' ', ' ', 'b', $record->publishers[$record->primaryLocale]) .
			$this->formatElement('720', ' ', ' ', 'a', $this->getLocalizedData($record->contributors, $record->primaryLocale)) .
			$this->formatElement('260', ' ', ' ', 'c', $record->date) .
			$this->formatElement('655', ' ', '7', 'a', $record->types[$record->primaryLocale]) .
			$this->formatElement('856', ' ', ' ', 'q', $record->format) .
			$this->formatElement('856', '4', '0', 'u', $record->url) .
			$this->formatElement('786', '0', ' ', 'n', $record->sources[$record->primaryLocale] . (!empty($record->pages)?"; " . $record->pages:"")) .
			$this->formatElement('546', ' ', ' ', 'a', $record->language) .
			$this->formatElement('787', '0', ' ', 'n', $record->relation) .
			$this->formatElement('500', ' ', ' ', 'a', $this->getLocalizedData($record->coverage, $record->primaryLocale)) .
			$this->formatElement('540', ' ', ' ', 'a', $record->rights[$record->primaryLocale]) .
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
			$response .= "\t<datafield tag=\"$tag\" ind1=\"$ind1\" ind2=\"$ind2\">\n" .
				"\t\t<subfield code=\"$code\">" . $this->oai->prepOutput($v) . "</subfield>\n" .
				"\t</datafield>\n";
		}
		return $response;
	}

}

?>
