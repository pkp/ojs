<?php

/**
 * @file tests/functional/settings/FunctionalDoiGenerationSettingsAndDisplayTest.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalDoiGenerationSettingsAndDisplayTest
 * @ingroup tests_functional_settings
 *
 * @brief Test DOI generation settings and display.
 *
 * FEATURE: DOI generation settings and display
 *   AS A    journal manager
 *   I WANT  to be able to assign DOIs to issues, articles, galleys and
 *           supplementary files of my journal according to my institutional
 *           prefix and suffix generation strategy
 *   SO THAT they can be uniquely identified for intellectual property
 *           transactions, in citations and look-up in meta-data databases
 *           and institutional repositories.
 */


import('lib.pkp.tests.WebTestCase');

class FunctionalDoiGenerationSettingsAndDisplayTest extends WebTestCase {
	private
		$pages,
		$objectTypes = array('Article', 'Issue', 'Galley', 'SuppFile'); // order is significant!


	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array(
			'journals', 'journal_settings', 'issues', 'issue_settings',
			'published_articles', 'articles', 'article_settings',
			'article_galleys', 'article_galley_settings',
			'article_supplementary_files', 'article_supp_file_settings'
		);
	}

	/**
	 * BACKGROUND:
	 *   GIVEN I am on the journal settings page step 1 (details)
	 *
	 * @see WebTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();

		// OJS pages and Selenium locators required for test.
		$this->pages = array(
			// journal setup
			'setup' => array(
				'url' => $this->baseUrl.'/index.php/test/manager/setup/%id',
				'doiPrefix' => 'id=doiPrefix',
				'reassignDOIs' => 'name=reassignDOIs',
				'formError' => '//ul[@class="pkp_form_error_list"]//a[@href="#%id"]',
				'saved' => $this->baseUrl.'/index.php/test/manager/setupSaved/1'
			),

			// public pages
			'issue' => array(
				'url' => $this->baseUrl.'/index.php/test/issue/view/%id',
				'visible' => '//a[@id="pub-id::doi"]'
			),
			'article' => array(
				'url' => $this->baseUrl.'/index.php/test/article/view/%id',
				'visible' => '//a[@id="pub-id::doi"]',
				'DC-meta' => '//meta[@name="DC.Identifier.DOI"]@content',
				'Google-meta' => '//meta[@name="citation_doi"]@content'
			),
			'article-rt-indexing' => array(
				'url' => $this->baseUrl.'/index.php/test/rt/metadata/%id/0',
				'visible' => '//tr/td[text()="Digital Object Identifier"]/../td[last()]'
			),
			'article-rt-citations-apa' => array(
				'url' => $this->baseUrl.'/index.php/test/rt/captureCite/%id/0/ApaCitationPlugin',
				'visible' => 'id=citation'
			),
			'galley' => array(
				'url' => $this->baseUrl.'/index.php/test/article/view/%id/1',
				'visible' => '//a[@id="pub-id::doi"]',
				'DC-meta' => '//meta[@name="DC.Identifier.DOI"]@content',
				'Google-meta' => '//meta[@name="citation_doi"]@content'
			),
			'suppfile' => array(
				'url' => $this->baseUrl.'/index.php/test/rt/suppFileMetadata/%id/0/1',
				'visible' => '//tr/td[text()="Digital Object Identifier"]/../td[last()]'
			),


			// meta-data editing pages
			'metadata-issue' => array(
				'url' => $this->baseUrl.'/index.php/test/editor/issueData/%id',
				'urlSuffix' => 'id=publicIssueId'
			),
			'metadata-article' => array(
				'url' => $this->baseUrl.'/index.php/test/editor/viewMetadata/%id',
				'urlSuffixPage' => $this->baseUrl.'/index.php/test/editor/issueToc/1',
				'urlSuffix' => 'name=publishedArticles[1]'
			),
			'metadata-galley' => array(
				'url' => $this->baseUrl.'/index.php/test/editor/editGalley/%id/1',
				'urlSuffix' => 'id=publicGalleyId'
			),
			'metadata-suppfile' => array(
				'url' => $this->baseUrl.'/index.php/test/editor/editSuppFile/%id/1',
				'urlSuffix' => 'id=publicSuppFileId'
			)
		);

		// The meta-data pages have uniform structure.
		foreach ($this->objectTypes as $objectType) {
			$objectType = strtolower($objectType);
			$this->pages["metadata-$objectType"] += array(
				'doi' => '//div[@id="pub-id::doi"]',
				'doiInput' => '//div[@id="pub-id::doi"]//input',
				'formError' => '//ul[@class="pkp_form_error_list"]//a[@href="#doiSuffix"]'
			);
		}

		// Start Selenium.
		$this->start();

		// Log in to OJS as admin.
		$this->logIn();

		// Open the settings page step 1 (details).
		$this->openSettingsPage();
	}


	/**
	 * SCENARIO OUTLINE: Display of DOIs for various journal objects
	 *    WHEN I enable DOIs for {object type}
	 *     AND I open up the {object page}
	 *    THEN an issue DOI will appear on that page in the given {display format}.
	 *
	 * EXAMPLES:
	 *   object type | object page                              | display format
	 *   ============|==========================================|============================
	 *   issue       | .../issue/view/1                         | visible (10.xxxx/xxx)
	 *   article     | .../article/view/1                       | visible (10.xxxx/xxx),
	 *               |                                          |   DC <meta>, Google <meta>
	 *   article     | .../rt/metadata/1/0                      | visible (10.xxxx/xxx)
	 *   article     | .../rt/captureCite/1/0/ApaCitationPlugin | visible (APA citation)
	 *   galley      | .../article/view/1/1                     | visible (10.xxxx/xxx)
	 *               |                                          |   DC <meta>, Google <meta>
	 *   supp file   | .../rt/suppFileMetadata/1/0/1            | visible (10.xxxx/xxx)
	 *
	 * SCENARIO OUTLINE: Standard pattern for suffix generation
	 *    WHEN I select the "default pattern" suffix generation strategy
	 *    THEN DOI suffixes for {object type} must follow a certain
	 *         {default pattern} where %j stands for the initials of the journal,
	 *         %v for the issue's volume, %i for the issue number, %a, %g, %s for
	 *         the internal OJS article, galley and supp file IDs respectively,
	 *         and %p for the page number.
	 *
	 * EXAMPLES:
	 *   object type | default pattern
	 *   ============|================
	 *   article     | %j.%v%i.%a
	 *   issue       | %j.%v%i
	 *   galley      | %j.%v%i.%a.g%g
	 *   supp file   | %j.%v%i.%a.s%s
	 */
	public function testDoiDisplayWithDefaultPatternOnAllPages() {
		// Enable DOIs with default settings for all objects.
		$this->configureDoi(true);

		// There should be no DOI meta-data field for this suffix generation strategy.
		$this->checkMetadataPages($editable = false);

		// Check whether the expected display formats exist for
		// the various object types and whether the suffix is
		// correctly generated according to the default pattern.
		$expectedDois = array(
			'issue' => '^10.1234/t.v1i1$',
			'article' => '^10.1234/t.v1i1.1$',
			'article-rt-indexing' => '^10.1234/t.v1i1.1$',
			'article-rt-citations-apa' => 'doi:10.1234/t.v1i1.1$',
			'galley' => '^10.1234/t.v1i1.1.g1$',
			'suppfile' => '^10.1234/t.v1i1.1.s1$'
		);
		foreach ($expectedDois as $objectType => $expectedDoi) {
			$this->checkDoiDisplay($objectType, $expectedDoi);
		}
	}


	/**
	 * SCENARIO OUTLINE: Disabled DOIs
	 *    WHEN I disable DOIs for {object type}
	 *     AND I open up the {object page}
	 *    THEN no issue DOI will appear anywhere on that page.
	 *
	 * EXAMPLES:
	 *   object type | object page
	 *   ============|==========================================
	 *   issue       | .../issue/view/1
	 *   article     | .../article/view/1
	 *   article     | .../rt/metadata/1/0
	 *   article     | .../rt/captureCite/1/0/ApaCitationPlugin
	 *   galley      | .../article/view/1/1
	 *   supp file   | .../rt/suppFileMetadata/1/0/1
	 */
	public function testDoiDisabled() {
		// Disable DOIs for all objects.
		$this->configureDoi(false);
		$testPages = array(
			'issue', 'article', 'article-rt-indexing',
			'article-rt-citations-apa', 'galley', 'suppfile'
		);
		foreach ($testPages as $objectType) {
			$this->checkDoiDisplay($objectType, false);
		}
	}


	/**
	 * SCENARIO OUTLINE: Individual journal prefix
	 *    WHEN I set the {DOI prefix}
	 *    THEN all DOIs for issues, articles, galleys and supp files
	 *         must start with that prefix (i.e. {DOI prefix}/xxx).
	 *
	 * EXAMPLES:
	 *   DOI prefix
	 *   ==========
	 *      10.1234
	 *      10.4321
	 */
	public function testDoiPrefix() {
		// Test 10.1234 example.
		$this->configureDoi(true, '10.1234');
		$this->checkDoiDisplay('article', '10.1234/t.v1i1.1');

		// Test 10.4321 example.
		$this->configureDoi(true, '10.4321');
		$this->checkDoiDisplay('article', '10.4321/t.v1i1.1');
	}


	/**
	 * SCENARIO OUTLINE: Custom pattern for suffix generation
	 *    WHEN I select the "custom pattern" suffix generation strategy
	 *    THEN DOI suffixes for {object type} must be generated according to
	 *         the GIVEN {custom pattern} where %j stands for the initials of the
	 *         journal, %v for the issue's volume, %i for the issue number, %a, %g,
	 *         %s for the internal OJS article, galley and supp file IDs respectively,
	 *         %p for the page number and %Y for the publication year.
	 *
	 * EXAMPLES:
	 *   object type | custom pattern
	 *   ============|==================
	 *   issue       | jor%j.%Y.vol%v
	 *   article     | jor%j.iss%i.art%a
	 *   galley      | jor%j.art%a.gal%g
	 *   supp file   | jor%j.art%a.suf%s
	 */
	public function testDoiCustomSuffixPattern() {
		// Configure custom pattern.
		$customPattern = array(
			'Issue' => 'jor%j.%Y.vol%v',
			'Article' => 'jor%j.iss%i.art%a',
			'Galley' => 'jor%j.art%a.gal%g',
			'SuppFile' => 'jor%j.art%a.suf%s'
		);
		$this->configureDoi(true, '10.1234', 'doiSuffix', $customPattern);

		// Check the results.
		$expectedDois = array(
			'issue' => '10.1234/jort.2011.vol1',
			'article' => '10.1234/jort.iss1.art1',
			'galley' => '10.1234/jort.art1.gal1',
			'suppfile' => '10.1234/jort.art1.suf1'
		);
		foreach ($expectedDois as $objectType => $expectedDoi) {
			$this->checkDoiDisplay($objectType, "^${expectedDoi}$");
			$this->checkMetadataPage($objectType, $editable = false, $expectedDoi);
		}
	}


	/**
	 * SCENARIO: Empty custom pattern not allowed
	 *    WHEN I select the "individual DOI suffix" option
	 *     AND I do not enter any suffix generation patterns
	 *         for an enabled publishing object
	 *    THEN an error message should alert me that I must
	 *         enter a pattern for enabled objects
	 *     AND no error message appears when leaving fields
	 *         of non-enabled publishing objects empty.
	 */
	public function testDoiCustomSuffixPatternEmptyNotAllowed() {
		// Select the "individual DOI suffix" option.
		$this->click('id=doiSuffix');

		// Make sure that DOIs for all object types
		// are enabled but that their suffix patterns are empty.
		foreach ($this->objectTypes as $objectType) {
			$this->check("id=enable${objectType}Doi");
			$this->type("id=doi${objectType}SuffixPattern", '');
		}

		// Try to save settings.
		$this->clickAndWait('css=input.button.defaultButton');

		// Now we should find error messages for all four object
		// types.
		foreach ($this->objectTypes as $objectType) {
			$formError = str_replace(
				'%id', "doi${objectType}SuffixPattern",
				$this->pages['setup']['formError']
			);
			$this->assertElementPresent($formError);
		}

		// Disable DOIs for all object types and the
		// error messages should disappear.
		foreach ($this->objectTypes as $objectType) {
			$this->uncheck("id=enable${objectType}Doi");
		}

		// Save settings.
		$this->clickAndWait('css=input.button.defaultButton');

		// If the save action was successful then
		// we should see the save confirmation page.
		$this->waitForLocation('exact:'.$this->pages['setup']['saved']);
	}


	/**
	 * SCENARIO OUTLINE: Suffix generation based on custom ID (custom URL suffix)
	 *    WHEN I select the "custom identifier" option
	 *    THEN the {generated DOI} for a GIVEN {object type} must be equal
	 *         to the prefix plus the {custom identifier} or - in the absence of such
	 *         an identifier - equal to the internal {object id} of the corresponding
	 *         publication object. The internal id - if used - must be preceded by some
	 *         unique object type dependent letter to avoid duplicate DOIs, except for
	 *         articles where no letter will be used to maintain backwards compatibility.
	 *
	 * EXAMPLES:
	 *   object type | custom identifier  | object id | generated DOI
	 *   ============|====================|===========|===========================
	 *   issue       | issue_url          |         1 | 10.1234/issue_url
	 *   issue       |                    |         1 | 10.1234/i1
	 *   article     | article_url        |         1 | 10.1234/article_url
	 *   article     |                    |         1 | 10.1234/1
	 *   galley      | article_galley_url |         1 | 10.1234/article_galley_url
	 *   galley      |                    |         1 | 10.1234/g1
	 *   supp file   | supp_file_url      |         1 | 10.1234/supp_file_url
	 *   supp file   |                    |         1 | 10.1234/s1
	 */
	public function testDoiSuffixIsCustomUrlSuffix() {
		// Configure the custom URL (a.k.a. publisher ID) suffix generation method.
		$this->configureDoi(true, '10.1234', 'doiSuffixPublisherId');
		// Enable custom URL suffixes.
		$this->configurePublisherIds(true);

		// Enter test URL-suffixes.
		$testUrlSuffixes = array(
			'issue' => 'issue_url',
			'article' => 'article_url',
			'galley' => 'galley_url',
			'suppfile' => 'supp_file_url',
		);

		// Check whether DOIs are generated based on these suffixes.
		foreach ($testUrlSuffixes as $objectType => $testUrlSuffix) {
			$this->setUrlSuffix($objectType, $testUrlSuffix);
			$expectedDoi = "10.1234/${testUrlSuffix}";
			$this->checkDoiDisplay($objectType, "^${expectedDoi}$");
			$this->checkMetadataPage($objectType, $editable = false, $expectedDoi);
		}

		// Delete DOIs generated so far.
		$this->deleteExistingDois();

		// Check whether DOIs are generated by "best id" when no suffix is given.
		$expectedDoisWithoutUrlSuffix = array(
			'issue' => '10.1234/i1',
			'article' => '10.1234/1',
			'galley' => '10.1234/g1',
			'suppfile' => '10.1234/s1',
		);
		foreach ($expectedDoisWithoutUrlSuffix as $objectType => $expectedDoi) {
			$this->setUrlSuffix($objectType, '');
			$this->checkDoiDisplay($objectType, "^${expectedDoi}$");
		}

		// Disable custom URL suffixes.
		$this->configurePublisherIds(false);
	}


	/**
	 * SCENARIO OUTLINE: Suffix generation based on custom ID (URL independent)
	 *    WHEN I select the "individual DOI suffix" option
	 *    THEN an input field for the individual identifier must be present
	 *         on the {object type}s meta-data entry page as long as no
	 *         DOI has been generated yet
	 *     AND the {generated DOI} for a GIVEN {object type} must be equal
	 *         to the prefix plus the {individual identifier} or - if no identifier
	 *         has been entered - equal to the internal {object id} of the
	 *         corresponding publication object. The internal id - if used - must be
	 *         preceded by some unique object type dependent letter to avoid duplicate
	 *         DOIs, except for articles where no letter will be used to maintain
	 *         backwards compatibility.
	 *
	 * EXAMPLES:
	 *   object type | individual identifier | object id | generated DOI
	 *   ============|=======================|===========|===============================
	 *   article     | article_suffix        |         1 | 10.1234/article_suffix
	 *   article     |                       |         1 | 10.1234/1
	 *   issue       | issue_suffix          |         1 | 10.1234/issue_suffix
	 *   issue       |                       |         1 | 10.1234/i1
	 *   galley      | article_galley_suffix |         1 | 10.1234/article_galley_suffix
	 *   galley      |                       |         1 | 10.1234/g1
	 *   supp file   | supp_file_suffix      |         1 | 10.1234/supp_file_suffix
	 *   supp file   |                       |         1 | 10.1234/s1
	 */
	public function testDoiSuffixIsCustomId() {
		// Change the suffix generation method.
		$this->configureDoi(true, '10.1234', 'doiSuffixCustomIdentifier');

		$existingSuffix = null;
		foreach ($this->objectTypes as $objectType) {
			$objectType = strtolower($objectType);

			// An input field should be present on all meta-data pages
			// as long as no DOI has been stored.
			$this->checkMetadataPage($objectType, $editable = true);

			// Check that an already existing suffix cannot be re-used.
			if ($objectType != 'article') {
				// NB: Called in all but the first iteration.
				self::assertNotNull($existingSuffix);
				$this->checkDuplicateDoiSuffixError($objectType, '1', $existingSuffix);
			}

			// Let's enter a custom DOI.
			$metadataPage = $this->pages["metadata-$objectType"];
			$customSuffix = "custom_${objectType}_doi";
			$this->type($metadataPage['doiInput'], $customSuffix);
			$this->clickAndWait('css=input.button.defaultButton');
			$this->assertElementNotPresent($metadataPage['formError']);

			// Check whether the custom DOI has been correctly generated.
			$expectedDoi = "10.1234/$customSuffix";
			$this->checkDoiDisplay($objectType, $expectedDoi);

			// As soon as the DOI has been generated the meta-data field
			// should no longer be editable and the generated DOI should
			// be displayed instead.
			$this->checkMetadataPage($objectType, $editable = false, $expectedDoi);

			// Check that an already existing suffix cannot be re-used.
			if ($objectType == 'article') {
				// NB: Called in first iteration only.
				$existingSuffix = $customSuffix;
				// Check the article against a second article.
				$this->checkDuplicateDoiSuffixError($objectType, '2', $existingSuffix);
			}
		}
	}


	/**
	 * SCENARIO: Suffixes must be persistent
	 *   GIVEN a suffix has already been generated for an object
	 *    WHEN I change the suffix generation strategy
	 *    THEN the already assigned suffix of the object will not
	 *         change to guarantee a persistent object identifier.
	 *
	 * SCENARIO: Reassigning suffixes
	 *   GIVEN a suffix has already been generated for an object
	 *    WHEN I go to the setup step 1
	 *     AND I choose a suffix generation strategy different from the one that
	 *         had been used previously for the object
	 *     AND I click the "Reassign DOIs" button
	 *    THEN the suffix for this object (and all other objects) will change.
	 */
	public function testDoiWillNotChangeWithoutReset() {
		// Configure DOI defaults and delete all existing DOIs.
		$this->configureDoi();

		// Generate a test DOI.
		$this->checkDoiDisplay('article', '10.1234/t.v1i1.1');

		// Change and save a DOI setting without deleting DOIs.
		$this->open($this->getUrl('setup', 1));
		$this->type('id=doiPrefix', '10.4321');
		$this->clickAndWait('css=input.button.defaultButton');

		// Check that the DOI didn't change.
		$this->checkDoiDisplay('article', '10.1234/t.v1i1.1');

		// Check that only deleting the DOIs will actually
		// re-assign a new DOI.
		$this->deleteExistingDois();
		$this->checkDoiDisplay('article', '10.4321/t.v1i1.1');
	}


	/**
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown() {
		// Restart the session so that we get access
		// to Selenium to clean up our configuration.
		$this->start();

		// Reset to standard settings.
		$this->resetDoiSettings();

		// Explicitly stop Selenium otherwise our session
		// will not be freed for re-use.
		$this->stop();

		parent::tearDown();
	}

	/**
	 * @see PHPUnit_Extensions_SeleniumTestCase::onNotSuccessfulTest()
	 */
	protected function onNotSuccessfulTest($e) {
		// Restart the Selenium session to avoid overlay of
		// error messages.
		$this->start();
		parent::onNotSuccessfulTest($e);
	}

	/*
	 * Private helper methods
	 */

	/**
	 * Return the url of the given page with the article ID
	 * correctly inserted.
	 * @param $page string
	 * @param $articleId integer
	 */
	private function getUrl($page, $id = null) {
		return str_replace('%id', (string) $id, $this->pages[$page]['url']);
	}

	/**
	 * Open the settings page
	 */
	private function openSettingsPage() {
		$this->verifyLocation('exact:'.$this->getUrl('setup', 1));
		if (!$this->verified()) {
			$this->open($this->getUrl('setup', 1));
		}
	}

	/**
	 * Configures the DOI prefix and suffix generation method
	 * and resets all DOIs so that the new rules will be applied.
	 * @param $enabled boolean
	 * @param $prefix string
	 * @param $suffixGenerationMethod string
	 * @param $pattern string
	 */
	private function configureDoi($enabled = true, $prefix = '10.1234', $suffixGenerationMethod = 'doiSuffixDefault',
			$pattern = array('Issue' => '', 'Article' => '', 'Galley' => '', 'SuppFile' => '')) {

		// Make sure the settings page is open.
		$this->openSettingsPage();

		// Check whether we have to change anything at all.
		foreach($this->objectTypes as $objectType) {
			$enableObject = 'id=enable'.$objectType.'Doi';
			if ($enabled) {
				$this->verifyChecked($enableObject);
			} else {
				$this->verifyNotChecked($enableObject);
			}
		}
		$this->verifyValue($this->pages['setup']['doiPrefix'], 'exact:'.$prefix);
		$this->verifyValue('id='.$suffixGenerationMethod, 'exact:on');
		if (!$this->verified()) {
			// Enable/Disable DOI generation.
			foreach($this->objectTypes as $objectType) {
				$enableObject = 'id=enable'.$objectType.'Doi';
				if ($enabled) {
					$this->check($enableObject);
				} else {
					$this->uncheck($enableObject);
				}
			}

			// Configure the prefix.
			$this->type($this->pages['setup']['doiPrefix'], $prefix);
			$this->click('id='.$suffixGenerationMethod);

			// Configure the suffix patterns.
			foreach ($pattern as $objectType => $suffixPattern) {
				$this->type(
					"id=doi${objectType}SuffixPattern",
					$suffixPattern
				);
			}

			// Save settings.
			$this->clickAndWait('css=input.button.defaultButton');

			// Delete existing DOIs.
			$this->deleteExistingDois();
		}
	}

	/**
	 * Delete all existing DOIs.
	 */
	private function deleteExistingDois() {
		$this->open($this->getUrl('setup', 1));
		$this->clickAndWait($this->pages['setup']['reassignDOIs']);
	}

	/**
	 * Enable/Disable publisher IDs.
	 */
	private function configurePublisherIds($enabled = true) {
		// Enable publisher IDs for all objects.
		$this->open($this->getUrl('setup', 4));
		foreach($this->objectTypes as $objectType) {
			$optionLocator = "id=enablePublic${objectType}Id";
			if ($enabled) {
				$this->check($optionLocator);
			} else {
				$this->uncheck($optionLocator);
			}
		}
		$this->clickAndWait('css=input.button.defaultButton');
	}

	/**
	 * Enter the URL suffix for the given object type.
	 * @param $objectType string
	 * @param $urlSuffix string
	 */
	private function setUrlSuffix($objectType, $urlSuffix) {
		$metadataPage = $this->pages["metadata-$objectType"];
		if (isset($metadataPage['urlSuffixPage'])) {
			$url = $metadataPage['urlSuffixPage'];
		} else {
			$url = $metadataPage['url'];
		}
		$this->open(str_replace('%id', '1', $url));
		$this->type($metadataPage['urlSuffix'], $urlSuffix);
		$this->clickAndWait('css=input.button.defaultButton');
		if ($objectType == 'article') {
			$this->assertConfirmation('Save changes to table of contents?');
		}
	}

	/**
	 * Check whether the given DOI appears on the object's page.
	 * @param $objectType string
	 * @param $expectedDoi string/boolean the expected DOI or false if
	 *    no DOI should be present.
	 */
	private function checkDoiDisplay($objectType, $expectedDoi) {
		$this->verifyLocation('exact:'.$this->getUrl($objectType, 1));
		if (!$this->verified()) {
			$this->open($this->getUrl($objectType, 1));
		}
		try {
			if ($expectedDoi === false) {
				$visibleElement = $this->pages[$objectType]['visible'];
				if (strpos($objectType, 'citations') !== false) {
					$this->assertNotText($visibleElement, 'doi');
				} else {
					$this->assertElementNotPresent($visibleElement);
				}
				foreach (array('DC-meta', 'Google-meta') as $doiMetaAttribute) {
					if (isset($this->pages[$objectType][$doiMetaAttribute])) {
						$doiMetaElement = String::regexp_replace(
							'/@[^@]+$/', '',
							$this->pages[$objectType][$doiMetaAttribute]
						);
						$this->assertElementNotPresent($doiMetaElement);
					}
				}
			} else {
				$this->assertText($this->pages[$objectType]['visible'], $expectedDoi);
				foreach (array('DC-meta', 'Google-meta') as $doiMetaAttribute) {
					if (isset($this->pages[$objectType][$doiMetaAttribute])) {
						$this->assertAttribute(
							$this->pages[$objectType][$doiMetaAttribute],
							$expectedDoi
						);
					}
				}
			}
		} catch(Exception $e) {
			throw $this->improveException($e, $objectType);
		}
	}

	/**
	 * Check DOI input and display on all metadata pages.
	 * @param $editable boolean whether the DOI Suffix field should be editable.
	 * @param $expectedDoi string
	 */
	private function checkMetadataPages($editable = false, $expectedDoi = null) {
		foreach($this->objectTypes as $objectType) {
			$this->checkMetadataPage($objectType, $editable, $expectedDoi);
		}
	}

	/**
	 * Check DOI input and display on a single metadata page.
	 * @param $objectType string
	 * @param $editable boolean whether the DOI Suffix field should be editable.
	 * @param $expectedDoi string
	 */
	private function checkMetadataPage($objectType, $editable = false, $expectedDoi = null) {
		try {
			$objectType = strtolower($objectType);
			$metadataPage = "metadata-$objectType";
			$this->verifyLocation('exact:'.$this->getUrl($metadataPage, 1));
			if (!$this->verified()) {
				$this->open($this->getUrl($metadataPage, 1));
			}
			if ($editable) {
				$this->assertElementPresent($this->pages[$metadataPage]['doiInput']);
			} else {
				$this->assertElementNotPresent($this->pages[$metadataPage]['doiInput']);
				$this->assertText($this->pages[$metadataPage]['doi'], $expectedDoi);
			}
		} catch(Exception $e) {
			throw $this->improveException($e, $objectType);
		}
	}

	/**
	 * Check whether the given meta-data page denies
	 * entering an existing DOI suffix across all object
	 * types.
	 * @param $objectType string
	 * @param $objectId string
	 * @param $existingSuffix string
	 */
	private function checkDuplicateDoiSuffixError($objectType, $objectId, $existingSuffix) {
		// We should get an error when trying to enter an already existing
		// suffix for another object.
		$metadataPage = "metadata-$objectType";
		$this->open($this->getUrl($metadataPage, $objectId));
		$this->type($this->pages[$metadataPage]['doiInput'], $existingSuffix);
		$this->clickAndWait('css=input.button.defaultButton');
		$this->assertElementPresent($this->pages[$metadataPage]['formError']);
	}

	/**
	 * Reset to standard DOI settings.
	 */
	private function resetDoiSettings() {
		$this->openSettingsPage();
		$this->configureDoi();
	}
}
?>