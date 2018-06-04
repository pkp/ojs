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

import('lib.pkp.tests.PKPApiTestCase');

class SubmissionsTest extends PKPApiTestCase {
	/**
	 * @covers /submissions
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetSubmissionsWithoutToken() {
		$response = $this->_sendRequest('GET', '/submissions', array(), false);
	}

	/**
	 * @covers /submissions
	 */
	public function testGetSubmissions() {
		$response = $this->_sendRequest('GET', '/submissions');
		$this->assertEquals(200, $response->getStatusCode());
		
		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('itemsMax', $data);
		$this->assertArrayHasKey('items', $data);
	}

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

	/**
	 * @covers /submissions/{submissionId}
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetSubmissionByIdWithValidIdWithoutToken() {
		$response = $this->_sendRequest('GET', '/submissions/25', array(), false);
	}

	/**
	 * @covers /submissions/{submissionId}
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetSubmissionByIdWithInvalidId() {
		$response = $this->_sendRequest('GET', '/submissions/9999');
		$this->assertSame(404, $response->getStatusCode());
	}

	/**
	 * @covers /submissions/{submissionId}
	 */
	public function testGetSubmissionByIdWithValidId() {
		$response = $this->_sendRequest('GET', '/submissions/25');
		$this->assertSame(200, $response->getStatusCode());

		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('id', $data);
		$this->assertArrayHasKey('title', $data);
		$this->assertArrayHasKey('abstract', $data);
		$this->assertArrayHasKey('authors', $data);
		$this->assertArrayHasKey('section', $data);
	}

	/**
	 * @covers /submissions/{submissionId}/participants
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetSubmissionParticipantsWithoutToken() {
		$response = $this->_sendRequest('GET', '/submissions/25/participants', array(), false);
		$this->assertSame(404, $response->getStatusCode());
	}

	/**
	 * @covers /submissions/{submissionId}/participants
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetSubmissionParticipantsWithInvalidId() {
		$response = $this->_sendRequest('GET', '/submissions/9999/participants');
		$this->assertSame(404, $response->getStatusCode());
	}

	/**
	 * @covers /submissions/{submissionId}/participants
	 */
	public function testGetSubmissionParticipants() {
		$response = $this->_sendRequest('GET', '/submissions/25/participants');
		$this->assertSame(200, $response->getStatusCode());

		$data = $this->_getResponseData($response);
		$this->assertTrue(is_array($data));
		$this->assertNotEmpty($data);

		$participant = (array) array_shift($data);
		$this->assertArrayHasKey('id', $participant);
		$this->assertArrayHasKey('_href', $participant);
		$this->assertArrayHasKey('userName', $participant);
		$this->assertArrayHasKey('email', $participant);
		$this->assertArrayHasKey('groups', $participant);
	}

	/**
	 * @covers /submissions/{submissionId}/participants/{stageId}
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetSubmissionParticipantsAssignedToStageWithoutToken() {
		$response = $this->_sendRequest('GET', '/submissions/25/participants/1', array(), false);
		$this->assertSame(404, $response->getStatusCode());
	}

	/**
	 * @covers /submissions/{submissionId}/participants/{stageId}
	 */
	public function testGetSubmissionParticipantsAssignedToStage() {
		$response = $this->_sendRequest('GET', '/submissions/25/participants/1');
		$this->assertSame(200, $response->getStatusCode());

		$data = $this->_getResponseData($response);
		$this->assertTrue(is_array($data));
		$this->assertNotEmpty($data);

		$participant = (array) array_shift($data);
		$this->assertArrayHasKey('id', $participant);
		$this->assertArrayHasKey('_href', $participant);
		$this->assertArrayHasKey('userName', $participant);
		$this->assertArrayHasKey('email', $participant);
		$this->assertArrayHasKey('groups', $participant);
	}
}

