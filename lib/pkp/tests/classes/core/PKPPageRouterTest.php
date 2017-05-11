<?php

/**
 * @file tests/classes/core/PKPPageRouterTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPPageRouterTest
 * @ingroup tests_classes_core
 * @see PKPPageRouter
 *
 * @brief Tests for the PKPPageRouter class.
 */


require_mock_env('env1');

import('lib.pkp.classes.core.PKPPageRouter');
import('lib.pkp.tests.classes.core.PKPRouterTestCase');
import('classes.security.Validation'); // This will import our mock validation class.
import('classes.i18n.AppLocale'); // This will import our mock locale.

class PKPPageRouterTest extends PKPRouterTestCase {
	protected function setUp() {
		parent::setUp();
		$this->router = $this->getMock('PKPPageRouter', array('getCacheablePages'));
		$this->router->expects($this->any())
		             ->method('getCacheablePages')
		             ->will($this->returnValue(array('cacheable')));
	}

	/**
	 * @covers PKPPageRouter::isCacheable
	 */
	public function testIsCacheableNotInstalled() {
		$this->setTestConfiguration('request2', 'classes/core/config'); // not installed
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_ENABLED);
		self::assertFalse($this->router->isCacheable($this->request));
	}

	/**
	 * @covers PKPPageRouter::isCacheable
	 */
	public function testIsCacheableWithPost() {
		$this->setTestConfiguration('request1', 'classes/core/config'); // installed
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_ENABLED);
		$_POST = array('somevar' => 'someval');
		self::assertFalse($this->router->isCacheable($this->request));
	}

	/**
	 * @covers PKPPageRouter::isCacheable
	 */
	public function testIsCacheableWithPathinfo() {
		$this->setTestConfiguration('request1', 'classes/core/config'); // installed
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_ENABLED);
		$_GET = array('somevar' => 'someval');
		$_SERVER = array(
			'PATH_INFO' => '/context1/context2/somepage'
		);
		self::assertFalse($this->router->isCacheable($this->request));

		$_GET = array();
		self::assertFalse($this->router->isCacheable($this->request));

	}

	/**
	 * @covers PKPPageRouter::isCacheable
	 */
	public function testIsCacheableWithPathinfoSuccess() {
		$this->setTestConfiguration('request1', 'classes/core/config'); // installed
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_ENABLED);
		$_GET = array();
		$_SERVER = array(
			'PATH_INFO' => '/context1/context2/cacheable'
		);

		self::assertTrue($this->router->isCacheable($this->request, true));

		Validation::setIsLoggedIn(true);
		self::assertFalse($this->router->isCacheable($this->request, true));
		Validation::setIsLoggedIn(false);
	}

	/**
	 * @covers PKPPageRouter::isCacheable
	 */
	public function testIsCacheableWithoutPathinfo() {
		$this->setTestConfiguration('request1', 'classes/core/config'); // installed
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_DISABLED);
		$_GET = array('somevar' => 'someval');
		self::assertFalse($this->router->isCacheable($this->request, true));

		$_GET = array(
			'firstContext' => 'something',
			'secondContext' => 'something',
			'page' => 'something',
			'op' => 'something',
			'path' => 'something'
		);
		self::assertFalse($this->router->isCacheable($this->request, true));
	}

	/**
	 * @covers PKPPageRouter::isCacheable
	 */
	public function testIsCacheableWithoutPathinfoSuccess() {
		$this->setTestConfiguration('request1', 'classes/core/config'); // installed
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_DISABLED);

		$_GET = array(
			'page' => 'cacheable'
		);
		self::assertTrue($this->router->isCacheable($this->request, true));
	}

	/**
	 * @covers PKPRouter::getCacheFilename
	 */
	public function testGetCacheFilename() {
		// Override parent test
	}

	/**
	 * @covers PKPPageRouter::getCacheFilename
	 */
	public function testGetCacheFilenameWithPathinfo() {
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_ENABLED);
		$_SERVER = array(
			'PATH_INFO' => '/context1/context2/index'
		);
		$expectedId = '/context1/context2/index-en_US';
		self::assertEquals(Core::getBaseDir().'/cache/wc-'.md5($expectedId).'.html', $this->router->getCacheFilename($this->request));
	}

	/**
	 * @covers PKPPageRouter::getCacheFilename
	 */
	public function testGetCacheFilenameWithoutPathinfo() {
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_DISABLED);
		$_GET = array(
			'firstContext' => 'something',
			'secondContext' => 'something',
			'page' => 'index'
		);
		$expectedId = 'something-something-index---en_US';
		self::assertEquals(Core::getBaseDir().'/cache/wc-'.md5($expectedId).'.html', $this->router->getCacheFilename($this->request));
	}

	/**
	 * @covers PKPPageRouter::getRequestedPage
	 */
	public function testGetRequestedPageWithPathinfo() {
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_ENABLED);

		$_SERVER = array(
			'PATH_INFO' => '/context1/context2/some#page'
		);
		self::assertEquals('somepage', $this->router->getRequestedPage($this->request));
	}

	/**
	 * @covers PKPPageRouter::getRequestedPage
	 */
	public function testGetRequestedPageWithPathinfoDisabled() {
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_DISABLED);

		$_GET['page'] = 'some#page';
		self::assertEquals('somepage', $this->router->getRequestedPage($this->request));
	}

	/**
	 * @covers PKPPageRouter::getRequestedPage
	 */
	public function testGetRequestedPageWithEmtpyPage() {
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_ENABLED);

		$_SERVER = array(
			'PATH_INFO' => '/context1/context2'
		);
		self::assertEquals('', $this->router->getRequestedPage($this->request));
	}

	/**
	 * @covers PKPPageRouter::getRequestedPage
	 */
	public function testGetRequestedPageWithPathinfoDisabledAndEmtpyPage() {
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_DISABLED);

		unset($_GET['page']);
		self::assertEquals('', $this->router->getRequestedPage($this->request));
	}

	/**
	 * @covers PKPPageRouter::getRequestedOp
	 */
	public function testGetRequestedOpWithPathinfo() {
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_ENABLED);

		$_SERVER = array(
			'PATH_INFO' => '/context1/context2/somepage/some#op'
		);
		self::assertEquals('someop', $this->router->getRequestedOp($this->request));
	}

	/**
	 * @covers PKPPageRouter::getRequestedOp
	 */
	public function testGetRequestedOpWithPathinfoDisabled() {
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_DISABLED);

		$_GET['op'] = 'some#op';
		self::assertEquals('someop', $this->router->getRequestedOp($this->request));
	}

	/**
	 * @covers PKPPageRouter::getRequestedOp
	 */
	public function testGetRequestedOpWithEmptyOp() {
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_ENABLED);

		$_SERVER = array(
			'PATH_INFO' => '/context1/context2/somepage'
		);
		self::assertEquals('index', $this->router->getRequestedOp($this->request));
	}

	/**
	 * @covers PKPPageRouter::getRequestedOp
	 */
	public function testGetRequestedOpWithPathinfoDisabledAndEmptyOp() {
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_DISABLED);

		unset($_GET['op']);
		self::assertEquals('index', $this->router->getRequestedOp($this->request));
	}

	/**
	 * @covers PKPPageRouter::url
	 */
	public function testUrlWithPathinfo() {
		$this->setTestConfiguration('request1', 'classes/core/config'); // restful URLs
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_ENABLED);
		$_SERVER = array(
			'SERVER_NAME' => 'mydomain.org',
			'SCRIPT_NAME' => '/index.php',
			'PATH_INFO' => '/current-context1/current-context2/current-page/current-op'
		);

		// Simulate context DAOs
		$this->_setUpMockDAOs();

		$result = $this->router->url($this->request);
		self::assertEquals('http://mydomain.org/index.php/current-context1/current-context2/current-page/current-op', $result);

		$result = $this->router->url($this->request, 'new-context1');
		self::assertEquals('http://mydomain.org/index.php/new-context1/current-context2', $result);

		$result = $this->router->url($this->request, array('new-context1', 'new?context2'));
		self::assertEquals('http://mydomain.org/index.php/new-context1/new%3Fcontext2', $result);

		$result = $this->router->url($this->request, array(), 'new-page');
		self::assertEquals('http://mydomain.org/index.php/current-context1/current-context2/new-page', $result);

		$result = $this->router->url($this->request, array(), null, 'new-op');
		self::assertEquals('http://mydomain.org/index.php/current-context1/current-context2/current-page/new-op', $result);

		$result = $this->router->url($this->request, 'new-context1', 'new-page');
		self::assertEquals('http://mydomain.org/index.php/new-context1/current-context2/new-page', $result);

		$result = $this->router->url($this->request, 'new-context1', 'new-page', 'new-op');
		self::assertEquals('http://mydomain.org/index.php/new-context1/current-context2/new-page/new-op', $result);

		$result = $this->router->url($this->request, 'new-context1', null, 'new-op');
		self::assertEquals('http://mydomain.org/index.php/new-context1/current-context2/index/new-op', $result);

		$result = $this->router->url($this->request, 'new-context1', null, null, 'add?path');
		self::assertEquals('http://mydomain.org/index.php/new-context1/current-context2/index/index/add%3Fpath', $result);

		$result = $this->router->url($this->request, 'new-context1', null, null, array('add-path1', 'add?path2'));
		self::assertEquals('http://mydomain.org/index.php/new-context1/current-context2/index/index/add-path1/add%3Fpath2', $result);

		$result = $this->router->url($this->request, array('firstContext' => null, 'secondContext' => null), null, 'new-op', 'add-path');
		self::assertEquals('http://mydomain.org/index.php/current-context1/current-context2/current-page/new-op/add-path', $result);

		$result = $this->router->url($this->request, 'new-context1', null, null, null,
			array(
				'key1' => 'val1?',
				'key2' => array('val2-1', 'val2?2')
			)
		);
		self::assertEquals('http://mydomain.org/index.php/new-context1/current-context2?key1=val1%3F&key2[]=val2-1&key2[]=val2%3F2', $result);

		$result = $this->router->url($this->request, 'new-context1', null, null, null, null, 'some?anchor');
		self::assertEquals('http://mydomain.org/index.php/new-context1/current-context2#some%3Fanchor', $result);

		$result = $this->router->url($this->request, 'new-context1', null, 'new-op', 'add-path', array('key' => 'val'), 'some-anchor');
		self::assertEquals('http://mydomain.org/index.php/new-context1/current-context2/index/new-op/add-path?key=val#some-anchor', $result);

		$result = $this->router->url($this->request, 'new-context1', null, null, null, array('key1' => 'val1', 'key2' => 'val2'), null, true);
		self::assertEquals('http://mydomain.org/index.php/new-context1/current-context2?key1=val1&amp;key2=val2', $result);
	}

	/**
	 * @covers PKPPageRouter::url
	 */
	public function testUrlWithPathinfoAndOverriddenBaseUrl() {
		$this->setTestConfiguration('request1', 'classes/core/config'); // contains overridden context

		// Set up a request with an overridden context
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_ENABLED);
		$_SERVER = array(
			'SERVER_NAME' => 'mydomain.org',
			'SCRIPT_NAME' => '/index.php',
			'PATH_INFO' => '/overridden-context/current-context2/current-page/current-op'
		);
		$this->_setUpMockDAOs('overridden-context');
		$result = $this->router->url($this->request);
		self::assertEquals('http://some-domain/xyz-context/current-context2/current-page/current-op', $result);
	}

	/**
	 * @covers PKPPageRouter::url
	 */
	public function testUrlWithPathinfoAndOverriddenNewContext() {
		$this->setTestConfiguration('request1', 'classes/core/config'); // contains overridden context

		// Same set-up as in testUrlWithPathinfoAndOverriddenBaseUrl()
		// but this time use a request with non-overridden context and
		// 'overridden-context' as new context. (Reproduces #5118)
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_ENABLED);
		$_SERVER = array(
			'SERVER_NAME' => 'mydomain.org',
			'SCRIPT_NAME' => '/index.php',
			'PATH_INFO' => '/current-context1/current-context2/current-page/current-op'
		);
		$this->_setUpMockDAOs('current-context1', 'current-context2', true);
		$result = $this->router->url($this->request, 'overridden-context', 'new-page');
		self::assertEquals('http://some-domain/xyz-context/current-context2/new-page', $result);
	}

	/**
	 * @covers PKPPageRouter::url
	 */
	public function testUrlWithPathinfoAndSecondContextObjectIsNull() {
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_ENABLED);
		$_SERVER = array(
			'SERVER_NAME' => 'mydomain.org',
			'SCRIPT_NAME' => '/index.php',
			'PATH_INFO' => '/current-context1/current-context2/current-page/current-op'
		);

		// Simulate context DAOs
		$this->_setUpMockDAOs('current-context1', 'current-context2', false, true);

		$result = $this->router->url($this->request);
		self::assertEquals('http://mydomain.org/index.php/current-context1/index/current-page/current-op', $result);
	}

	/**
	 * @covers PKPPageRouter::url
	 */
	public function testUrlWithoutPathinfo() {
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_DISABLED);
		$_SERVER = array(
			'SERVER_NAME' => 'mydomain.org',
			'SCRIPT_NAME' => '/index.php',
		);
		$_GET = array(
			'firstContext' => 'current-context1',
			'secondContext' => 'current-context2',
			'page' => 'current-page',
			'op' => 'current-op'
		);

		// Simulate context DAOs
		$this->_setUpMockDAOs();

		$result = $this->router->url($this->request);
		self::assertEquals('http://mydomain.org/index.php?firstContext=current-context1&secondContext=current-context2&page=current-page&op=current-op', $result);

		$result = $this->router->url($this->request, 'new-context1');
		self::assertEquals('http://mydomain.org/index.php?firstContext=new-context1&secondContext=current-context2', $result);

		$result = $this->router->url($this->request, array('new-context1', 'new-context2'));
		self::assertEquals('http://mydomain.org/index.php?firstContext=new-context1&secondContext=new-context2', $result);

		$result = $this->router->url($this->request, array(), 'new-page');
		self::assertEquals('http://mydomain.org/index.php?firstContext=current-context1&secondContext=current-context2&page=new-page', $result);

		$result = $this->router->url($this->request, array(), null, 'new-op');
		self::assertEquals('http://mydomain.org/index.php?firstContext=current-context1&secondContext=current-context2&page=current-page&op=new-op', $result);

		$result = $this->router->url($this->request, 'new-context1', 'new-page');
		self::assertEquals('http://mydomain.org/index.php?firstContext=new-context1&secondContext=current-context2&page=new-page', $result);

		$result = $this->router->url($this->request, 'new-context1', 'new-page', 'new-op');
		self::assertEquals('http://mydomain.org/index.php?firstContext=new-context1&secondContext=current-context2&page=new-page&op=new-op', $result);

		$result = $this->router->url($this->request, 'new-context1', null, 'new-op');
		self::assertEquals('http://mydomain.org/index.php?firstContext=new-context1&secondContext=current-context2&page=index&op=new-op', $result);

		$result = $this->router->url($this->request, 'new-context1', null, null, 'add?path');
		self::assertEquals('http://mydomain.org/index.php?firstContext=new-context1&secondContext=current-context2&page=index&op=index&path[]=add%3Fpath', $result);

		$result = $this->router->url($this->request, 'new-context1', null, null, array('add-path1', 'add?path2'));
		self::assertEquals('http://mydomain.org/index.php?firstContext=new-context1&secondContext=current-context2&page=index&op=index&path[]=add-path1&path[]=add%3Fpath2', $result);

		$result = $this->router->url($this->request, 'new-context1', null, null, null,
			array(
				'key1' => 'val1?',
				'key2' => array('val2-1', 'val2?2')
			)
		);
		self::assertEquals('http://mydomain.org/index.php?firstContext=new-context1&secondContext=current-context2&key1=val1%3F&key2[]=val2-1&key2[]=val2%3F2', $result);

		$result = $this->router->url($this->request, 'new-context1', null, null, null, null, 'some?anchor');
		self::assertEquals('http://mydomain.org/index.php?firstContext=new-context1&secondContext=current-context2#some%3Fanchor', $result);

		$result = $this->router->url($this->request, 'new-context1', null, 'new-op', 'add-path', array('key' => 'val'), 'some-anchor');
		self::assertEquals('http://mydomain.org/index.php?firstContext=new-context1&secondContext=current-context2&page=index&op=new-op&path[]=add-path&key=val#some-anchor', $result);

		$result = $this->router->url($this->request, 'new-context1', null, null, null, array('key1' => 'val1', 'key2' => 'val2'), null, true);
		self::assertEquals('http://mydomain.org/index.php?firstContext=new-context1&amp;secondContext=current-context2&amp;key1=val1&amp;key2=val2', $result);
	}

	/**
	 * @covers PKPPageRouter::url
	 */
	public function testUrlWithoutPathinfoAndOverriddenBaseUrl() {
		$this->setTestConfiguration('request2', 'classes/core/config'); // contains overridden context
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_DISABLED);
		$_SERVER = array(
			'SERVER_NAME' => 'mydomain.org',
			'SCRIPT_NAME' => '/index.php',
		);
		$_GET = array(
			'firstContext' => 'overridden-context',
			'secondContext' => 'current-context2',
			'page' => 'current-page',
			'op' => 'current-op'
		);

		// Simulate context DAOs
		$this->_setUpMockDAOs('overridden-context');

		// NB: This also tests whether unusual URL elements like user, password and port
		// will be handled correctly.
		$result = $this->router->url($this->request);
		self::assertEquals('http://some-user:some-pass@some-domain:8080/?firstContext=xyz-context&secondContext=current-context2&page=current-page&op=current-op', $result);
	}

	/**
	 * @covers PKPPageRouter::url
	 */
	public function testUrlWithoutPathinfoAndSecondContextObjectIsNull() {
		$this->setTestConfiguration('request2', 'classes/core/config'); // restful URLs enabled
		$mockApplication = $this->_setUpMockEnvironment(self::PATHINFO_DISABLED);
		$_SERVER = array(
			'SERVER_NAME' => 'mydomain.org',
			'SCRIPT_NAME' => '/index.php',
		);
		$_GET = array(
			'firstContext' => 'current-context1',
			'secondContext' => 'current-context2',
			'page' => 'current-page',
			'op' => 'current-op'
		);

		// Simulate context DAOs
		$this->_setUpMockDAOs('current-context1', 'current-context2', false, true);

		// NB: This also tests whether restful URLs work correctly.
		$result = $this->router->url($this->request);
		self::assertEquals('http://mydomain.org/?firstContext=current-context1&secondContext=index&page=current-page&op=current-op', $result);
	}
}
?>
