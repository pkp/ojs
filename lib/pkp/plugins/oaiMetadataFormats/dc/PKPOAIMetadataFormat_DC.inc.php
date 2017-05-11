<?php

/**
 * @file plugins/oaiMetadataFormats/dc/PKPOAIMetadataFormat_DC.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPOAIMetadataFormat_DC
 * @see OAI
 *
 * @brief OAI metadata format class -- Dublin Core.
 */

class PKPOAIMetadataFormat_DC extends OAIMetadataFormat {
	/**
	 * @copydoc OAIMetadataFormat::toXML
	 */
	function toXml($dataObject, $format = null) {
		import('plugins.metadata.dc11.schema.Dc11Schema');
		$dcDescription = $dataObject->extractMetadata(new Dc11Schema());

		$response = "<oai_dc:dc\n" .
			"\txmlns:oai_dc=\"http://www.openarchives.org/OAI/2.0/oai_dc/\"\n" .
			"\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/\n" .
			"\thttp://www.openarchives.org/OAI/2.0/oai_dc.xsd\">\n";

		foreach($dcDescription->getProperties() as $propertyName => $property) { /* @var $property MetadataProperty */
			if ($dcDescription->hasStatement($propertyName)) {
				if ($property->getTranslated()) {
					$values = $dcDescription->getStatementTranslations($propertyName);
				} else {
					$values = $dcDescription->getStatement($propertyName);
				}
				$response .= $this->formatElement($propertyName, $values, $property->getTranslated());
			}
		}

		$response .= "</oai_dc:dc>\n";

		return $response;
	}

	/**
	 * Format XML for single DC element.
	 * @param $propertyName string
	 * @param $value array
	 * @param $multilingual boolean optional
	 */
	function formatElement($propertyName, $values, $multilingual = false) {
		if (!is_array($values)) $values = array($values);

		// Translate the property name to XML syntax.
		$openingElement = str_replace(array('[@', ']'), array(' ',''), $propertyName);
		$closingElement = PKPString::regexp_replace('/\[@.*/', '', $propertyName);

		// Create the actual XML entry.
		$response = '';
		foreach ($values as $key => $value) {
			if ($multilingual) {
				$key = str_replace('_', '-', $key);
				assert(is_array($value));
				foreach ($value as $subValue) {
					if ($key == METADATA_DESCRIPTION_UNKNOWN_LOCALE) {
						$response .= "\t<$openingElement>" . OAIUtils::prepOutput($subValue) . "</$closingElement>\n";
					} else {
						$response .= "\t<$openingElement xml:lang=\"$key\">" . OAIUtils::prepOutput($subValue) . "</$closingElement>\n";
					}
				}
			} else {
				assert(is_scalar($value));
				$response .= "\t<$openingElement>" . OAIUtils::prepOutput($value) . "</$closingElement>\n";
			}
		}
		return $response;
	}
}

?>
