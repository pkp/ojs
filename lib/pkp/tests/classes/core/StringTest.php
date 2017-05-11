<?php

/**
 * @file tests/classes/core/StringTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StringTest
 * @ingroup tests_classes_core
 * @see String
 *
 * @brief Tests for the String class.
 */


require_mock_env('env1');

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.core.PKPString');

class StringTest extends PKPTestCase {
	/**
	 * @covers PKPString::titleCase
	 */
	public function testTitleCase() {
		AppLocale::setTranslations(array('common.titleSmallWords' => 'is a'));
		$originalTitle = 'AND This IS A TEST title';
		self::assertEquals('And This is a Test Title', PKPString::titleCase($originalTitle));
	}

	/**
	 * @covers PKPString::trimPunctuation
	 */
	public function testTrimPunctuation() {
		$trimmedChars = array(
			' ', ',', '.', ';', ':', '!', '?',
			'(', ')', '[', ']', '\\', '/'
		);

		foreach($trimmedChars as $trimmedChar) {
			self::assertEquals('trim.med',
					PKPString::trimPunctuation($trimmedChar.'trim.med'.$trimmedChar));
		}
	}

	/**
	 * @covers PKPString::diff
	 */
	public function testDiff() {
		// Test two strings that have common substrings.
		$originalString = 'The original string.';
		$editedString = 'The edited original.';
		$expectedDiff = array(
			array( 0 => 'The'),
			array( 1 => ' edited'),
			array( 0 => ' original'),
			array( -1 => ' string'),
			array( 0 => '.')
		);
		$resultDiff = PKPString::diff($originalString, $editedString);
		self::assertEquals($expectedDiff, $resultDiff);

		// Test two completely different strings.
		$originalString = 'abc';
		$editedString = 'def';
		$expectedDiff = array(
			array( -1 => 'abc'),
			array( 1 => 'def')
		);
		$resultDiff = PKPString::diff($originalString, $editedString);
		self::assertEquals($expectedDiff, $resultDiff);

		// A more realistic example from the citation editor use case
		$originalString = 'Willinsky, B. (2006). The access principle: The case for open acces to research and scholarship. Cambridge, MA: MIT Press.';
		$editedString = 'Willinsky, J. (2006). The access principle: The case for open access to research and scholarship. Cambridge, MA: MIT Press.';
		$expectedDiff = array(
			array( 0 => 'Willinsky, ' ),
			array( -1 => 'B' ),
			array( 1 => 'J' ),
			array( 0 => '. (2006). The access principle: The case for open acce' ),
			array( 1 => 's' ),
			array( 0 => 's to research and scholarship. Cambridge, MA: MIT Press.' )
		);
		$resultDiff = PKPString::diff($originalString, $editedString);
		self::assertEquals($expectedDiff, $resultDiff);
	}
}
?>
