<?php

/**
 * @file tests/data/PKPCreateUsersTest.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPCreateUsersTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create test users
 */

import('lib.pkp.tests.WebTestCase');

class PKPCreateUsersTest extends WebTestCase {
	/**
	 * Create a user account.
	 * @param $data array
	 */
	protected function createUser($data) {
		// Come up with sensible defaults for data not supplied
		$username = $data['username'];
		$data = array_merge(array(
			'email' => $username . '@mailinator.com',
			'password' => $username . $username,
			'password2' => $username . $username,
			'roles' => array()
		), $data);

		$this->open(self::$baseUrl);
		$this->waitForElementPresent($selector='css=li.profile a:contains(\'Dashboard\')');
		$this->clickAndWait($selector);
		$this->waitForElementPresent($selector='link=Users & Roles');
		$this->click($selector);
		$this->waitForElementPresent($selector='css=[id^=component-grid-settings-user-usergrid-addUser-button-]');
		$this->click($selector);
		$this->waitForElementPresent('css=[id^=firstName-]');

		// Fill in user data
		$this->type('css=[id^=firstName-]', $data['firstName']);
		$this->type('css=[id^=lastName-]', $data['lastName']);
		$this->type('css=[id^=username-]', $username);
		$this->type('css=[id^=email-]', $data['email']);
		$this->type('css=[id^=password-]', $data['password']);
		$this->type('css=[id^=password2-]', $data['password2']);
		if (isset($data['country'])) $this->select('id=country', $data['country']);
		if (isset($data['affiliation'])) $this->type('css=[id^=affiliation-]', $data['affiliation']);
		$this->click('css=[id=mustChangePassword]'); // Uncheck the reset password requirement
		$this->click('//button[text()=\'OK\']');
		$this->waitJQuery();

		// Roles
		foreach ($data['roles'] as $role) {
			$this->waitForElementPresent('css=[id^=component-listbuilder-users-userusergrouplistbuilder-addItem-button-]');
			$this->clickAt('css=[id^=component-listbuilder-users-userusergrouplistbuilder-addItem-button-]', '10,10');
			$this->waitForElementPresent('//select[@name=\'newRowId[name]\']//option[text()=\'' . $role . '\']');
			$this->select('name=newRowId[name]', $role);
			$this->waitJQuery();
		}

		$this->click('//button[text()=\'Save\']');
		$this->waitForElementNotPresent('css=div.pkp_modal_panel');
	}
}
