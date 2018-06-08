<?php
/**
 * @file tests/api/IssuesTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssuesTest
 * @ingroup tests_api
 * @see IssueHandler
 *
 * @brief Test class for the /issues endpoint
 */

import('lib.pkp.tests.PKPApiTestCase');

class IssuesTest extends PKPApiTestCase {
	/**
	 * @covers /issues
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetIssuesWithoutToken() {
		$response = $this->_sendRequest('GET', '/issues', array(), false);
	}

	/**
	 * @covers /issues
	 */
	public function testGetIssues() {
		$response = $this->_sendRequest('GET', '/issues');
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('itemsMax', $data);
		$this->assertArrayHasKey('items', $data);
	}

	/**
	 * @covers /issues
	 */
	public function testGetIssuesParameters() {
		// count parameter
		$response = $this->_sendRequest('GET', '/issues', array('count' => 1));
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertEquals(1, count($data['items']));

		// isPublished parameter
		$response = $this->_sendRequest('GET', '/issues', array('isPublished' => 0));
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertEquals(0, count($data['items']));
	}

	/**
	 * @covers /issues/current
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetCurrentIssueWithoutToken() {
		$response = $this->_sendRequest('GET', '/issues/current', array(), false);
	}

	/**
	 * @covers /issues/current
	 */
	public function testGetCurrentIssue() {
		$response = $this->_sendRequest('GET', '/issues/current');
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('id', $data);
		$this->assertArrayHasKey('title', $data);
	}

	/**
	 * @covers /issues/{issueId}
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetIssueByIdWithoutToken() {
		$issue = $this->_getFirstEntity('/issues');
		$response = $this->_sendRequest('GET', "/issues/{$issue->id}", array(), false);
	}
	
	/**
	 * @covers /issues/{issueId}
	 * @expectedException GuzzleHttp\Exception\ClientException
	 */
	public function testGetIssueByIdWithInvalidId() {
		$response = $this->_sendRequest('GET', "/issues/{$this->_invalidId}");
		$this->assertSame(404, $response->getStatusCode());
	}

	/**
	 * @covers /issues/{issueId}
	 */
	public function testGetIssueById() {
		$issue = $this->_getFirstEntity('/issues');
		$response = $this->_sendRequest('GET', "/issues/{$issue->id}");
		$this->assertEquals(200, $response->getStatusCode());
		$data = $this->_getResponseData($response);
		$this->assertArrayHasKey('id', $data);
		$this->assertArrayHasKey('_href', $data);
		$this->assertArrayHasKey('title', $data);
	}
}
