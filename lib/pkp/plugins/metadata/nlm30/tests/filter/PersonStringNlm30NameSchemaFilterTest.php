<?php

/**
 * @file plugins/metadata/nlm30/tests/filter/PersonStringNlm30NameSchemaFilterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PersonStringNlm30NameSchemaFilterTest
 * @ingroup plugins_metadata_nlm30_tests_filter
 * @see PersonStringNlm30NameSchemaFilter
 *
 * @brief Tests for the PersonStringNlm30NameSchemaFilter class.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.plugins.metadata.nlm30.filter.PersonStringNlm30NameSchemaFilter');

class PersonStringNlm30NameSchemaFilterTest extends PKPTestCase {
	/**
	 * @covers PersonStringNlm30NameSchemaFilter
	 * @covers Nlm30PersonStringFilter
	 */
	public function testExecuteWithSinglePersonString() {
		$personArgumentArray = array(
			array('MULLER', false, false),                           // surname
			array('His Excellency B.C. Van de Haan', true, false),   // initials prefix surname + title
			array('Mrs. P.-B. von Redfield-Brownfox', true, false),  // initials prefix double-surname with hyphen + title
			array('Professor K-G. Brown, MA, MSc.', true, true),     // initials surname + title + degree
			array('IFC O\'Connor', false, false),                    // initials surname
			array('Peters, H. C.', false, false),                    // surname, initials
			array('Fernandes Lopes, Paula', false, false),           // double-surname with spaces, initials
			array('Peters HC', false, false),                        // surname initials
			array('Yu, QK', false, false),                           // short surname, initials
			array('Yu QK', false, false),                            // short surname initials
			array('Sperling, Hans P.', false, false),                // surname, firstname initials
			array('Hans P. Sperling', false, false),                 // firstname initials surname
			array('Sperling, Hans Peter B.', false, false),          // surname, firstname middlename initials
			array('Hans Peter B. Sperling', false, false),           // firstname middlename initials surname
			array('Peters, Herbert', false, false),                  // surname, firstname
			array('Prof. Dr. Bernd Rutherford', true, false),        // firstname surname + title
			array('Her Honour Ruth-Martha Rheinfels', true, false),  // double-firstname surname + title
			array('Sperling, Hans Peter', false, false),             // surname, firstname middlename
			array('Hans Peter Sperling', false, false),              // firstname middlename surname
			array('Adelshausen III, H. (Gustav) von', false, false), // surname suffix, initials (firstname) prefix
			array('Adelshausen, (Gustav)', false, false),            // ibid.
			array('Gustav.Adelshausen', false, false),               // firstname.lastname (for ParaCite support)
			array('# # # Greenberg # # #', false, false),            // catch-all
		);
		$expectedResults = array(
			array(null, null, null, 'Muller'),
			array('His Excellency', array('B', 'C'), 'Van de', 'Haan'),
			array('Mrs.', array('P','B'), 'von', 'Redfield-Brownfox'),
			array('Professor - MA; MSc', array('K', 'G'), null, 'Brown'),
			array(null, array('I', 'F', 'C'), 'O\'', 'Connor'),
			array(null, array('H', 'C'), null, 'Peters'),
			array(null, array('Paula'), null, 'Fernandes Lopes'),
			array(null, array('H', 'C'), null, 'Peters'),
			array(null, array('Q', 'K'), null, 'Yu'),
			array(null, array('Q', 'K'), null, 'Yu'),
			array(null, array('Hans', 'P'), null, 'Sperling'),
			array(null, array('Hans', 'P'), null, 'Sperling'),
			array(null, array('Hans', 'Peter', 'B'), null, 'Sperling'),
			array(null, array('Hans', 'Peter', 'B'), null, 'Sperling'),
			array(null, array('Herbert'), null, 'Peters'),
			array('Prof. Dr.', array('Bernd'), null, 'Rutherford'),
			array('Her Honour', array('Ruth-Martha'), null, 'Rheinfels'),
			array(null, array('Hans', 'Peter'), null, 'Sperling'),
			array(null, array('Hans', 'Peter'), null, 'Sperling'),
			array('III', array('Gustav', 'H'), 'von', 'Adelshausen'),
			array(null, array('Gustav'), null, 'Adelshausen'),
			array(null, array('Gustav'), null, 'Adelshausen'),
			array(null, null, null, '# # # Greenberg # # #'),
		);

		$personStringNlm30NameSchemaFilter = new PersonStringNlm30NameSchemaFilter(ASSOC_TYPE_AUTHOR);
		foreach($personArgumentArray as $testNumber => $personArguments) {
			$personStringNlm30NameSchemaFilter->setFilterTitle($personArguments[1]);
			$personStringNlm30NameSchemaFilter->setFilterDegrees($personArguments[2]);
			$personDescription =& $personStringNlm30NameSchemaFilter->execute($personArguments[0]);
			$this->assertPerson($expectedResults[$testNumber], $personDescription, $testNumber);
		}
	}

	/**
	 * @covers PersonStringNlm30NameSchemaFilter
	 * @covers Nlm30PersonStringFilter
	 * @depends testExecuteWithSinglePersonString
	 */
	public function testExecuteWithMultiplePersonsStrings() {
		$personsString = 'MULLER:IFC Peterberg:Peters HC:Yu QK:Hans Peter B. Sperling:et al';
		$expectedResults = array(
			array(null, null, null, 'Muller'),
			array(null, array('I', 'F', 'C'), null, 'Peterberg'),
			array(null, array('H', 'C'), null, 'Peters'),
			array(null, array('Q', 'K'), null, 'Yu'),
			array(null, array('Hans', 'Peter', 'B'), null, 'Sperling'),
		);

		$personStringNlm30NameSchemaFilter = new PersonStringNlm30NameSchemaFilter(ASSOC_TYPE_AUTHOR, PERSON_STRING_FILTER_MULTIPLE);
		$personDescriptions =& $personStringNlm30NameSchemaFilter->execute($personsString);
		// The last description should be an 'et-al' string
		self::assertEquals(PERSON_STRING_FILTER_ETAL, array_pop($personDescriptions));
		foreach($personDescriptions as $testNumber => $personDescription) {
			$this->assertPerson($expectedResults[$testNumber], $personDescription, $testNumber);
		}

		// Test again, this time with title and degrees
		$personsString = 'Dr. MULLER; IFC Peterberg; Prof. Peters HC, MSc.; Yu QK;Hans Peter B. Sperling; etal';
		$expectedResults = array(
			array('Dr.', null, null, 'Muller'),
			array(null, array('I', 'F', 'C'), null, 'Peterberg'),
			array('Prof. - MSc', array('H', 'C'), null, 'Peters'),
			array(null, array('Q', 'K'), null, 'Yu'),
			array(null, array('Hans', 'Peter', 'B'), null, 'Sperling'),
		);

		$personStringNlm30NameSchemaFilter->setFilterTitle(true);
		$personStringNlm30NameSchemaFilter->setFilterDegrees(true);
		$personDescriptions =& $personStringNlm30NameSchemaFilter->execute($personsString);
		// The last description should be an 'et-al' string
		self::assertEquals(PERSON_STRING_FILTER_ETAL, array_pop($personDescriptions));
		foreach($personDescriptions as $testNumber => $personDescription) {
			$this->assertPerson($expectedResults[$testNumber], $personDescription, $testNumber);
		}

		// Test whether Vancouver style comma separation works correctly
		$personsString = 'Peterberg IFC, Peters HC, Sperling HP';
		$expectedResults = array(
			array(null, array('I', 'F', 'C'), null, 'Peterberg'),
			array(null, array('H', 'C'), null, 'Peters'),
			array(null, array('H', 'P'), null, 'Sperling')
		);
		$personStringNlm30NameSchemaFilter->setFilterTitle(false);
		$personStringNlm30NameSchemaFilter->setFilterDegrees(false);
		$personDescriptions =& $personStringNlm30NameSchemaFilter->execute($personsString);
		foreach($personDescriptions as $testNumber => $personDescription) {
			$this->assertPerson($expectedResults[$testNumber], $personDescription, $testNumber);
		}

		// Single name strings should not be cut when separated by comma.
		$personsString = 'Willinsky, John';
		$expectedResult = array(null, array('John'), null, 'Willinsky');
		$personDescriptions =& $personStringNlm30NameSchemaFilter->execute($personsString);
		$this->assertEquals(1, count($personDescriptions));
		$this->assertPerson($expectedResult, $personDescriptions[0], $testNumber);

		// Test APA style author tokenization.
		$singleAuthor = array(1 => 'Berndt, T. J.');
		$twoAuthors = array(2 => 'Wegener-Prent, D. T., & Petty, R. E.');
		$threeToSevenAuthors = array(6 => 'Kernis Wettelberger, M. H., Cornell, D. P., Sun, C. R., Berry, A., Harlow, T., & Bach, J. S.');
		$moreThanSevenAuthors = array(7 => 'Miller, F. H., Choi, M.J., Angeli, L. L., Harland, A. A., Stamos, J. A., Thomas, S. T., . . . Rubin, L. H.');
		$singleEditor = array(1 => 'A. Editor');
		$twoEditors = array(2 => 'A. Editor-Double & B. Editor');
		$threeToSevenEditors = array(6 => 'M.H. Kernis Wettelberger, D. P. Cornell, C.R. Sun, A. Berry, T. Harlow & J.S. Bach');
		$moreThanSevenEditors = array(7 => 'F. H. Miller, M. J. Choi, L. L. Angeli, A. A. Harland, J. A. Stamos, S. T. Thomas . . . L. H. Rubin');
		foreach(array($singleAuthor , $twoAuthors, $threeToSevenAuthors, $moreThanSevenAuthors,
				$singleEditor, $twoEditors, $threeToSevenEditors, $moreThanSevenEditors) as $test) {
			$expectedNumber = key($test);
			$testString = current($test);
			$personDescriptions =& $personStringNlm30NameSchemaFilter->execute($testString);
			$this->assertEquals($expectedNumber, count($personDescriptions), 'Offending string: '.$testString);
		}
	}

	/**
	 * Test a given person description against an array of expected results
	 * @param $expectedResultArray array
	 * @param $personDescription MetadataDescription
	 * @param $testNumber integer The test number for debugging purposes
	 */
	private function assertPerson($expectedResultArray, $personDescription, $testNumber) {
		self::assertEquals($expectedResultArray[0], $personDescription->getStatement('suffix'), "Wrong suffix for test $testNumber.");
		self::assertEquals($expectedResultArray[1], $personDescription->getStatement('given-names'), "Wrong given-names for test $testNumber.");
		self::assertEquals($expectedResultArray[2], $personDescription->getStatement('prefix'), "Wrong prefix for test $testNumber.");
		self::assertEquals($expectedResultArray[3], $personDescription->getStatement('surname'), "Wrong surname for test $testNumber.");
	}
}
?>
