<?php
/**
 * @file tests/api/BackendTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BackendTest
 * @ingroup tests_api
 * @see BackendSubmissionsHandler
 *
 * @brief Test class for the backend endpoints
 */

import('lib.pkp.tests.PKPApiTestCase');

class BackendTest extends PKPApiTestCase {
	/**
	 * @covers /_submissions
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testBackendGetSubmissionsWithoutToken() {
		$response = $this->_sendRequest('GET', '/_submissions', array(), false);
	}

	/**
	 * @covers /_submissions
	 */
	public function testBackendGetSubmissions() {
		$response = $this->_sendRequest('GET', '/_submissions');
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('itemsMax', $data);
		$this->assertArrayHasKey('items', $data);
	}

	/**
	 * @covers /_submissions
	 */
	public function testBackendGetSubmissionsParameters() {
		$count = 2;
		$response = $this->_sendRequest('GET', '/_submissions');
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		// count parameter
		$response = $this->_sendRequest('GET', '/_submissions', array('count' => $count));
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('itemsMax', $data);
		$this->assertArrayHasKey('items', $data);
		$this->assertEquals($count, count($data['items']));
		// statuses parameter
		$response = $this->_sendRequest('GET', '/_submissions', array('status' => 3));
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('itemsMax', $data);
		$this->assertArrayHasKey('items', $data);
		$this->assertEquals(6, count($data['items']));
		// searchPhrase parameter
		$response = $this->_sendRequest('GET', '/_submissions', array('searchPhrase' => 'Nigeria'));
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertEquals(1, count($data['items']));
	}
}
