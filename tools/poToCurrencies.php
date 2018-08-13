<?php

/**
 * @file tools/poToCurrencies.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class poToCurrencies
 * @ingroup tools
 *
 * @brief CLI tool to convert a .PO file for ISO4217 into the currencies.xml format
 * supported by the PKP suite.
 */

require(dirname(__FILE__) . '/bootstrap.inc.php');

define('PO_TO_CSV_TOOL', '/usr/bin/po2csv');

class poToCurrencies extends CommandLineTool {
	/** @var $locale string */
	var $locale;

	/** @var $translationFile string */
	var $translationFile;

	/**
	 * Constructor
	 */
	function __construct($argv = array()) {
		parent::__construct($argv);

		array_shift($argv); // Shift tool name off the top
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
		echo "Script to convert PO file to OJS's ISO4217 XML format\n"
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
		$currencyDao = DAORegistry::getDAO('CurrencyDAO');
		$currencies = $currencyDao->getCurrencies();

		// Generate a map of code => translation
		$outputMap = array();
		foreach ($currencies as $currency) {
			$english = $currency->getName();

			if (!isset($translationMap[$english])) {
				echo "WARNING: Unknown currency \"$english\"! Using English as default.\n";
			} else {
				$currency->setName($translationMap[$english]);
			}
			$outputMap[] = $currency;
		}

		// Use the map to convert the currency list to the new locale
		$ofn = 'locale/' . $this->locale . '/currencies.xml';
		$oh = fopen($ofn, 'w');
		if (!$oh) die ("Unable to $ofn for writing.\n");

		fwrite($oh, '<?xml version="1.0" encoding="UTF-8"?>

<!--
  * currencies.xml
  *
  * Copyright (c) 2003-2018 Simon Fraser University
  * Copyright (c) 2003-2018 John Willinsky
  * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
  *
  * Localized list of currencies.
  -->

<!DOCTYPE currencies [
	<!ELEMENT currencies (currency+)>
		<!ATTLIST currencies
			locale CDATA #REQUIRED>
	<!ELEMENT currency EMPTY>
		<!ATTLIST currency
			code_alpha CDATA #REQUIRED
			code_numeric CDATA #REQUIRED
			name CDATA #REQUIRED>
]>

<currencies locale="' . $this->locale . "\">\n");
		foreach ($outputMap as $currency) {
			fwrite($oh, "	<currency name=\"" . $currency->getName() . "\" code_alpha=\"" . $currency->getCodeAlpha() . "\" code_numeric=\"" . $currency->getCodeNumeric() . "\" />\n");
		}

		fwrite($oh, "</currencies>");
		fclose($oh);
	}
}

$tool = new poToCurrencies(isset($argv) ? $argv : array());
$tool->execute();

?>
