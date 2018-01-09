<?php

/**
 * @file tests/data/50-SectionsTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SectionsTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create/configure sections
 */

import('lib.pkp.tests.WebTestCase');

class SectionsTest extends WebTestCase {
	/**
	 * Configure section editors
	 */
	function testConfigureSections() {
		$this->open(self::$baseUrl);

		// Management > Settings > Journal
		$this->clickAndWait('link=User Home');
		$this->clickAndWait('link=Journal Manager');
		$this->clickAndWait('link=Journal Sections');

		// Edit Section (default "Articles")
		$this->clickAndWait('link=Edit');

		// Add Section Editors (David Buskins)
		$this->clickAndWait('//td[contains(text(),\'David Buskins\')]/..//a[text()=\'Add\']');
		$this->click('//td[contains(text(),\'David Buskins\')]/..//input[contains(@name, \'canReview\')]');
		$this->click('//td[contains(text(),\'David Buskins\')]/..//input[contains(@name, \'canEdit\')]');

		// Add Section Editor (Stephanie Berardo)
		$this->clickAndWait('//td[contains(text(),\'Stephanie Berardo\')]/..//a[text()=\'Add\']');
		$this->click('//td[contains(text(),\'Stephanie Berardo\')]/..//input[contains(@name, \'canReview\')]');
		$this->click('//td[contains(text(),\'Stephanie Berardo\')]/..//input[contains(@name, \'canEdit\')]');

		// Save
		$this->clickAndWait('css=input.button.defaultButton');

		// Create a new "Reviews" section
		$this->clickAndWait('link=Create Section');
		$this->type('id=title', 'Reviews');
		$this->type('id=abbrev', 'REV');
		$this->type('id=identifyType', 'Review Article');
		$this->click('id=abstractsNotRequired');

		// Add a Section Editor (Minoti Inoue)
		$this->clickAndWait('//td[contains(text(),\'Minoti Inoue\')]/..//a[text()=\'Add\']');
		$this->click('//td[contains(text(),\'Minoti Inoue\')]/..//input[contains(@name, \'canReview\')]');
		$this->click('//td[contains(text(),\'Minoti Inoue\')]/..//input[contains(@name, \'canEdit\')]');

		// Save
		$this->clickAndWait('css=input.button.defaultButton');
	}
}
