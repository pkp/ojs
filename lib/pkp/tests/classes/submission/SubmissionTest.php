<?php
/**
 * @file tests/classes/article/SubmissionTest.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionTest
 * @ingroup tests_classes_submission
 * @see Submission
 *
 * @brief Test class for the Submission class
 */
import('lib.pkp.tests.PKPTestCase');
class SubmissionTest extends PKPTestCase {
	/**
	 * @see PKPTestCase::setUp()
	 */
	protected function setUp() {
		$submissionDao = Application::getSubmissionDao();
		$this->submission = $submissionDao->newDataObject();
	}
	/**
	 * @see PKPTestCase::tearDown()
	 */
	protected function tearDown() {
		unset($this->submission);
	}
	//
	// Unit tests
	//
	/**
	 * @covers Submission
	 */
	public function testPageArray() {
		$expected = array(array('i', 'ix'), array('6', '11'), array('19'), array('21'));
		// strip prefix and spaces
		$this->submission->setPages('pg. i-ix, 6-11, 19, 21');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		// no spaces
		$this->submission->setPages('i-ix,6-11,19,21');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		// double-hyphen
		$this->submission->setPages('i--ix,6--11,19,21');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		// single page
		$expected = array(array('16'));
		$this->submission->setPages('16');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		// spaces in a range
		$expected = array(array('16', '20'));
		$this->submission->setPages('16 - 20');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		// pages are alphanumeric
		$expected = array(array('a6', 'a12'), array('b43'));
		$this->submission->setPages('a6-a12,b43');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		// inconsisent formatting
		$this->submission->setPages('pp:  a6 -a12,   b43');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		$this->submission->setPages('  a6 -a12,   b43 ');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		// empty-ish values
		$expected = array();
		$this->submission->setPages('');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		$this->submission->setPages(' ');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
		$expected = array(array('0'));
		$this->submission->setPages('0');
		$pageArray = $this->submission->getPageArray();
		$this->assertSame($expected,$pageArray);
	}

	/**
	 * @covers Submission
	 */
	public function testGetStartingPage() {
		$expected = 'i';
		// strip prefix and spaces
		$this->submission->setPages('pg. i-ix, 6-11, 19, 21');
		$startingPage = $this->submission->getStartingPage();
		$this->assertSame($expected,$startingPage);
		// no spaces
		$this->submission->setPages('i-ix,6-11,19,21');
		$startingPage = $this->submission->getStartingPage();
		$this->assertSame($expected,$startingPage);
		// double-hyphen
		$this->submission->setPages('i--ix,6--11,19,21');
		$startingPage = $this->submission->getStartingPage();
		$this->assertSame($expected,$startingPage);
		// single page
		$expected = '16';
		$this->submission->setPages('16');
		$startingPage = $this->submission->getStartingPage();
		$this->assertSame($expected,$startingPage);
		// spaces in a range
		$this->submission->setPages('16 - 20');
		$startingPage = $this->submission->getStartingPage();
		$this->assertSame($expected,$startingPage);
		// pages are alphanumeric
		$expected = 'a6';
		$this->submission->setPages('a6-a12,b43');
		$startingPage = $this->submission->getStartingPage();
		$this->assertSame($expected,$startingPage);
		// inconsisent formatting
		$this->submission->setPages('pp:  a6 -a12,   b43');
		$startingPage = $this->submission->getStartingPage();
		$this->assertSame($expected,$startingPage);
		$this->submission->setPages('  a6 -a12,   b43 ');
		$startingPage = $this->submission->getStartingPage();
		$this->assertSame($expected,$startingPage);
		// empty-ish values
		$expected = '';
		$this->submission->setPages('');
		$startingPage = $this->submission->getStartingPage();
		$this->assertSame($expected,$startingPage);
		$this->submission->setPages(' ');
		$startingPage = $this->submission->getStartingPage();
		$this->assertSame($expected,$startingPage);
		$expected = '0';
		$this->submission->setPages('0');
		$startingPage = $this->submission->getStartingPage();
		$this->assertSame($expected,$startingPage);
	}

	/**
	 * @covers Submission
	 */
	public function testGetEndingPage() {
		$expected = '21';
		// strip prefix and spaces
		$this->submission->setPages('pg. i-ix, 6-11, 19, 21');
		$endingPage = $this->submission->getEndingPage();
		$this->assertSame($expected,$endingPage);
		// no spaces
		$this->submission->setPages('i-ix,6-11,19,21');
		$endingPage = $this->submission->getEndingPage();
		$this->assertSame($expected,$endingPage);
		// double-hyphen
		$this->submission->setPages('i--ix,6--11,19,21');
		$endingPage = $this->submission->getEndingPage();
		$this->assertSame($expected,$endingPage);
		// single page
		$expected = '16';
		$this->submission->setPages('16');
		$endingPage = $this->submission->getEndingPage();
		$this->assertSame($expected,$endingPage);
		// spaces in a range
		$expected = '20';
		$this->submission->setPages('16 - 20');
		$endingPage = $this->submission->getEndingPage();
		$this->assertSame($expected,$endingPage);
		// pages are alphanumeric
		$expected = 'b43';
		$this->submission->setPages('a6-a12,b43');
		$endingPage = $this->submission->getEndingPage();
		$this->assertSame($expected,$endingPage);
		// inconsisent formatting
		$this->submission->setPages('pp:  a6 -a12,   b43');
		$endingPage = $this->submission->getEndingPage();
		$this->assertSame($expected,$endingPage);
		$this->submission->setPages('  a6 -a12,   b43 ');
		$endingPage = $this->submission->getEndingPage();
		$this->assertSame($expected,$endingPage);
		// empty-ish values
		$expected = '';
		$this->submission->setPages('');
		$endingPage = $this->submission->getEndingPage();
		$this->assertSame($expected,$endingPage);
		$this->submission->setPages(' ');
		$endingPage = $this->submission->getEndingPage();
		$this->assertSame($expected,$endingPage);
		$expected = '0';
		$this->submission->setPages('0');
		$endingPage = $this->submission->getEndingPage();
		$this->assertSame($expected,$endingPage);
	}

}
?>
