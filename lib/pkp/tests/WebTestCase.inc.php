<?php

/**
 * @file lib/pkp/tests/WebTestCase.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WebTestCase
 * @ingroup tests
 *
 * @brief Base test class for Selenium functional tests.
 */

import('lib.pkp.tests.PKPTestHelper');

class WebTestCase extends PHPUnit_Extensions_SeleniumTestCase {
	/** @var string Base URL provided from environment */
	static protected $baseUrl;

	/** @var int Timeout limit for tests in seconds */
	static protected $timeout;

	protected $captureScreenshotOnFailure = true;
	protected $screenshotPath, $screenshotUrl;

	protected $coverageScriptPath = 'lib/pkp/lib/vendor/phpunit/phpunit-selenium/PHPUnit/Extensions/SeleniumCommon/phpunit_coverage.php';
	protected $coverageScriptUrl = '';

	/**
	 * Override this method if you want to backup/restore
	 * tables before/after the test.
	 * @return array|PKP_TEST_ENTIRE_DB A list of tables to backup and restore.
	 */
	protected function getAffectedTables() {
		return array();
	}

	/**
	 * @copydoc PHPUnit_Framework_TestCase::setUpBeforeClass()
	 */
	public static function setUpBeforeClass() {
		// Retrieve and check configuration.
		self::$baseUrl = getenv('BASEURL');
		self::$timeout = (int) getenv('TIMEOUT');
		if (!self::$timeout) self::$timeout = 60; // Default 60 seconds
		parent::setUpBeforeClass();
	}

	/**
	 * @copydoc PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		$screenshotsFolder = 'lib/pkp/tests/results';
		$this->screenshotPath = BASE_SYS_DIR . '/' . $screenshotsFolder;
		$this->screenshotUrl = getenv('BASEURL') . '/' . $screenshotsFolder;

		if (empty(self::$baseUrl)) {
			$this->markTestSkipped(
				'Please set BASEURL as an environment variable.'
			);
		}

		// Set the URL for the script that generates the selenium coverage reports
		$this->coverageScriptUrl = self::$baseUrl . '/' .  $this->coverageScriptPath;

		$this->setTimeout(self::$timeout);

		// See PKPTestCase::setUp() for an explanation
		// of this code.
		if(function_exists('_array_change_key_case')) {
			global $ADODB_INCLUDED_LIB;
			$ADODB_INCLUDED_LIB = 1;
		}

		// This is not Google Chrome but the Firefox Heightened
		// Privilege mode required e.g. for file upload.
		$this->setBrowser('*chrome');

		$this->setBrowserUrl(self::$baseUrl . '/');
		if (Config::getVar('general', 'installed')) {
			$affectedTables = $this->getAffectedTables();
			if (is_array($affectedTables)) {
				PKPTestHelper::backupTables($affectedTables, $this);
			}
		}

		$cacheManager = CacheManager::getManager();
		$cacheManager->flush(null, CACHE_TYPE_FILE);
		$cacheManager->flush(null, CACHE_TYPE_OBJECT);

		// Clear ADODB's cache
		if (Config::getVar('general', 'installed')) {
			$userDao = DAORegistry::getDAO('UserDAO'); // As good as any
			$userDao->flushCache();
		}

		parent::setUp();
	}

	/**
	 * @copydoc PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown() {
		parent::tearDown();
		if (Config::getVar('general', 'installed')) {
			$affectedTables = $this->getAffectedTables();
			if (is_array($affectedTables)) {
				PKPTestHelper::restoreTables($this->getAffectedTables(), $this);
			} elseif ($affectedTables === PKP_TEST_ENTIRE_DB) {
				PKPTestHelper::restoreDB($this);
			}
		}
	}

	/**
	 * Log in.
	 * @param $username string
	 * @param $password string Optional -- defaults to usernameusername
	 */
	protected function logIn($username, $password = null) {
		// Default to twice username (convention for test data)
		if ($password === null) $password = $username . $username;

		$this->open(self::$baseUrl);
		$this->waitForElementPresent($selector='link=Login');
		$this->clickAndWait($selector);
		$this->waitForElementPresent($selector='css=[id=username]');
		$this->type($selector, $username);
		$this->type('css=[id=password]', $password);
		$this->waitForElementPresent($selector='css=#login button.submit');
		$this->click($selector);
		$this->waitForElementPresent('link=Logout');
	}

