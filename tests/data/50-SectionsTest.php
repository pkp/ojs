<?php

/**
 * @file tests/data/50-SectionsTest.inc.php
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
		$this->waitForElementPresent($selector='css=li.profile a:contains(\'Dashboard\')');
		$this->clickAndWait($selector);

		// Section settings
		$this->waitForElementPresent($selector='link=Journal');
		$this->click($selector);
		$this->waitForElementPresent($selector='link=Sections');
		$this->click($selector);

		// Edit Section (default "Articles")
		$this->waitForElementPresent($selector='css=[id^=component-grid-settings-sections-sectiongrid-row-1-editSection-button-]');
		$this->click($selector);

		// Add Section Editors (David Buskins and Stephanie Berardo)
		$this->waitForElementPresent($selector='css=.pkpListPanelItem__item:contains(\'David Buskins\')');
		$this->clickAt($selector);
		$this->waitForElementPresent($selector='css=.pkpListPanelItem__item:contains(\'Stephanie Berardo\')');
		$this->clickAt($selector);

		// Save changes
		$this->click('//form[@id=\'sectionForm\']//button[text()=\'Save\']');
		$this->waitForElementNotPresent('css=div.pkp_modal_panel');

		// Verify resulting grid row
		$this->assertEquals('Stephanie Berardo, David Buskins', $this->getText('css=#cell-1-editors > span'));
		$this->waitForElementNotPresent('css=div.pkp_modal_panel');

		// Create a new "Reviews" section
		$this->click('css=[id^=component-grid-settings-sections-sectiongrid-addSection-button-]');
		$this->waitForElementPresent($selector='css=[id^=title-]');
		$this->type($selector, 'Reviews');
		$this->type('css=[id^=abbrev-]', 'REV');
		$this->type('css=[id^=identifyType-]', 'Review Article');
		$this->click('id=abstractsNotRequired');

		// Add a Section Editor (Minoti Inoue)
		$this->waitForElementPresent($selector='css=.pkpListPanelItem__item:contains(\'Minoti Inoue\')');
		$this->clickAt($selector);
		$this->click('//form[@id=\'sectionForm\']//button[text()=\'Save\']');
		$this->waitForElementNotPresent('css=div.pkp_modal_panel');
	}
}
