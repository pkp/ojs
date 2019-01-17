<?php

/**
 * @file tests/data/ContentBaseTestCase.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContentBaseTestCase
 * @ingroup tests_data
 *
 * @brief Data build suite: Base class for content creation tests
 */

import('lib.pkp.tests.data.PKPContentBaseTestCase');

class ContentBaseTestCase extends PKPContentBaseTestCase {
	/**
	 * Handle any section information on submission step 1
	 * @return string
	 */
	protected function _handleSection($data) {
		$section = 'Articles'; // Default
		if (isset($data['section'])) $section = $data['section'];

		// Page 1
		$this->waitForElementPresent('id=sectionId');
		$this->select('id=sectionId', 'label=' . $this->escapeJS($section));
	}

	/**
	 * Get the number of items in the default submission checklist
	 * @return int
	 */
	protected function _getChecklistLength() {
		return 6;
	}

	/**
	 * Assign a copyeditor by name.
	 * @param $name string Needs to be in the form "lastname, firstname"
	 */
	protected function assignCopyeditor($name) {
		$this->clickAndWait('link=Editing');
		$this->clickAndWait('link=Assign Copyeditor');
		$this->clickAndWait('//td/a[contains(text(),\''. $this->escapeJS($name) . '\')]/../..//a[text()=\'Assign\']');
	}

	/**
	 * Assign a layout editor by name.
	 * @param $name string Needs to be in the form "lastname, firstname"
	 */
	protected function assignLayoutEditor($name) {
		$this->clickAndWait('link=Editing');
		$this->clickAndWait('link=Assign Layout Editor');
		$this->clickAndWait('//td/a[contains(text(),\''. $this->escapeJS($name) . '\')]/../..//a[text()=\'Assign\']');
	}

	/**
	 * Assign a layout editor by name.
	 * @param $name string Needs to be in the form "lastname, firstname"
	 */
	protected function assignProofreader($name) {
		$this->clickAndWait('link=Editing');
		$this->clickAndWait('link=Assign Proofreader');
		$this->clickAndWait('//td/a[contains(text(),\''. $this->escapeJS($name) . '\')]/../..//a[text()=\'Assign\']');
	}
}