	/**
	 * Self-register a new user account.
	 * @param $data array
	 */
	protected function register($data) {
		// Check that the required parameters are provided
		foreach (array(
			'username', 'firstName', 'lastName'
		) as $paramName) {
			$this->assertTrue(isset($data[$paramName]));
		}

		$username = $data['username'];
		$data = array_merge(array(
			'email' => $username . '@mailinator.com',
			'password' => $username . $username,
			'password2' => $username . $username,
			'roles' => array()
		), $data);

		// Find registration page
		$this->open(self::$baseUrl);
		$this->waitForElementPresent($selector='link=Register');
		$this->click($selector);

		// Fill in user data
		$this->waitForElementPresent('css=[id=firstName]');
		$this->type('css=[id=firstName]', $data['firstName']);
		$this->type('css=[id=lastName]', $data['lastName']);
		$this->type('css=[id=username]', $username);
		$this->type('css=[id=email]', $data['email']);
		$this->type('css=[id=password]', $data['password']);
		$this->type('css=[id=password2]', $data['password2']);
		if (isset($data['affiliation'])) $this->type('css=[id=affiliation]', $data['affiliation']);
		if (isset($data['country'])) $this->select('id=country', $data['country']);

		// Select the specified roles
		foreach ($data['roles'] as $role) {
			$this->click('//label[contains(., \'' . htmlspecialchars($role) . '\')]');
		}

		// Save the new user
		$this->waitForElementPresent($formButtonSelector = '//button[contains(.,\'Register\')]');
		$this->click($formButtonSelector);
		$this->waitForElementPresent('link=Logout');
		$this->waitJQuery();

		if (in_array('Author', $data['roles'])) {
			$this->waitForElementPresent('//h4[contains(.,\'My Authored\')]');
		}
	}

	/**
	 * Log out.
	 */
	protected function logOut() {
		$this->open(self::$baseUrl);
		$this->waitForElementPresent('link=Logout');
		$this->waitJQuery();
		$this->click('link=Logout');
		$this->waitForElementPresent('link=Login');
		$this->waitJQuery();
	}

	/**
	 * Check for verification errors and
	 * clean the verification error list.
	 */
	protected function verified() {
		if (!$verified = empty($this->verificationErrors)) {
			$this->verificationErrors = array();
		}
		return $verified;
	}

	/**
	 * Open a URL but only if it's not already
	 * the current location.
	 * @param $url string
	 */
	protected function verifyAndOpen($url) {
		$this->verifyLocation('exact:' . $url);
		if (!$this->verified()) {
			$this->open($url);
		}
		$this->waitForLocation($url);
	}

	/**
	 * Types a text into an input field.
	 *
	 * This is done using low-level methods in a way
	 * to simulate actual key-press events that can
	 * trigger autocomplete events or similar.
	 *
	 * @param $box string the locator of the box
	 * @param $letters string the text to type
	 */
	protected function typeText($box, $letters) {
		$this->focus($box);
		$currentContent = '';
		foreach(str_split($letters) as $letter) {
			// The following hack makes jQueryUI behave as
			// if typing in letters manually.
			$currentContent .= $letter;
			$this->type($box, $currentContent);
			$this->typeKeys($box, $letter);
			usleep(300000);
		}
		// Fix one more timing problem on the test server:
		sleep(1);
	}

	/**
	 * Make the exception message more informative.
	 * @param $e Exception
	 * @param $testObject string
	 * @return Exception
	 */
	protected function improveException($e, $testObject) {
		$improvedMessage = "Error while testing $testObject: ".$e->getMessage();
		if (is_a($e, 'PHPUnit_Framework_ExpectationFailedException')) {
			$e = new PHPUnit_Framework_ExpectationFailedException($improvedMessage, $e->getComparisonFailure());
		} elseif (is_a($e, 'PHPUnit_Framework_Exception')) {
			$e = new PHPUnit_Framework_Exception($improvedMessage, $e->getCode());
		}
		return $e;
	}

	/**
	 * Save an Ajax form, waiting for the loading sprite
	 * to be hidden to continue the test execution.
	 * @param $formLocator String
	 */
	protected function submitAjaxForm($formId) {
		$this->assertElementPresent($formId, 'The passed form locator do not point to any form element at the current page.');
		$this->click('css=#' . $formId . ' #submitFormButton');

		$progressIndicatorSelector = '#' . $formId . ' .formButtons .pkp_spinner';

		// First make sure that the progress indicator is visible.
		$this->waitForCondition("selenium.browserbot.getUserWindow().jQuery('$progressIndicatorSelector:visible').length == 1", 2000);

		// Wait until it disappears (the form submit process is finished).
		$this->waitForCondition("selenium.browserbot.getUserWindow().jQuery('$progressIndicatorSelector:visible').length == 0");
	}

