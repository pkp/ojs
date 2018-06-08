<?php
/**
 * @file tests/api/SubmissionsTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionsTest
 * @ingroup tests_api
 * @see SubmissionHandler
 *
 * @brief Test class for the /submissions endpoint
 */

import('lib.pkp.tests.PKPSubmissionsTest');

class SubmissionsTest extends PKPSubmissionsTest {
	/**
	 * Search and return a specific submission used for galleys testing
	 * @return Submission
	 */
	protected function _getSubmissionForGalleyTesting() {
		$title = "Cyclomatic Complexity";
		$submissions = $this->_findSubmissionByTitle($title);
		$this->assertTrue(is_array($submissions));
		$submission = array_shift($submissions);
		$this->assertTrue($submission instanceof Submission);
		$this->assertContains($title, $submission->getTitle(AppLocale::getLocale()));
		return $submission;
	}

	/**
	 * @covers /submissions/{submissionId}/galleys
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetSubmissionGalleysWithoutToken() {
		$submission = $this->_getSubmissionForGalleyTesting();
		$submissionId = $submission->getId();
		$response = $this->_sendRequest('GET', "/submissions/{$submissionId}/galleys", array(), false);
	}

	/**
	 * @covers /submissions/{submissionId}/galleys
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetSubmissionGalleysWithInvalidId() {
		$response = $this->_sendRequest('GET', "/submissions/{$this->_invalidId}/galleys");
		$this->assertEquals(404, $response->getStatusCode());
	}

	/**
	 * @covers /submissions/{submissionId}/galleys
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetSubmissionGalleysForUnpublishedSubmissions() {
		$title = "Antimicrobial";
		$submissions = $this->_findSubmissionByTitle($title, STATUS_QUEUED);
		$this->assertTrue(is_array($submissions));
		$submission = array_shift($submissions);
		$this->assertTrue($submission instanceof Submission);
		$this->assertContains($title, $submission->getTitle(AppLocale::getLocale()));
		$submissionId = $submission->getId();
		$response = $this->_sendRequest('GET', "/submissions/{$submissionId}/galleys");
		$this->assertEquals(404, $response->getStatusCode());
	}

	/**
	 * @covers /submissions/{submissionId}/galleys
	 */
	public function testGetSubmissionGalleys() {
		$submission = $this->_getSubmissionForGalleyTesting();
		$submissionId = $submission->getId();
		$response = $this->_sendRequest('GET', "/submissions/{$submissionId}/galleys");
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertEquals(1, count($data));
	}
}

