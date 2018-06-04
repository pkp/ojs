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
	 * @covers /submissions/{submissionId}/galleys
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetSubmissionGalleysWithoutToken() {
		$response = $this->_sendRequest('GET', '/submissions/9/galleys', array(), false);
	}

	/**
	 * @covers /submissions/{submissionId}/galleys
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetSubmissionGalleysWithInvalidId() {
		$response = $this->_sendRequest('GET', '/submissions/999/galleys');
		$this->assertEquals(404, $response->getStatusCode());
	}

	/**
	 * @covers /submissions/{submissionId}/galleys
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetSubmissionGalleysForUnpublishedSubmissions() {
		$response = $this->_sendRequest('GET', '/submissions/23/galleys');
		$this->assertEquals(404, $response->getStatusCode());
	}

	/**
	 * @covers /submissions/{submissionId}/galleys
	 */
	public function testGetSubmissionGalleys() {
		$response = $this->_sendRequest('GET', '/submissions/9/galleys');
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertEquals(1, count($data));
	}
}

