<?php

/**
 * @file tools/poToCountries.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class poToCountries
 * @ingroup tools
 *
 * @brief CLI tool to convert a .PO file for ISO3166 into the countries.xml format
 * supported by the PKP suite.
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

define('PO_TO_CSV_TOOL', '/usr/bin/po2csv');

class poToCountries extends CommandLineTool {
	/** @var $locale string */
	var $locale;

	/** @var $translationFile string */
	var $translationFile;

	/**
	 * Constructor
	 */
	function poToCountries($argv = array()) {
		parent::CommandLineTool($argv);

		$toolName = array_shift($argv);

		$this->locale = array_shift($argv);
		$this->translationFile = array_shift($argv);

		if (	!preg_match('/^[a-z]{2}_[A-Z]{2}$/', $this->locale) ||
			empty($this->translationFile) ||
			!file_exists($this->translationFile)
		) {
			$this->usage();
			exit(1);
		}
	}

	/**
	 * Print command usage information.
	 */
	function usage() {
		echo "Script to convert PO file to OJS's ISO3166 XML format\n"
			. "Usage: {$this->scriptName} locale /path/to/translation.po\n";
	}

	/**
	 * Rebuild the search index for all articles in all journals.
	 */
	function execute() {
		// Read the translated file as a map from English => Whatever
		$ih = popen(PO_TO_CSV_TOOL . ' ' . escapeshellarg($this->translationFile), 'r');
		if (!$ih) die ('Unable to read ' . $this->translationFile . ' using ' . PO_TO_CSV_TOOL . "\n");

		$translationMap = array();
		while ($row = fgetcsv($ih)) {
			if (count($row) != 3) continue;
			list($comment, $english, $translation) = $row;
			$translationMap[$english] = $translation;
		}
		fclose($ih);

		// Get the English map
		$countryDao =& DAORegistry::getDAO('CountryDAO');
		$countries =& $countryDao->getCountries();

		// Generate a map of code => translation
		$outputMap = array();
		foreach ($countries as $code => $english) {
			if (!isset($translationMap[$english])) {
				echo "WARNING: Unknown country \"$english\"! Using English as default.\n";
				$outputMap[$code] = $english;
			} else {
				$outputMap[$code] = $translationMap[$english];
				unset($translationMap[$english]);
			}
		}

		// Use the map to convert the country list to the new locale
		$ofn = 'registry/locale/' . $this->locale . '/countries.xml';
		$oh = fopen($ofn, 'w');
		if (!$oh) die ("Unable to $ofn for writing.\n");

		fwrite($oh, '<?xml version="1.0" encoding="UTF-8"?>

<!--
  * countries.xml
  *
  * Copyright (c) 2003-2014 John Willinsky
  * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
  *
  * Localized list of countries.
  -->

<!DOCTYPE countries [
	<!ELEMENT countries (country+)>
	<!ELEMENT country EMPTY>
		<!ATTLIST country
			code CDATA #REQUIRED
			name CDATA #REQUIRED>
]>

<countries>
');
		foreach ($outputMap as $code => $translation) {
			fwrite($oh, "	<country name=\"$translation\" code=\"$code\"/>\n");
		}

		fwrite($oh, "</countries>");
		fclose($oh);
	}
}

$tool = new poToCountries(isset($argv) ? $argv : array());
$tool->execute();

?>
