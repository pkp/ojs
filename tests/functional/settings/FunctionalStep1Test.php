<?php

/**
 * @file tests/functional/settings/FunctionalStep1Test.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalStep1Test
 * @ingroup tests_functional_settings
 *
 * @brief Test settings step 1 (details).
 */


import('lib.pkp.tests.WebTestCase');

class FunctionalStep1Test extends WebTestCase {
	public function testDoiPrefixAndDefaultSuffixPattern() {
		$this->logIn();

		// Change the prefix and assert that the DOI in the article changes.
		$this->checkArticleDoi('10.1234/t.v1i1.1');
		$this->configureDoi('10.4321');
		$this->checkArticleDoi('10.4321/t.v1i1.1');

		// Reset configuration.
		$this->configureDoi();
	}

	public function testDoiCustomSuffixPattern() {
		$this->logIn();

		// Change the suffix generation method.
		$this->checkArticleDoi('10.1234/t.v1i1.1');
		$this->configureDoi('10.1234', 'doiSuffix', '%j-vol%v-iss%i-art%a');
		$this->checkArticleDoi('10.1234/t-vol1-iss1-art1');

		// Reset configuration.
		$this->configureDoi();
	}

	public function testDoiSuffixIsCustomUrl() {
		$this->logIn();

		// Change the suffix generation method.
		$this->checkArticleDoi('10.1234/t.v1i1.1');
		$this->configureDoi('10.1234', 'doiSuffixCustomIdentifier');
		$this->checkArticleDoi('10.1234/doi_test_url');

		// Reset configuration.
		$this->configureDoi();
	}

	public function testDoiWillNotChangeWithoutReset() {
		$this->logIn();

		// Check the default.
		$this->checkArticleDoi('10.1234/t.v1i1.1');

		// Change and save a DOI setting.
		$this->open($this->baseUrl.'/index.php/test/manager/setup/1');
		$this->type('id=doiPrefix', '10.4321');
		$this->clickAndWait('css=input.button.defaultButton');

		// Check that the DOI didn't change.
		$this->checkArticleDoi('10.1234/t.v1i1.1');

		// Check that only reassigning the DOIs actually changes the DOI.
		$this->open($this->baseUrl.'/index.php/test/manager/setup/1');
		$this->clickAndWait('name=reassignDOIs');
		$this->checkArticleDoi('10.4321/t.v1i1.1');

		// Reset configuration.
		$this->configureDoi();
	}


	/**
	 * @see WebTestCase::logIn()
	 */
	protected function logIn() {
		parent::logIn();

		// Open the settings page and make sure that the DOI settings are the default settings.
		$this->configureDoi('10.1234', 'doiSuffixDefault');
	}

	/**
	 * Configures the DOI prefix and suffix generation method
	 * and resets all DOIs so that the new rules will be applied.
	 * @param $prefix string
	 * @param $suffixGenerationMethod string
	 */
	private function configureDoi($prefix='10.1234',
			$suffixGenerationMethod='doiSuffixDefault', $pattern = '') {
		// Make sure we're on the settings page.
		$this->verifyLocation('exact:'.$this->baseUrl.'/index.php/test/manager/setup/1');
		if ($this->verificationErrors) {
			$this->verificationErrors = array();
			$this->open($this->baseUrl.'/index.php/test/manager/setup/1');
		}

		// Check whether we have to change anything at all.
		$this->verifyValue('id=doiPrefix', 'exact:'.$prefix);
		$this->verifyValue('id='.$suffixGenerationMethod, 'exact:on');
		if ($this->verificationErrors) {
			$this->verificationErrors = array();

			// Change the settings.
			$this->type('id=doiPrefix', $prefix);
			$this->click('id='.$suffixGenerationMethod);
			$this->type('id=doiSuffixPattern', $pattern);
			$this->clickAndWait('css=input.button.defaultButton');

			// Reassign DOIs.
			$this->open($this->baseUrl.'/index.php/test/manager/setup/1');
			$this->clickAndWait('name=reassignDOIs');
		}
	}

	/**
	 * Check whether the given DOI appears on the article page.
	 * @param $articleDoi string
	 */
	private function checkArticleDoi($articleDoi) {
		$this->open($this->baseUrl.'/index.php/test/article/view/1');
		$this->assertAttribute('//meta[@name="DC.Identifier.DOI"]@content', 'exact:'.$articleDoi);
	}
}
?>