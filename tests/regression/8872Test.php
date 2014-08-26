<?php

/**
 * @file tests/regression/8872Test.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class 8872Test
 * @ingroup tests_regression
 *
 * @brief Regression test for http://pkp.sfu.ca/bugzilla/show_bug.cgi?id=8872
 */

import('lib.pkp.tests.WebTestCase');

class CreateJournalTest extends WebTestCase {
	/** @var $fullTitle Full title of test submission */
	static $fullTitle = 'The Facets Of Job Satisfaction: A Nine-Nation Comparative Study Of Construct Equivalence';

	/**
	 * Get a list of affected tables.
	 * @return array A list of tables to backup and restore.
	 */
	protected function getAffectedTables() {
		return array('articles', 'signoffs', 'email_log', 'event_log');
	}

	/**
	 * Test editor submission search
	 */
	function testEditorSearch() {
		$this->open(self::$baseUrl);
		$this->logIn('dbarnes');

		// Use the search to find a known submission with punctuation
		// in the title
		$this->_search(self::$fullTitle, 'is');
		$this->assertText('css=h3', 'Submission');

		// Try again, this time partial and without the colon
		$this->clickAndWait('link=Editor');
		$this->_search('NineNation', 'contains');
		$this->assertText('css=h3', 'Submissions');

		// Ensure that the title sort heading doesn't error out.
		$this->clickAndWait('link=Title');
		$this->assertText('css=h3', 'Submissions');

		$this->logOut();
	}

	/**
	 * Test section editor submission search
	 */
	function testSectionEditorSearch() {
		$this->open(self::$baseUrl);
		$this->logIn('sberardo');

		// Use the search to find a known submission with punctuation
		// in the title. Should find a submission with the exact name.
		$this->clickAndWait('link=In Editing');
		$this->_search(self::$fullTitle, 'is');
		$this->assertText('css=h3', 'Submission');

		// Try again, this time partial and without the colon. Before
		// the fix, this would have matched a submission. After the fix,
		// it shouldn't.
		$this->clickAndWait('link=Section Editor');
		$this->clickAndWait('link=In Editing');
		$this->_search('NineNation', 'contains');
		$this->assertText('css=h2', 'Submissions in Editing');

		// Ensure that the title sort heading doesn't error out.
		$this->clickAndWait('link=Title');
		$this->assertText('css=h2', 'Submissions in Editing');

		$this->logOut();
	}

	/**
	 * Test copyeditor submission search
	 */
	function testCopyeditorSearch() {
		$this->open(self::$baseUrl);

		// Prep: Copyeditor search is only available in archive.
		// Need to archive the submission first.
		$this->findSubmissionAsEditor('dbarnes', null, self::$fullTitle);
		$this->clickAndWait('link=Reject and Archive Submission');
		$this->clickAndWait('css=input.button.defaultButton');
		$this->logOut();

		$this->logIn('mfritz');
		$this->clickAndWait('link=Archive');

		// Use the search to find a known submission with punctuation
		// in the title. Should find a submission with the exact name.
		$this->_search(self::$fullTitle, 'is');
		$this->assertText('css=h3', 'Submission');

		// Try again, this time partial and without the colon. Before
		// the fix, this would have matched a submission. After the fix,
		// it shouldn't.
		$this->clickAndWait('link=Copyeditor');
		$this->clickAndWait('link=Archive');
		$this->_search('NineNation', 'contains');
		$this->assertElementPresent('css=td.nodata');

		// Ensure that the title sort heading doesn't error out.
		$this->clickAndWait('link=Title');
		$this->assertText('css=h2', 'Archive');

		$this->logOut();
	}

	/**
	 * Test layout editor submission search
	 */
	function testLayoutEditorSearch() {
		$this->open(self::$baseUrl);

		// Prep: Layout editor needs to be assigned and requested.
		// WARNING: This is currently dependent on the CE test above,
		// which sends the submission to the Archive. The search form
		// only exists for archived submissions.
		$this->findSubmissionAsEditor('dbarnes', null, self::$fullTitle);
		$this->clickAndWait('link=Editing');

		// Assign Graham Cox
		$this->clickAndWait('link=Assign Layout Editor');
		$this->clickAndWait('//td/a[contains(text(),\'Cox, Graham\')]/../..//a[text()=\'Assign\']');

		// Upload a layout version
		$this->click('id=layoutFileTypeSubmission');
		$this->uploadFile(getenv('DUMMYFILE'), '//input[@name="layoutFile"]', '//input[@name="layoutFile"]/../input[@value=\'Upload\']');

		// Notify Layout Editor
		$this->clickAndWait('//td[contains(text(), \'Layout Version\')]/..//img');
		$this->clickAndWait('css=input.button.defaultButton');
		$this->logOut();

		$this->logIn('gcox');
		$this->clickAndWait('link=Archive');

		// Use the search to find a known submission with punctuation
		// in the title. Should find a submission with the exact name.
		$this->_search(self::$fullTitle, 'is');
		$this->assertText('css=h3', 'Submission');

		// Try again, this time partial and without the colon. Before
		// the fix, this would have matched a submission. After the fix,
		// it shouldn't.
		$this->clickAndWait('link=Layout Editor');
		$this->clickAndWait('link=Archive');
		$this->_search('NineNation', 'contains');
		$this->assertElementPresent('css=td.nodata');

		// Ensure that the title sort heading doesn't error out.
		$this->clickAndWait('link=Title');
		$this->assertText('css=h2', 'Archive');

		$this->logOut();
	}

	/**
	 * Search for a submission by title.
	 * @param $title string Title search text
	 * @param $match string string is|contains Type of match
	 */
	private function _search($title, $match) {
		$this->type('//div[@id=\'main\']//input[@name=\'search\']', $title);
		$this->select('//div[@id=\'main\']//select[@name=\'searchMatch\']', 'label=' . $match);
		$this->clickAndWait('//div[@id=\'main\']//input[@value=\'Search\']');
	}
}
