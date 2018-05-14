<?php
/**
 * @file tests/api/UsersTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsersTest
 * @ingroup tests_api
 * @see UserHandler
 *
 * @brief Test class for the /users endpoint
 */

import('lib.pkp.tests.PKPApiTestCase');

class UsersTest extends PKPApiTestCase {
	/**
	 * @covers /users
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetUsersWithoutToken() {
		$response = $this->_sendRequest('GET', '/users', array(), false);
	}

	/**
	 * @covers /issues
	 */
	public function testGetUsers() {
		$response = $this->_sendRequest('GET', '/users');
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('itemsMax', $data);
		$this->assertArrayHasKey('items', $data);
	}

	/**
	 * @covers /issues
	 */
	public function testGetUsersParameters() {
		$count = 12;
		$response = $this->_sendRequest('GET', '/users');
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		// lcount parameter
		$response = $this->_sendRequest('GET', '/users', array('count' => $count));
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('itemsMax', $data);
		$this->assertArrayHasKey('items', $data);
		$this->assertEquals($count, count($data['items']));
		// status parameter
		$response = $this->_sendRequest('GET', '/users', array('status' => 'disabled'));
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('itemsMax', $data);
		$this->assertArrayHasKey('items', $data);
		$this->assertEquals(0, $data['itemsMax']);
		$this->assertEquals(0, count($data['items']));
		// roleIds parameter
		$response = $this->_sendRequest('GET', '/users', array('roleIds' => ROLE_ID_MANAGER));
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertEquals(3, count($data['items']));
	}

	/**
	 * @covers /users/{userId}
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetUserByIdWithoutToken() {
		$response = $this->_sendRequest('GET', '/users/1', array(), false);
	}
	
	/**
	 * @covers /users/{userId}
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetUserByIdWithInvalidId() {
		$response = $this->_sendRequest('GET', '/users/999');
		$this->assertSame(404, $response->getStatusCode());
	}
	
	/**
	 * @covers /users/{userId}
	 */
	public function testGetUserById() {
		$response = $this->_sendRequest('GET', '/users/1');
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('id', $data);
		$this->assertArrayHasKey('userName', $data);
		$this->assertArrayHasKey('fullName', $data);
	}

	/**
	 * @covers /users/reviewers
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetReviewersWithoutToken() {
		$response = $this->_sendRequest('GET', '/users/reviewers', array(), false);
	}
	
	/**
	 * @covers /users/reviewers
	 */
	public function testGetReviewers() {
		$response = $this->_sendRequest('GET', '/users/reviewers');
		$this->assertSame(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('itemsMax', $data);
		$this->assertArrayHasKey('items', $data);
	}
	
	/**
	 * @covers /users/reviewers
	 */
	public function testGetReviewersParamaters() {
		$count = 2;
		$response = $this->_sendRequest('GET', '/users/reviewers');
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		// count parameter
		$response = $this->_sendRequest('GET', '/users/reviewers', array('count' => $count));
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('itemsMax', $data);
		$this->assertArrayHasKey('items', $data);
		$this->assertEquals($count, count($data['items']));
		// status parameter
		$response = $this->_sendRequest('GET', '/users/reviewers', array('status' => 'disabled'));
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('itemsMax', $data);
		$this->assertArrayHasKey('items', $data);
		$this->assertEquals(0, $data['itemsMax']);
		$this->assertEquals(0, count($data['items']));
		// searchPhrase parameter
		$response = $this->_sendRequest('GET', '/users/reviewers', array('searchPhrase' => 'Paul'));
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertEquals(1, count($data['items']));
	}
}

