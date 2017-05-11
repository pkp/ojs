<?php

/**
 * @file tests/functional/StaticPagesFunctionalTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StaticPagesFunctionalTest
 * @package plugins.generic.staticPages
 *
 * @brief Functional tests for the static pages plugin.
 */

import('lib.pkp.tests.WebTestCase');

class StaticPagesFunctionalTest extends WebTestCase {
	/**
	 * @copydoc WebTestCase::getAffectedTables
	 */
	protected function getAffectedTables() {
		return PKP_TEST_ENTIRE_DB;
	}

	/**
	 * Enable the plugin
	 */
	function testStaticPages() {
		$this->open(self::$baseUrl);

		$this->logIn('admin', 'admin');
		$this->waitForElementPresent($selector='link=Website');
		$this->clickAndWait($selector);
		$this->click('link=Plugins');

		// Find and enable the plugin
		$this->waitForElementPresent($selector = '//input[starts-with(@id, \'select-cell-staticpagesplugin-enabled\')]');
		$this->assertElementNotPresent('link=Static Pages'); // Plugin should be disabled
		$this->click($selector); // Enable plugin
		$this->waitForElementPresent('//div[contains(.,\'The plugin "Static Pages Plugin" has been enabled.\')]');

		// Check for a 404 on the page we are about to create
		$this->open(self::$baseUrl . '/index.php/publicknowledge/flarm');
		$this->assertText('css=h1', '404 Not Found');

		// Find the plugin's tab
		$this->open(self::$baseUrl);
		$this->waitForElementPresent($selector='css=li.profile a:contains(\'Dashboard\')');
		$this->clickAndWait($selector);
		$this->waitForElementPresent($selector='link=Website');
		$this->clickAndWait($selector);
		$this->waitForElementPresent($selector = 'link=Static Pages');
		$this->click($selector);

		// Create a static page
		$this->waitForElementPresent($selector = '//a[starts-with(@id, \'component-plugins-generic-staticpages-controllers-grid-staticpagegrid-addStaticPage-button-\')]');
		$this->click($selector);
		$this->waitForElementPresent($selector='//input[starts-with(@id, \'path-\')]');
		$this->type($selector, 'flarm');
		$this->type($selector='//input[starts-with(@id, \'title-\')]', 'Test Static Page');
		$this->typeTinyMCE('content', 'Here is my new static page.');
		$this->waitForElementPresent($selector = '//button[starts-with(@id, \'submitFormButton-\')]');
		$this->click($selector);

		// View the static page
		$this->waitForElementPresent($selector='//a[text()=\'flarm\']');
		$this->click($selector);
		$this->waitForPopUp('staticPage', 10000);
		$this->selectWindow('name=staticPage');
		$this->waitForElementPresent('//h2[contains(text(),\'Test Static Page\')]');
		$this->waitForElementPresent('//p[contains(text(),\'Here is my new static page.\')]');
		$this->close();
		$this->selectWindow(null);

		$this->logOut();
	}
}
