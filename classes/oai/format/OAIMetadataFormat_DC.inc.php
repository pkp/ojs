<?php

/**
 * @defgroup oai_format
 */
 
/**
 * @file classes/oai/format/OAIMetadataFormat_DC.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_DC
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- Dublin Core.
 */

// $Id$


class OAIMetadataFormat_DC extends OAIMetadataFormat {

	/**
	 * @see OAIMetadataFormat#toXML
	 */
	function toXML(&$record) {
		// Add page information to sources
		if (!empty($record->pages)) foreach ((array) $record->sources as $a => $b) {
			$record->sources[$a] .= '; ' . $record->pages;
		}

		$response = "<oai_dc:dc\n" .
			"\txmlns:oai_dc=\"http://www.openarchives.org/OAI/2.0/oai_dc/\"\n" .
			"\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/\n" .
			"\thttp://www.openarchives.org/OAI/2.0/oai_dc.xsd\">\n" .
			$this->formatElement('title', $record->titles, true) .
			$this->formatElement('creator', $record->creator) .
			$this->formatElement('subject', $record->subjects, true) .
			$this->formatElement('description', $record->descriptions, true) .
			$this->formatElement('publisher', $record->publishers, true) .
			$this->formatElement('contributor', $record->contributors, true) .
			$this->formatElement('date', $record->date) .
			$this->formatElement('type', $record->types, true) .
			$this->formatElement('format', $record->format) .
			$this->formatElement('identifier', $record->url) .
			$this->formatElement('source', $record->sources, true) .
			$this->formatElement('language', $record->language) .
			$this->formatElement('relation', $record->relation) .
			$this->formatElement('coverage', $record->coverage, true) .
			$this->formatElement('rights', $record->rights) .
			"</oai_dc:dc>\n";

		return $response;
	}

	/**
	 * Format XML for single DC element.
	 * @param $name string
	 * @param $value mixed
	 * @param $multilingual boolean optional
	 */
	function formatElement($name, $value, $multilingual = false) {
		if (!is_array($value)) {
			$value = array($value);
		}

		$response = '';
		foreach ($value as $key => $v) {
			if (!$multilingual) $response .= "\t<dc:$name>" . $this->oai->prepOutput($v) . "</dc:$name>\n";
			else {
				if (is_array($v)) {
					foreach ($v as $subV) {
						$response .= "\t<dc:$name xml:lang=\"$key\">" . $this->oai->prepOutput($subV) . "</dc:$name>\n";
					}
				} else {
					$response .= "\t<dc:$name xml:lang=\"$key\">" . $this->oai->prepOutput($v) . "</dc:$name>\n";
				}
			}
		}
		return $response;
	}

}

?>
