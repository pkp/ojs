<?php

/**
 * @file tests/classes/core/DispatcherTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DispatcherTest
 * @ingroup tests_classes_core
 * @see Dispatcher
 *
 * @brief Tests for the Dispatcher class.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.core.Registry');
import('classes.core.Application');
import('lib.pkp.classes.core.Dispatcher');
import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.classes.plugins.HookRegistry');

class DispatcherTest extends PKPTestCase {
	const
		PATHINFO_ENABLED = true,
		PATHINFO_DISABLED = false;

	private
		$dispatcher,
		$request;

	/**
	 * @see PKPTestCase::getMockedRegistryKeys()
	 */
	protected function getMockedRegistryKeys() {
		return array('application', 'dispatcher');
	}

	/**
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		// Mock application object without calling its constructor.
		$mockApplication =
				$this->getMock('Application', array('getContextDepth', 'getContextList'),
				array(), '', false);
		Registry::set('application', $mockApplication);
		$nullVar = null;
		Registry::set('dispatcher', $nullVar);

		// Set up the getContextDepth() method
		$mockApplication->expects($this->any())
		                ->method('getContextDepth')
		                ->will($this->returnValue(2));

		// Set up the getContextList() method
		$mockApplication->expects($this->any())
		                ->method('getContextList')
		                ->will($this->returnValue(array('firstContext', 'secondContext')));

		$this->dispatcher = $mockApplication->getDispatcher(); // this also adds the component router
		$this->dispatcher->addRouterName('lib.pkp.classes.core.PKPPageRouter', 'page');

		$this->request = new PKPRequest();
	}

	/**
	 * @covers Dispatcher::url
	 */
	public function testUrl() {
		$baseUrl = $this->request->getBaseUrl();

		$url = $this->dispatcher->url($this->request, ROUTE_PAGE, array('context1', 'context2'), 'somepage', 'someop');
		self::assertEquals($baseUrl.'/index.php/context1/context2/somepage/someop', $url);

		$url = $this->dispatcher->url($this->request, ROUTE_COMPONENT, array('context1', 'context2'), 'some.ComponentHandler', 'someOp');
		self::assertEquals($baseUrl.'/index.php/context1/context2/$$$call$$$/some/component/some-op', $url);
	}
}

?>
