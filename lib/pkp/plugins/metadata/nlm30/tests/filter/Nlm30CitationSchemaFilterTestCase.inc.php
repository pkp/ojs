<?php

/**
 * @file plugins/metadata/nlm30/tests/filter/Nlm30CitationSchemaFilterTestCase.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30CitationSchemaFilterTestCase
 * @ingroup plugins_metadata_nlm30_tests_filter
 *
 * @brief Base class for all citation parser and lookup service implementation tests.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.metadata.MetadataDescription');

abstract class Nlm30CitationSchemaFilterTestCase extends PKPTestCase {
	//
	// Protected helper methods
	//
	/**
	 * Test a given NLM citation filter with an array of test data.
	 * @param $citationFilterTests array test data
	 * @param $filter Nlm30CitationSchemaFilter
	 */
	protected function assertNlm30CitationSchemaFilter($citationFilterTests, $filter) {
		// Execute the filter for all test citations and check the result
		foreach($citationFilterTests as $citationFilterTestIndex => $citationFilterTest) {
			// Transform citation description arrays into citation descriptions (if any);
			foreach(array('testInput', 'testOutput') as $testDataType) {
				if (is_array($citationFilterTest[$testDataType])) {
					$citationFilterTest[$testDataType] =&
							$this->instantiateNlm30CitationDescription($citationFilterTest[$testDataType]);
				}
			}
			// The expected display name of the description
			// corresponds to the filter display name.
			if (is_a($citationFilterTest['testOutput'], 'MetadataDescription')) {
				$citationFilterTest['testOutput']->setDisplayName($filter->getDisplayName());
			}

			// Execute the filter with the test description/raw citation
			$testInput =& $citationFilterTest['testInput'];
			$testOutput =& $filter->execute($testInput);

			// Prepare an error message
			if (is_string($testInput) && !$filter->getData('serverError')) {
				// A raw citation or other easy-to-display test input.
				$errorMessage = "Error in test #$citationFilterTestIndex: '$testInput'.";
			} else {
				// The test input cannot be easily rendered.
				$errorMessage = "Error in test #$citationFilterTestIndex.";
			}

			if ($filter->getData('serverError')) {
				// The error wasn't our fault.
				$this->markTestSkipped('The external service is not working at the moment.');
			}

			// The citation filter should return a result
			self::assertNotNull($testOutput, $errorMessage);

			// Test whether the returned result coincides with the expected result
			self::assertEquals($citationFilterTest['testOutput'], $testOutput, $errorMessage);
		}
	}

	/**
	 * Simulate a web service error
	 * @param $paramenters array parameters for the citation service
	 */
	protected function assertWebServiceError($citationFilterName, $constructorArguments = array()) {
		// Mock Nlm30CitationSchemaFilter->callWebService()
		$mockCPFilter =
				$this->getMock($citationFilterName, array('callWebService'), $constructorArguments);

		// Set up the callWebService() method
		// to simulate an error condition (=return null)
		$mockCPFilter->expects($this->once())
		             ->method('callWebService')
		             ->will($this->returnValue(null));

		// Call the SUT
		$citationString = 'rawCitation';
		$citationDescription = $mockCPFilter->execute($citationString);
		self::assertNull($citationDescription);
	}

	//
	// Private helper methods
	//
	/**
	 * Instantiate an NLM citation description from an array.
	 * @param $citationArray array
	 * @return MetadataDescription
	 */
	private function &instantiateNlm30CitationDescription(&$citationArray) {
		static $personGroups = array(
			'person-group[@person-group-type="author"]' => ASSOC_TYPE_AUTHOR,
			'person-group[@person-group-type="editor"]' => ASSOC_TYPE_EDITOR
		);

		// Replace the authors and editors arrays with NLM name descriptions
		foreach($personGroups as $personGroup => $personAssocType) {
			if (isset($citationArray[$personGroup])) {
				$citationArray[$personGroup] =&
						$this->instantiateNlm30NameDescriptions($citationArray[$personGroup], $personAssocType);
			}
		}

		// Instantiate the NLM citation description
		$citationDescription = new MetadataDescription('lib.pkp.plugins.metadata.nlm30.schema.Nlm30CitationSchema', ASSOC_TYPE_CITATION);
		self::assertTrue($citationDescription->setStatements($citationArray));

		return $citationDescription;
	}

	/**
	 * Instantiate an NLM name description from an array.
	 * @param $personArray array
	 * @param $assocType integer
	 * @return MetadataDescription
	 */
	private function &instantiateNlm30NameDescriptions(&$personArray, $assocType) {
		$personDescriptions = array();
		foreach ($personArray as $key => $person) {
			if ($person == PERSON_STRING_FILTER_ETAL) {
				$personDescription = 'et-al';
			} else {
				// Create a new NLM name description and fill it
				// with the values from the test array.
				$personDescription = new MetadataDescription('lib.pkp.plugins.metadata.nlm30.schema.Nlm30NameSchema', $assocType);
				self::assertTrue($personDescription->setStatements($person));
			}

			// Add the result to the descriptions list
			$personDescriptions[$key] = $personDescription;
		}
		return $personDescriptions;
	}
}
?>
