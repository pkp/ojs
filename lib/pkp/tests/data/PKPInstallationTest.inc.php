<?php

/**
 * @file tests/data/PKPInstallationTest.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPInstallationTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Install the system.
 */

import('lib.pkp.tests.WebTestCase');

abstract class PKPInstallationTest extends WebTestCase {
	/**
	 * Get a piece of text by which to recognize the installation form.
	 * @return string
	 */
	abstract protected function _getInstallerText();

	/**
	 * Install the application. Requires configuration items to be specified
	 * in environment variables -- see getenv(...) calls below.
	 */
	function testInstallation() {
		$this->open(self::$baseUrl);
		$this->assertTextPresent($this->_getInstallerText());

		// Administrator
		$this->waitForElementPresent('css=[id^=adminUsername-]');
		$this->type('css=[id^=adminUsername-]', 'admin');
		$this->type('css=[id^=adminPassword-]', 'admin');
		$this->type('css=[id^=adminPassword2-]', 'admin');
		$this->type('css=[id^=adminEmail-]', 'pkpadmin@mailinator.com');

		// Database
		$this->select('id=databaseDriver', 'label=' . getenv('DBTYPE'));
		$this->type('css=[id^=databaseHost-]', getenv('DBHOST'));
		$this->type('css=[id^=databasePassword-]', getenv('DBPASSWORD'));
		$this->type('css=[id^=databaseUsername-]', getenv('DBUSERNAME'));
		$this->type('css=[id^=databaseName-]', getenv('DBNAME'));
		$this->click('id=createDatabase');

		// Locale
		$this->click('id=additionalLocales-en_US');
		$this->click('id=additionalLocales-fr_CA');
		$this->select('id=connectionCharset', 'label=Unicode (UTF-8)');
		$this->select('id=databaseCharset', 'label=Unicode (UTF-8)');

		// Files
		$this->type('css=[id^=filesDir-]', getenv('FILESDIR'));

		// Execute
		$this->clickAndWait('css=[id^=submitFormButton-]');
		$this->waitForTextPresent('has completed successfully.');
		$this->waitJQuery();
	}
}

?>
