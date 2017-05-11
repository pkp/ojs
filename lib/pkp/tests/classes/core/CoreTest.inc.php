<?php

/**
 * @file tests/classes/core/CoreTest.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CoreTest
 * @ingroup tests_classes_core
 * @see Core
 *
 * @brief Tests for the Core class.
 */

import('lib.pkp.tests.PKPTestCase');

class CoreTest extends PKPTestCase {

	/**
	 * Test Core::removeBaseUrl method using the default
	 * base url config setting.
	 * @param $baseUrl string
	 * @param $url string
	 * @param $expectUrl string
	 * @covers removeBaseUrl
	 * @dataProvider testRemoveBaseUrlDataProvider
	 */
	public function testRemoveBaseUrl($baseUrl, $url, $expectUrl) {
		$configData =& Config::getData();
		$configData['general']['base_url'] = $baseUrl;

		$actualUrl = Core::removeBaseUrl($url);
		$this->assertEquals($expectUrl, $actualUrl);
	}

	/**
	 * Test Core::removeBaseUrl method using the base_url[...]
	 * override config settings.
	 * @param $contextPath string
	 * @param $baseUrl string
	 * @param $url string
	 * @param $expectUrl string
	 * @covers removeBaseUrl
	 * @dataProvider testRemoveBaseUrlOverrideDataProvider
	 */
	public function testRemoveBaseUrlOverride($contextPath, $baseUrl, $url, $expectUrl) {
		$configData =& Config::getData();
		$configData['general']['base_url[' . $contextPath . ']'] = $baseUrl;
		$configData['general']['base_url[test2]'] = $baseUrl . '/test';

		$actualUrl = Core::removeBaseUrl($url);
		$this->assertEquals($expectUrl, $actualUrl);
	}

	/**
	 * Return cases data for testRemoveBaseUrl test.
	 * @return array
	 */
	public function testRemoveBaseUrlDataProvider() {
		$cases = array();

		// Without host.
		$cases[] = array('http://localhost/ojs', '/', '');
		$cases[] = array('http://localhost/ojs', '/index.php', '');
		$cases[] = array('http://localhost/ojs', '/ojs', '');
		$cases[] = array('http://localhost/ojs', '/ojs/index.php/ojs/index', '/ojs/index');
		$cases[] = array('http://localhost/ojs', '/ojs/index.php/ojstest/index', '/ojstest/index');
		// Without host and rewrite rules removing index.php.
		$cases[] = array('http://localhost/ojs', '/ojs/ojstest/index', '/ojstest/index');

		// With host.
		$cases[] = array('http://localhost/ojs', 'http://localhost/ojs/', '');
		$cases[] = array('http://localhost/ojs', 'http://localhost/ojs/index.php', '');
		$cases[] = array('http://localhost/ojs', 'http://localhost/ojs/index.php/ojstest/index', '/ojstest/index');
		$cases[] = array('http://localhost/ojs', 'http://localhost/ojs/index.php/ojstest/index/index/path?arg1=arg&arg2=arg', '/ojstest/index/index/path?arg1=arg&arg2=arg');
		// With host and rewrite rules removing index.php.
		$cases[] = array('http://localhost/ojs', 'http://localhost/ojs/ojstest/index', '/ojstest/index');

		// Path info disabled.
		$cases[] = array('http://localhost/ojs', 'http://localhost/ojs/index.php?journal=test', '?journal=test');
		$cases[] = array('http://localhost/ojs', 'http://localhost/ojs', '');
		$cases[] = array('http://localhost/ojs', 'http://localhost/ojs/index.php', '');
		$cases[] = array('http://localhost/ojs', 'http://localhost/ojs/index.php?', '?');
		// Path info disabled and rewrite rules removing index.php.
		$cases[] = array('http://localhost/ojs', 'http://localhost/ojs?journal=test', '?journal=test');

		// Path info disabled without host.
		$cases[] = array('http://localhost/ojs', '/ojs/index.php?journal=test', '?journal=test');
		$cases[] = array('http://localhost/ojs', '/ojs', '');
		$cases[] = array('http://localhost/ojs', '/ojs/index.php', '');
		$cases[] = array('http://localhost/ojs', '/ojs/index.php?', '?');
		// Path info disabled without host and rewrite rules removing index.php.
		$cases[] = array('http://localhost/ojs', '/ojs?journal=test', '?journal=test');

		return $cases;
	}

	/**
	 * Return cases data for testRemoveBaseUrl test.
	 * @return array
	 */
	public function testRemoveBaseUrlOverrideDataProvider() {
		$cases = array();

		// Url without context or any other url component.
		$cases[] = array('test', 'http://localhost', '/', '/test');
		$cases[] = array('test', 'http://localhost', '/?', '/test/?');

		// Url without context or any other path.
		$cases[] = array('test', 'http://localhost', 'http://localhost', '/test');
		$cases[] = array('test', 'http://localhost', 'http://localhost/?', '/test/?');

		// Url without context removed by rewrite rules.
		$cases[] = array('test', 'http://localhost/ojs', '/ojs/index', '/test/index');
		// Same as above but with index.php.
		$cases[] = array('test', 'http://localhost/ojs', '/ojs/index.php/index', '/test/index');

		// Impossible to know which base url forms the url.
		$cases[] = array('test', 'http://localhost/ojstest', '/ojstest/test/index', false);
		// Same as above, but possible to know because of the 'index.php' presence.
		$cases[] = array('test', 'http://localhost/ojstest', '/ojstest/index.php/test/index', '/test/index');

		// Overlaping contexts.
		$cases[] = array('test1', 'http://localhost', '/test/index', '/test2/index');
		$cases[] = array('test1', 'http://localhost', '/test/index', '/test2/index');
		// Overlaping contexts, path info disabled.
		$cases[] = array('test1', 'http://localhost', '/test/index.php?journal=test2&page=index', '/test2?journal=test2&page=index');
		// Overlaping contexts, path info disabled, rewrite rules removing index.php.
		$cases[] = array('test1', 'http://localhost', '/test?journal=test2&page=index', '/test2?journal=test2&page=index');

		// Path info disabled, overwrite rules removing index.php
		$cases[] = array('test', 'http://localhost/ojstest', '/ojstest?journal=test&page=index', '/test?journal=test&page=index');
		// Path info disabled only.
		$cases[] = array('test', 'http://localhost/ojstest', '/ojstest/index.php?journal=test&page=index', '/test?journal=test&page=index');

		return $cases;
	}
}
?>
