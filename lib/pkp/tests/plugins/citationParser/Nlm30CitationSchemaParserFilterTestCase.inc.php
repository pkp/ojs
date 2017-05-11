<?php

/**
 * @defgroup tests_plugins_citationParser Citation Parser Plugin Tests
 */

/**
 * @file tests/plugins/citationParser/Nlm30CitationSchemaParserFilterTestCase.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationSchemaParserFilterTestCase
 * @ingroup tests_plugins_citationParser
 *
 * @brief Base class for all Nlm30CitationSchemaFilter tests for parser filters.
 */

import('lib.pkp.plugins.metadata.nlm30.tests.filter.Nlm30CitationSchemaFilterTestCase');

abstract class Nlm30CitationSchemaParserFilterTestCase extends Nlm30CitationSchemaFilterTestCase {
	const TEST_ALL_CITATIONS = false;

	/**
	 * This will call the given filter for all raw citations
	 * contained in the file 'test-citations.txt'.
	 *
	 * It tests whether any of these citations
	 * triggers an error. It also creates a human readable and
	 * PHP parsable test result output so that the
	 * parser results can be checked (and improved) for all
	 * test citations.
	 *
	 * Setting the class constant TEST_ALL_CITATIONS to false
	 * will skip this time consuming test.
	 * @param $filter Nlm30CitationSchemaFilter
	 */
	protected function _testAllCitationsWithThisParser(&$filter) {
		// Is this test switched off?
		if (!self::TEST_ALL_CITATIONS) return;

		// Determine the test citation and result file names
		$sourceFile = dirname(__FILE__).DIRECTORY_SEPARATOR.'test-citations.txt';
		$parameterExtension = implore('', $parameters);
		$targetFile = dirname(dirname(dirname(dirname(__FILE__)))).DIRECTORY_SEPARATOR.
			'results'.DIRECTORY_SEPARATOR.$this->getCitationServiceName().$parameterExtension.'Results.inc.php';

		// Get the test citations from the source file
		$testCitationsString = file_get_contents($sourceFile);
		$testCitations = explode("\n", $testCitationsString);

		// Start the output string as a parsable php file
		$resultString = '<?php'."\n".'$citationFilterTestResults = array('."\n";

		foreach($testCitations as $rawCitationString) {
			// Call the parser service for every test citation
			$citationDescription =& $filter->execute($rawCitationString);
			self::assertNotNull($citationDescription);

			// Serialize the citation description
			$serializedCitationDescription = $this->serializeCitationDescription($citationDescription);

			// Add the result to the output string
			$rawCitationOutput = str_replace("'", "\'", $rawCitationString);
			$resultString .= "\t'$rawCitationOutput' => \n".$serializedCitationDescription.",\n";
		}

		// Close the output string
		$resultString .= ");\n?>\n";

		// Write the results file
		file_put_contents($targetFile, $resultString);
	}

	private function serializeCitationDescription(MetadataDescription &$citationDescription) {
		// Prepare transformation tables for the output serialization:
		// - the following lines will be deleted from our output file
		static $linesToDelete = array(
			'    [0-9]+ => ',
			'    array \(',
			'      \'_data\' => ',
			'    \),'
		);

		// Transform person descriptions to arrays
		$citationDescriptionArray = $citationDescription->getStatements();
		$personDescriptionProperties = array(
			'person-group[@person-group-type="author"]',
			'person-group[@person-group-type="editor"]'
		);
		foreach($personDescriptionProperties as $personDescriptionProperty) {
			if (isset($citationDescriptionArray[$personDescriptionProperty])) {
				foreach ($citationDescriptionArray[$personDescriptionProperty] as &$person) {
					$person = $person->getStatements();
				}
			}
		}

		// Transform the result into an array that we can serialize
		// in a human-readable form and also re-import as PHP-parsable code.
		$citationDescriptionOutput = var_export($citationDescriptionArray, true);
		$citationDescriptionOutputArray = explode("\n", $citationDescriptionOutput);
		foreach($citationDescriptionOutputArray as $key => &$citationDescriptionOutputLine) {
			// Remove redundant lines
			foreach($linesToDelete as $lineToDelete) {
				if (preg_match('/^'.$lineToDelete.'$/', $citationDescriptionOutputLine)) {
					unset($citationDescriptionOutputArray[$key]);
				}
			}

			// Correctly indent the output line
			$citationDescriptionOutputLine = "\t\t\t".preg_replace('/^\t\t\t/', "\t\t", str_replace('  ', "\t", $citationDescriptionOutputLine));
		}

		// Create the final serialized format
		return implode("\n", $citationDescriptionOutputArray);
	}
}
?>