	/**
	 * Upload a file using plupload interface.
	 * @param $file string Path to the file relative to the
	 * OmpWebTestCase class file location.
	 */
	protected function uploadFile($file) {
		$this->assertTrue(file_exists($file), 'Test file does not exist.');
		$testFile = realpath($file);
		$fileName = basename($testFile);

		$this->waitForElementPresent('//input[@type="file"]');
		$this->type('css=input[type="file"]', $testFile);
		$this->waitForElementPresent('css=span.pkpUploaderFilename');
		//$this->waitForElementPresent('css=div.ui-icon-circle-check');
	}

	/**
	 * Download the passed file.
	 * @param $filename string
	 */
	protected function downloadFile($filename) {
		$fileXPath = $this->getEscapedXPathForLink($filename);
		$this->waitForElementPresent($fileXPath);
		$this->click($fileXPath);
		$this->waitJQuery();
		$this->assertAlertNotPresent(); // An authentication failure will lead to a js alert.
		$downloadLinkId = $this->getAttribute($fileXPath . '/@id');
		$this->waitForCondition("window.jQuery('#" . htmlspecialchars($downloadLinkId) . "').hasClass('ui-state-disabled') == false");
	}

	/**
	 * Log in as author user.
	 */
	protected function logAuthorIn() {
		$authorUser = 'kalkhafaji';
		$authorPw = 'kalkhafajikalkhafaji';
		$this->logIn($authorUser, $authorPw);
	}

	/**
	 * Type a value into a TinyMCE control.
	 * @param $controlPrefix string Prefix of control name
	 * @param $value string Value to enter into control
	 */
	protected function typeTinyMCE($controlPrefix, $value) {
		sleep(2); // Give TinyMCE a chance to load/init
		$this->runScript("tinyMCE.get($('textarea[id^=\\'" . htmlspecialchars($controlPrefix) . "\\']').attr('id')).setContent('" . htmlspecialchars($value, ENT_QUOTES) . "');");
	}

	/**
	 * Add a tag to a TagIt-enabled control
	 * @param $controlPrefix string Prefix of control name
	 * @param $value string Value of new tag
	 */
	protected function addTag($controlPrefix, $value) {
		$this->runScript('$(\'[id^=\\\'' . htmlspecialchars($controlPrefix) . '\\\']\').tagit(\'createTag\', \'' . htmlspecialchars($value) . '\');');
	}

	/**
	 * Click a link action with the specified name.
	 * @param $name string Name of link action.
	 * @param $waitFirst boolean True (default) to wait for the element first.
	 */
	protected function clickLinkActionNamed($name, $waitFirst = true) {
		$selector = '//button[text()=\'' . $this->escapeJS($name) . '\']';
		$this->waitForElementPresent($selector);
		$this->click($selector);
	}

	/**
	 * Wait for active JQuery requests to complete.
	 */
	protected function waitJQuery() {
		$this->waitForCondition('window.jQuery.active == 0');
	}

	/**
	 * Escape a string for inclusion in JS, typically as part of a selector.
	 * WARNING: This is probably not safe for use outside the test suite.
	 * @param $value string The value to escape.
	 * @return string Escaped string.
	 */
	protected function escapeJS($value) {
		return str_replace('\'', '\\\'', $value);
	}

	/**
	 * Scroll a grid down until it loads all elements.
	 * @param $gridContainerId string The grid container id.
	 */
	protected function scrollGridDown($gridContainerId) {
		$this->waitForElementPresent('css=#' . $gridContainerId . ' .scrollable');
		$loadedItems = 0;
		$totalItems = 1; // Just to start.
		while($loadedItems < $totalItems) {
			$this->runScript('$(\'.scrollable\', \'#' . $gridContainerId . '\').find(\'tr:visible\').last()[0].scrollIntoView()');
			$this->waitJQuery();
			$this->waitForElementPresent($selector='css=#' . $gridContainerId . ' .gridPagingScrolling');
			$pagingInfo = $this->getText($selector);
			if (!$pagingInfo) break;

			$pagingInfo = explode(' ', $pagingInfo);
			$loadedItems = $pagingInfo[1];
			$totalItems = $pagingInfo[3];
		}
	}

	/**
	 * Scroll page down until the end.
	 */
	protected function scrollPageDown() {
		$this->waitJQuery();
		$this->runScript('scroll(0, document.body.scrollHeight()');
	}
}
?>
