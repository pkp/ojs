<?php

/**
 * @file tests/functional/search/WorkflowSearchTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowSearchTest
 * @ingroup tests_functional_search
 *
 * @brief Workflow search tests
 */

import('lib.pkp.tests.WebTestCase');

class WorkflowSearchTest extends WebTestCase {
	/** @var $punctuationTitle Full title of test submission */
	static $punctuationTitle = 'The Facets Of Job Satisfaction: A Nine-Nation Comparative Study Of Construct Equivalence';
	static $punctuationTitleSnippet = 'NineNation';
	static $titleStartSnippet = 'the facets';

	/**
	 * @copydoc WebTestCase::getAffectedTables
	 */
	protected function getAffectedTables() {
		return PKP_TEST_ENTIRE_DB;
	}

	/**
	 * Test editor submission search
	 */
	function testEditorSearch() {
		$this->open(self::$baseUrl);
		$this->logIn('dbarnes');

		// Use the search to find a known submission with punctuation
		// in the title (bug #8872)
		$this->_search('Title', 'is', self::$punctuationTitle);
		$this->assertText('css=h3', 'Submission'); // Should match 1

		// Try again, this time partial and without the colon
		$this->clickAndWait('link=Editor');
		$this->_search('Title', 'contains', self::$punctuationTitleSnippet);
		$this->assertText('css=h3', 'Submissions'); // Should not match

		// Test "starts with" searches.
		$this->_search('Title', 'starts with', self::$titleStartSnippet);
		$this->assertText('css=h3', 'Submission'); // Should match 1
		$this->clickAndWait('link=Editor');

		// Ensure that the title sort heading doesn't error out.
		$this->clickAndWait('link=In Review');
		$this->clickAndWait('link=Title');
		$this->assertText('css=h2', 'Submissions in Review');

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
		// (Bug #8872)
		$this->clickAndWait('link=In Editing');
		$this->_search('Title', 'is', self::$punctuationTitle);
		$this->assertText('css=h3', 'Submission'); // Should match 1

		// Try again, this time partial and without the colon. Before
		// the fix, this would have matched a submission. After the fix,
		// it shouldn't.
		$this->clickAndWait('link=Section Editor');
		$this->clickAndWait('link=In Editing');
		$this->_search('Title', 'contains', self::$punctuationTitleSnippet);
		$this->assertText('css=h2', 'Submissions in Editing'); // No match

		// Test "starts with" searches.
		$this->_search('Title', 'starts with', self::$titleStartSnippet);
		$this->assertText('css=h3', 'Submission'); // Should match 1
		$this->clickAndWait('link=In Editing');

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
		$this->findSubmissionAsEditor('dbarnes', null, self::$punctuationTitle);
		$this->clickAndWait('link=Reject and Archive Submission');
		$this->clickAndWait('css=input.button.defaultButton');
		$this->logOut();

		$this->logIn('mfritz');
		$this->clickAndWait('link=Archive');

		// Use the search to find a known submission with punctuation
		// in the title. Should find a submission with the exact name.
		// (Bug #8872)
		$this->_search('Title', 'is', self::$punctuationTitle);
		$this->assertText('css=h3', 'Submission'); // Should match 1

		// Try again, this time partial and without the colon. Before
		// the fix, this would have matched a submission. After the fix,
		// it shouldn't.
		$this->clickAndWait('link=Copyeditor');
		$this->clickAndWait('link=Archive');
		$this->_search('Title', 'contains', self::$punctuationTitleSnippet);
		$this->assertElementPresent('css=td.nodata');

		// Test "starts with" searches.
		$this->_search('Title', 'starts with', self::$titleStartSnippet);
		$this->assertText('css=h3', 'Submission'); // Should match 1
		$this->clickAndWait('link=Archive');

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
		$this->findSubmissionAsEditor('dbarnes', null, self::$punctuationTitle);
		$this->clickAndWait('link=Reject and Archive Submission');
		$this->clickAndWait('css=input.button.defaultButton');
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
		// (Bug #8872)
		$this->_search('Title', 'is', self::$punctuationTitle);
		$this->assertText('css=h3', 'Submission'); // Should match 1

		// Try again, this time partial and without the colon. Before
		// the fix, this would have matched a submission. After the fix,
		// it shouldn't.
		$this->clickAndWait('link=Layout Editor');
		$this->clickAndWait('link=Archive');
		$this->_search('Title', 'contains', self::$punctuationTitleSnippet);
		$this->assertElementPresent('css=td.nodata'); // No match

		// Test "starts with" searches.
		$this->_search('Title', 'starts with', self::$titleStartSnippet);
		$this->assertText('css=h3', 'Submission'); // Should match 1
		$this->clickAndWait('link=Archive');

		// Ensure that the title sort heading doesn't error out.
		$this->clickAndWait('link=Title');
		$this->assertText('css=h2', 'Archive');

		$this->logOut();
	}

	/**
	 * Search for a submission.
	 * @param $field string Name of field (e.g. Title)
	 * @param $match string string is|contains Type of match
	 * @param $value string Title search text
	 */
	private function _search($field, $match, $value) {
		$this->type('//div[@id=\'main\']//input[@name=\'search\']', $value);
		$this->select('//div[@id=\'main\']//select[@name=\'searchField\']', 'label=' . $field);
		$this->select('//div[@id=\'main\']//select[@name=\'searchMatch\']', 'label=' . $match);
		$this->clickAndWait('//div[@id=\'main\']//input[@value=\'Search\']');
	}
}
