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
	// XPaths
	private $setupPage,
		$articlePage,
		$metadataPage,
		$doiSection,
		$doiInputField,
		$metadataPage2,
		$formError;

	protected function setUp() {
		parent::setUp();

		// Set-up page.
		$this->setupPage = $this->baseUrl.'/index.php/test/manager/setup/1';

		// Article page.
		$this->articlePage = $this->baseUrl.'/index.php/test/article/view/1';

		// Meta-data editing page.
		$this->metadataPage = $this->baseUrl.'/index.php/test/editor/viewMetadata/1';
		$this->doiSection = '//div[@id="pub-id::doi"]';
		$this->doiInputField = $this->doiSection . '//input';

		// Meta-data editing page of a second article.
		$this->metadataPage2 = $this->baseUrl.'/index.php/test/editor/viewMetadata/2';
		$this->formError = '//ul[@class="pkp_form_error_list"]//a[@href="#doiSuffix"]';
	}

	public function testDoiPrefixAndDefaultSuffixPattern() {
		$this->logIn();

		// Change the prefix and assert that the DOI in the article changes.
		$this->configureDoi('10.4321');
		$expectedDoi = '10.4321/t.v1i1.1';
		$this->checkArticleDoi($expectedDoi);
		$this->checkMetadataDoi($editable = false, $expectedDoi);

		// Reset configuration.
		$this->configureDoi();
	}

	public function testDoiCustomSuffixPattern() {
		$this->logIn();

		// Change the suffix generation method.
		$this->configureDoi('10.1234', 'doiSuffix', '%j-vol%v-iss%i-art%a');
		$expectedDoi = '10.1234/t-vol1-iss1-art1';
		$this->checkArticleDoi($expectedDoi);
		$this->checkMetadataDoi($editable = false, $expectedDoi);

		// Reset configuration.
		$this->configureDoi();
	}

	public function testDoiSuffixIsPublisherId() {
		$this->logIn();

		// Change the suffix generation method.
		$this->configureDoi('10.1234', 'doiSuffixPublisherId');
		$expectedDoi = '10.1234/doi_test_url';
		$this->checkArticleDoi($expectedDoi);
		$this->checkMetadataDoi($editable = false, $expectedDoi);

		// Reset configuration.
		$this->configureDoi();
	}

	public function testDoiSuffixIsCustomId() {
		$this->logIn();

		// Change the suffix generation method.
		$this->configureDoi('10.1234', 'doiSuffixCustomIdentifier');

		// Now an input field should be present on the meta-data page.
		$this->checkMetadataDoi($editable = true);

		// Let's enter a custom DOI and check whether it's being generated
		// correctly.
		$this->type($this->doiInputField, 'custom_doi');
		$this->clickAndWait('css=input.button.defaultButton');
		$this->assertElementNotPresent($this->formError);

		$expectedDoi = '10.1234/custom_doi';
		$this->checkArticleDoi($expectedDoi);

		// As soon as the DOI has been generated the meta-data field
		// should no longer be editable.
		$this->checkMetadataDoi($editable = false, $expectedDoi);

		// We should get an error when trying to enter the same
		// suffix for another article.
		$this->open($this->metadataPage2);
		$this->type($this->doiInputField, 'custom_doi');
		$this->clickAndWait('css=input.button.defaultButton');
		$this->assertElementPresent($this->formError);

		// Reset configuration.
		$this->configureDoi();
	}

	public function testDoiWillNotChangeWithoutReset() {
		$this->logIn();

		// Change and save a DOI setting.
		$this->open($this->setupPage);
		$this->type('id=doiPrefix', '10.4321');
		$this->clickAndWait('css=input.button.defaultButton');

		// Check that the DOI didn't change.
		$this->checkArticleDoi('10.1234/t.v1i1.1');

		// Check that only reassigning the DOIs actually changes the DOI.
		$this->open($this->setupPage);
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
		$this->checkArticleDoi('10.1234/t.v1i1.1');
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
		$this->verifyLocation('exact:'.$this->setupPage);
		if (!$this->verified()) {
			$this->open($this->setupPage);
		}

		// Check whether we have to change anything at all.
		$this->verifyValue('id=doiPrefix', 'exact:'.$prefix);
		$this->verifyValue('id='.$suffixGenerationMethod, 'exact:on');
		if (!$this->verified()) {
			// Change the settings.
			$this->type('id=doiPrefix', $prefix);
			$this->click('id='.$suffixGenerationMethod);
			$this->type('id=doiSuffixPattern', $pattern);
			$this->clickAndWait('css=input.button.defaultButton');

			// Delete existing DOIs.
			$this->open($this->setupPage);
			$this->clickAndWait('name=reassignDOIs');
		}
	}

	/**
	 * Check whether the given DOI appears on the article meta-data page.
	 * @param $expectedDoi string
	 */
	private function checkArticleDoi($expectedDoi) {
		$this->verifyLocation('exact:'.$this->articlePage);
		if (!$this->verified()) {
			$this->open($this->articlePage);
		}
		$this->assertAttribute('//meta[@name="DC.Identifier.DOI"]@content', 'exact:'.$expectedDoi);
	}

	/**
	 * Check that DOI entry is not enabled.
	 * @param $editable boolean whether the DOI Suffix field should be editable.
	 * @param $expectedDoi string
	 */
	private function checkMetadataDoi($editable = false, $expectedDoi = null) {
		$this->verifyLocation('exact:'.$this->metadataPage);
		if (!$this->verified()) {
			$this->open($this->metadataPage);
		}
		if ($editable) {
			$this->assertElementPresent($this->doiInputField);
		} else {
			$this->assertElementNotPresent($this->doiInputField);
			$this->assertText($this->doiSection, $expectedDoi);
		}
	}
}
?>