<?php

/**
 * @file tests/functional/plugins/pubIds/FunctionalDoiPubIdPluginTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalDOIPubIdPluginTest
 * @ingroup tests_functional_plugins_pubIds
 *
 * @brief Test DOI PubId plug-in.
 *
 * FEATURE: DOI generation settings and display
 *   AS A    journal manager
 *   I WANT  to be able to assign DOIs to issues, articles, and galleys
 *           of my journal according to my institutional prefix and suffix
 *           generation strategy
 *   SO THAT they can be uniquely identified for intellectual property
 *           transactions, in citations and look-up in meta-data databases
 *           and institutional repositories.
 */


import('lib.pkp.tests.WebTestCase');

class FunctionalDOIPubIdPluginTest extends WebTestCase {
	private
		$pages,
		$objectTypes = array('Article', 'Issue', 'Galley'); // order is significant!


	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array(
			'journal_settings', 'plugin_settings', 'issues', 'issue_settings',
			'published_submissions', 'submissions', 'submission_settings',
			'submission_files', 'submission_galleys', 'submission_galley_settings',
			'event_log', 'event_log_settings', 'notifications', 'sessions'
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
			'journal-setup' => array(
				'url' => $this->baseUrl.'/index.php/test/manager/setup/%id'
			),

			// DOI plug-in settings
			'settings' => array(
				'url' => $this->baseUrl.'/index.php/test/manager/plugins',
				'doiPrefix' => 'css=input[id^="doiPrefix"]',
				'reassignDOIs' => 'css=a[id^="doiSettingsForm-reassignDOIs-button"]',
				'formError' => '//ul[@class="pkp_form_error_list"]//a[@href="#%id"]'
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


			// meta-data editing pages
			'metadata-issue' => array(
				'url' => $this->baseUrl.'/index.php/test/editor/issueData/%id',
				'urlSuffix' => 'id=publicIssueId'
			),
			'metadata-article' => array(
				'url' => $this->baseUrl.'/index.php/test/editor/viewMetadata/%id',
				'urlSuffixPage' => $this->baseUrl.'/index.php/test/editor/issueToc/%id',
				'urlSuffix' => 'name=publishedArticles[1]'
			),
			'metadata-galley' => array(
				'url' => $this->baseUrl.'/index.php/test/editor/editGalley/%id/1',
				'urlSuffix' => 'id=publicGalleyId'
			),
		);

		// The meta-data pages have uniform structure.
		foreach ($this->objectTypes as $objectType) {
			$objectType = strtolower_codesafe($objectType);
			$this->pages["metadata-$objectType"] += array(
				'doi' => '//div[@id="pub-id::doi"]',
				'doiInput' => '//div[@id="pub-id::doi"]//input',
				'formError' => '//ul[@class="pkp_form_error_list"]//a[@href="#%id"]'
			);
		}

		// Start Selenium.
		$this->start();

		// Log in to OJS as admin.
		$this->logIn();

		// Open the DOI settings page.
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
	 *
	 * SCENARIO OUTLINE: Standard pattern for suffix generation
	 *    WHEN I select the "default pattern" suffix generation strategy
	 *    THEN DOI suffixes for {object type} must follow a certain
	 *         {default pattern} where %j stands for the initials of the journal,
	 *         %v for the issue's volume, %i for the issue number, %a, %g, %s for
	 *         the internal OJS article and galley IDs respectively,
	 *         and %p for the page number.
	 *
	 * EXAMPLES:
	 *   object type | default pattern
	 *   ============|================
	 *   article     | %j.%v%i.%a
	 *   issue       | %j.%v%i
	 *   galley      | %j.%v%i.%a.g%g
	 */
	public function testDoiDisplayWithDefaultPatternOnAllPages() {
		// Enable DOIs with default settings for all objects.
		$this->configureDoi(true);

		// Check whether the expected display formats exist for
		// the various object types and whether the suffix is
		// correctly generated according to the default pattern.
		$expectedDois = array(
			'issue' => '10.1234/t.v1i1',
			'article' => '10.1234/t.v1i1.1',
			'article-rt-indexing' => '10.1234/t.v1i1.1',
			'article-rt-citations-apa' => 'doi:10.1234/t.v1i1.1',
			'galley' => '10.1234/t.v1i1.1.g1',
		);
		foreach ($expectedDois as $objectType => $expectedDoi) {
		    // There should be no DOI meta-data field for this suffix generation strategy.
		    if (strpos($objectType, '-rt-') === false) {
				$this->checkMetadataPage($objectType, $editable = false, $expectedDoi, 1, true);
		    }
		    // DOIs should be generated for all object pages automatically.
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
	 */
	public function testDoiDisabled() {
		// Disable DOIs for all objects.
		$tests = array(
			'Issue' => 'issue',
			'Article' => 'article',
			'Article' => 'article-rt-indexing',
			'Article' => 'article-rt-citations-apa',
			'Galley' => 'galley',
		);
		foreach ($tests as $objectType => $page) {
			$this->configureDoi($objectType);
			$this->checkDoiDisplay($page, false);
		}
	}


	/**
	 * SCENARIO OUTLINE: Individual journal prefix
	 *    WHEN I set the {DOI prefix}
	 *    THEN all DOIs for issues, articles, and galleys
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
	 *         for the internal OJS article and galley IDs respectively,
	 *         %p for the page number and %Y for the publication year.
	 *
	 * EXAMPLES:
	 *   object type | custom pattern
	 *   ============|==================
	 *   issue       | jor%j.%Y.vol%v
	 *   article     | jor%j.iss%i.art%a
	 *   galley      | jor%j.art%a.gal%g
	 */
	public function testDoiCustomSuffixPattern() {
		// Configure custom pattern.
		$customPattern = $this->getCustomPatternArray();
		$this->configureDoi(true, '10.1234', 'doiSuffix', $customPattern);

		// Check the results.
		$expectedDois = array(
			'issue' => '10.1234/jort.2011.vol1',
			'article' => '10.1234/jort.iss1.art1',
			'galley' => '10.1234/jort.art1.gal1',
		);
		foreach ($expectedDois as $objectType => $expectedDoi) {
			$this->checkDoiDisplay($objectType, $expectedDoi);
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
		$this->submitAjaxForm('doiSettingsForm');

		// Now we should find error messages for all four object
		// types.
		foreach ($this->objectTypes as $objectType) {
			$formError = str_replace(
				'%id', "doi${objectType}SuffixPattern",
				$this->pages['settings']['formError']
			);
			$this->assertElementPresent($formError);
		}

		// Disable DOIs for an object type and the
		// corresponding error message should disappear.
		foreach ($this->objectTypes as $objectType) {
			$this->uncheck("id=enable${objectType}Doi");
			$this->submitAjaxForm('doiSettingsForm');
			$this->assertLocation('exact:' . $this->getUrl('settings') . '#formErrors');
			$formError = str_replace(
				'%id', "doi${objectType}SuffixPattern",
				$this->pages['settings']['formError']
			);
			$this->assertElementNotPresent($formError);
		}
	}


	/**
	 * SCENARIO OUTLINE: Suffix generation based on custom ID (custom URL suffix)
	 *    WHEN I select the "custom identifier" option
	 *    THEN the {generated DOI} for a GIVEN {object type} must be equal
	 *         to the prefix plus the {custom identifier} or - in the absence of such
	 *         an identifier - equal to the internal {object id} of the corresponding
	 *         publication object
	 *     AND the internal id - if used - must be preceded by some unique object type
	 *         dependent letter to avoid duplicate DOIs, except for articles where no
	 *         letter will be used to maintain backwards compatibility.
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
		);

		// Check whether DOIs are generated based on these suffixes.
		foreach ($testUrlSuffixes as $objectType => $testUrlSuffix) {
			$this->setUrlSuffix($objectType, $testUrlSuffix);
			$expectedDoi = "10.1234/${testUrlSuffix}";
			$this->checkDoiDisplay($objectType, $expectedDoi);
			$this->checkMetadataPage($objectType, $editable = false, $expectedDoi);
		}

		// Delete DOIs generated so far.
		$this->deleteExistingDois();

		// Check whether DOIs are generated by "best id" when no suffix is given.
		$expectedDoisWithoutUrlSuffix = array(
			'issue' => '10.1234/i1',
			'article' => '10.1234/1',
			'galley' => '10.1234/g1',
		);
		foreach ($expectedDoisWithoutUrlSuffix as $objectType => $expectedDoi) {
			$this->setUrlSuffix($objectType, '');
			$this->checkDoiDisplay($objectType, $expectedDoi);
		}

		// Disable custom URL suffixes.
		$this->configurePublisherIds(false);
	}


	/**
	 * SCENARIO OUTLINE: Check for duplicate url suffixes across object types
	 *   GIVEN I already assigned the custom identifier “doitest” to
	 *         a {publication object} in the database
	 *    WHEN I select the "custom identifier" option
	 *     AND I navigate to the {meta-data page} of another object
	 *     AND I enter that same custom identifier for the other object
	 *     AND I click the “Save” button
	 *    THEN the object will not be saved
	 *     AND the form will redisplay with an error message "...".
	 *
	 * EXAMPLES:
	 *   publication object | meta-data page
	 *   ===================|========================
	 *   issue              | editor/editSuppFile/1/1 (?)
	 *   article            | editor/issueData/1
	 *   galley             | editor/viewMetadata/1
	 */
	public function testCheckForDuplicateUrlSuffixesAcrossObjectTypes() {
		// This test is not about testing the settings GUI so
		// configure DOIs via the database which is faster.
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds');
		$doiPlugin = $pubIdPlugins['DOIPubIdPlugin'];
		$doiPlugin->updateSetting(1, 'doiSuffix', 'publisherId');
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var journalDao JournalDAO */
		$journal = $journalDao->getById(1);
		foreach($this->objectTypes as $objectType) {
			$journal->updateSetting('enablePublic' . $objectType . 'Id', true);
		}

		// Assign object types to DAO names and methods.
		$daos = array(
			'Issue' => array('IssueDAO', 'getById', 'updateObject'),
			'Article' => array('PublishedArticleDAO', 'getBySubmissionId', 'updateObject'),
			'Galley' => array('ArticleGalleyDAO', 'getById', 'updateObject'),
		);

		// Test examples.
		$examples = array(
			'Article' => 'Issue',
			'Galley' => 'Article',
		);

		// Go through all object types.
		foreach($this->objectTypes as $objectType) {
			// Assign the URL suffix "doitest" to an object
			// with the given object type. Again, this is not
			// to test the DOI so we do it directly in the
			// database.
			$dao = DAORegistry::getDAO($daos[$objectType][0]);
			// Retrieve the object.
			$object = $dao->$daos[$objectType][1](1);
			// Set its URL suffix to 'doitest'.
			$object->setStoredPubId('publisher-id', 'doitest');
			// Due to the 'unconventional' implementation of the article
			// DAOs we have to manually change our DAO for article update.
			if (is_a($object, 'PublishedArticle')) $dao = $dao->articleDao;
			// Update the object.
			$dao->$daos[$objectType][2]($object);

			// Navigate to the meta-data page given in the example
			// and enter the (duplicate) URL suffix "doitest" and
			// try to save the form.
			$targetObjectType = $examples[$objectType];
			$this->setUrlSuffix(strtolower_codesafe($targetObjectType), 'doitest');

			// Check that the form is being redisplayed with an error.
			$expectedErrorMessage = "The public identifier 'doitest' already exists.*";
			if ($targetObjectType == 'Article') {
				// We expect a notification in the case of article
				// URL suffixes as they are edited in the issue toc
				// which is not a form.
				$this->waitForLocation(
					'exact:'.str_replace('%id', '1', $this->pages['metadata-article']['urlSuffixPage'])
				);
				$this->waitForElementPresent('//*[contains(@class,"ui-pnotify-text") and contains(text(),' . $this->quoteXpath($expectedErrorMessage) . ')]');
			} else {
				// All other target objects are edited in forms and should
				// produce a form error.
				$metadataPage = 'metadata-' . strtolower_codesafe($targetObjectType);
				$this->assertText(
					str_replace(
						'%id', "public${targetObjectType}Id",
						$this->pages[$metadataPage]['formError']
					),
					$expectedErrorMessage
				);
			}
		}
	}

	/**
	 * SCENARIO OUTLINE: Suffix generation based on custom ID (URL independent)
	 *    WHEN I select the "individual DOI suffix" option
	 *    THEN I see an input field for the individual identifier
	 *         on the {object type}s meta-data entry page as long as no
	 *         DOI has been generated yet
	 *     AND the {generated DOI} for a GIVEN {object type} must be equal
	 *         to the prefix plus the {individual identifier} or - if no identifier
	 *         has been entered - no DOI will be generated
	 *     AND the internal id - if used - must be preceded by some unique object
	 *         type dependent letter to avoid duplicate DOIs, except for articles
	 *         where no letter will be used to maintain backwards compatibility.
	 *
	 * EXAMPLES:
	 *   object type | individual identifier | generated DOI
	 *   ============|=======================|===============================
	 *   article     | article_suffix        | 10.1234/article_suffix
	 *   article     | ./.                   | ./.
	 *   issue       | issue_suffix          | 10.1234/issue_suffix
	 *   issue       | ./.                   | ./.
	 *   galley      | article_galley_suffix | 10.1234/article_galley_suffix
	 *   galley      | ./.                   | ./.
	 *
	 *
	 * SCENARIO OUTLINE: Delete custom suffix.
	 *   GIVEN I select the "individual DOI suffix" option
	 *     AND I already assigned a custom DOI suffix to a
	 *         {publication object}
	 *     BUT a DOI has not yet been generated for that object
	 *    WHEN I empty the custom suffix field for that object
	 *         on its corresponding {meta-data page}
	 *     AND I click the “Save” button
	 *    THEN the existing custom DOI suffix should be deleted
	 *         in the database.
	 *
	 * EXAMPLES:
	 *   publication object | meta-data page
	 *   ===================|========================
	 *   issue              | editor/issueData/1
	 *   article            | editor/viewMetadata/1
	 *   galley             | editor/editGalley/1/1
	 */
	public function testDoiSuffixIsCustomId() {
		// Change the suffix generation method.
		$this->configureDoi(true, '10.1234', 'doiSuffixCustomIdentifier');

		$existingSuffix = null;
		foreach ($this->objectTypes as $objectType) {
			$objectType = strtolower_codesafe($objectType);

			// An input field should be present on all meta-data pages
			// as long as no DOI has been generated.
			$this->checkMetadataPage($objectType, $editable = true, '');

			// Check that an already existing suffix cannot be re-used.
			if ($objectType != 'article') {
				// NB: Called in all but the first iteration.
				self::assertNotNull($existingSuffix);
				$this->checkDuplicateDoiSuffixError($objectType, '1', $existingSuffix);
			}

			// Let's enter a custom DOI.
			$customSuffix = "custom_${objectType}_doi";
			$this->setCustomId($objectType, $customSuffix);

			// Make sure that the suffix has been saved and is being
			// re-displayed in the form which should still be editable.
			$this->checkMetadataPage($objectType, $editable = true, $customSuffix);

			// Enter an empty suffix (=delete the suffix).
			$this->setCustomId($objectType, '');

			// Check that no DOI is being generated when entering
			// while the suffix is empty.
			$this->checkDoiDisplay($objectType, false);

			// Re-enter the suffix.
			$this->checkMetadataPage($objectType, $editable = true, '');
			$this->setCustomId($objectType, $customSuffix);

			// Check whether a custom DOI is correctly generated.
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
	 *    WHEN I go to the DOI settings page
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
		$this->openSettingsPage();
		$this->type('css=input[id^="doiPrefix"]', '10.4321');
		$this->submitAjaxForm('doiSettingsForm');

		// Check that the DOI didn't change.
		$this->checkDoiDisplay('article', '10.1234/t.v1i1.1');

		// Check that only deleting the DOIs will actually
		// re-assign a new DOI.
		$this->deleteExistingDois();
		$this->checkDoiDisplay('article', '10.4321/t.v1i1.1');
	}


	/**
	 * SCENARIO OUTLINE: Preview DOI of published articles.
	 *    WHEN I choose {suffix generation method}
	 *     AND I open the metadata page of an published
	 *         article that does not yet have a DOI generated
	 *    THEN I'll see a {DOI preview} without a DOI being
	 *         generated.
	 *
	 * EXAMPLES:
	 *   suffix generation method | DOI preview
	 *   =================================================
	 *   default pattern          | 10.1234/t.v1i1.1
	 *   custom pattern           | 10.1234/jort.iss1.art1
	 *   custom url suffix        | 10.1234/custom-url
	 *
	 *
	 * SCENARIO OUTLINE: Preview DOI of unpublished articles.
	 *    WHEN I choose {suffix generation method}
	 *     AND I open the metadata page of an unpublished
	 *         article
	 *    THEN I'll see a {partial DOI preview} even if the
	 *         article is not yet published
	 *     AND a DOI will not be generated.
	 *
	 * EXAMPLES:
	 *   suffix generation method | partial DOI preview
	 *   ==================================================
	 *   default pattern          | 10.1234/t.v%vi%i.2
	 *   custom pattern           | 10.1234/jort.iss%i.art2
	 *   custom url suffix        | 10.1234/2
	 */
	public function testPreviewArticleDoi() {
		// Our test cases. For performance reasons we test both
		// scenarios together.
		$testCases = array(
			'doiSuffixDefault' => array(1 => '10.1234/t.v1i1.1', 2 => '10.1234/t.v%vi%i.2'),
			'doiSuffix' =>  array(1 => '10.1234/jort.iss1.art1', 2 => '10.1234/jort.iss%i.art2'),
			'doiSuffixPublisherId' =>  array(1 => '10.1234/custom-url', 2 => '10.1234/2')
		);

		// Enable custom URL suffixes.
		$this->configurePublisherIds(true);
		$this->setUrlSuffix('article', 'custom-url');

		// Run through all test cases.
		foreach ($testCases as $suffixGenerationMethod => $expectedPreviews) {
			// Set the suffix generation method.
			if ($suffixGenerationMethod == 'doiSuffix') {
				$customPattern = $this->getCustomPatternArray();
				$this->configureDoi(true, '10.1234', $suffixGenerationMethod, $customPattern);
			} else {
				$this->configureDoi(true, '10.1234', $suffixGenerationMethod);
			}

			// Check the (partial) DOI prefix.
			foreach ($expectedPreviews as $articleId => $expectedPreview) {
				$this->checkMetadataPage('article', false, $expectedPreview, $articleId, true);
			}
		}
	}


	/**
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown() {
		// Restart the session so that we get access
		// to Selenium to clean up our configuration.
		$this->start();

		// Reset to standard settings. We have to do this
		// through the UI to correctly reset caches, too.
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


	//
	// Private helper methods
	//
	/**
	 * Return the url of the given page with the article ID
	 * correctly inserted.
	 * @param $page string
	 * @param $articleId integer
	 */
	private function getUrl($page, $id = null) {
		$url = $this->pages[$page]['url'];
		if (!is_null($id)) {
			$url = str_replace('%id', (string) $id, $url);
		}
		return $url;
	}

	/**
	 * Open the settings page
	 */
	private function openSettingsPage() {
		$this->verifyAndOpen($this->getUrl('settings'));
		$this->waitForElementPresent('css=tr.elementDOIPubIdPlugin');
		$this->click('css=a[id^="component-grid-settings-plugins-settingsplugingrid-category-pubIds-row-DOIPubIdPlugin-settings-button"]');
		$this->waitForElementPresent('css=#doiSettingsForm .submitFormButton');
	}

	/**
	 * Configures the DOI prefix and suffix generation method
	 * and resets all DOIs so that the new rules will be applied.
	 * @param $disabled string an object type for which DOI generation should be disabled
	 * @param $prefix string
	 * @param $suffixGenerationMethod string
	 * @param $pattern string
	 */
	private function configureDoi($disabled = null, $prefix = '10.1234', $suffixGenerationMethod = 'doiSuffixDefault',
			$pattern = array('Issue' => '', 'Article' => '', 'Galley' => '')) {

		// Make sure the settings page is open.
		$this->openSettingsPage();

		// Check whether we have to change anything at all.
		foreach($this->objectTypes as $objectType) {
			$objectLocator = 'id=enable'.$objectType.'Doi';
			if ($objectType === $disabled) {
				$this->verifyNotChecked($objectLocator);
			} else {
				$this->verifyChecked($objectLocator);
			}
		}
		$this->verifyValue($this->pages['settings']['doiPrefix'], 'exact:'.$prefix);
		$this->verifyValue('id='.$suffixGenerationMethod, 'exact:on');
		if (!$this->verified()) {
			// Enable/Disable DOI generation.
			foreach($this->objectTypes as $objectType) {
				$objectLocator = 'id=enable'.$objectType.'Doi';
				if ($objectType === $disabled) {
					$this->uncheck($objectLocator);
				} else {
					$this->check($objectLocator);
				}
			}

			// Configure the prefix.
			$this->type($this->pages['settings']['doiPrefix'], $prefix);
			$this->click('id='.$suffixGenerationMethod);

			// Configure the suffix patterns.
			foreach ($pattern as $objectType => $suffixPattern) {
				$this->type(
					'css=input[id^="doi' . $objectType . 'SuffixPattern"]',
					$suffixPattern
				);
			}

			// Save settings.
			$this->submitAjaxForm('doiSettingsForm');
		}

		// Delete existing DOIs.
		$this->deleteExistingDois();
	}

	/**
	 * Delete all existing DOIs.
	 */
	private function deleteExistingDois() {
		$this->openSettingsPage();
		$this->click($this->pages['settings']['reassignDOIs']);
		$confirmationDivSelector = 'css=div:contains("Are you sure you wish to delete all existing DOIs?")';
		$this->waitForElementPresent($confirmationDivSelector);
		$this->click($confirmationDivSelector . ' button.ui-button');
		$this->waitForElementNotPresent($confirmationDivSelector);
	}

	/**
	 * Enable/Disable publisher IDs.
	 */
	private function configurePublisherIds($enabled = true) {
		// Enable publisher IDs for all objects.
		$this->open($this->getUrl('journal-setup', 4));
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
	 * Enter a custom suffix for the given object
	 * type and save the form. Make sure that the
	 * form does not produce an error.
	 * @param $objectType string
	 * @param $customSuffix string
	 */
	private function setCustomId($objectType, $customSuffix) {
		$metadataPage = $this->pages["metadata-$objectType"];
		$this->type($metadataPage['doiInput'], $customSuffix);
		$this->clickAndWait('css=input.button.defaultButton');
		$this->assertElementNotPresent(
			str_replace('%id', 'doiSuffix', $metadataPage['formError'])
		);
	}


	/**
	 * Check whether the given DOI appears on the object's page.
	 * @param $objectType string
	 * @param $expectedDoi string/boolean the expected DOI or false if
	 *    no DOI should be present.
	 */
	private function checkDoiDisplay($objectType, $expectedDoi) {
		$url = $this->getUrl($objectType, 1);
		$this->verifyAndOpen($url);
		if ($expectedDoi === false) {
			$visibleElement = $this->pages[$objectType]['visible'];
			if (strpos($objectType, 'citations') !== false) {
				$this->assertNotText($visibleElement, 'doi');
			} else {
				$this->assertElementNotPresent($visibleElement);
			}
			foreach (array('DC-meta', 'Google-meta') as $doiMetaAttribute) {
				if (isset($this->pages[$objectType][$doiMetaAttribute])) {
					$doiMetaElement = PKPString::regexp_replace(
						'/@[^@]+$/', '',
						$this->pages[$objectType][$doiMetaAttribute]
					);
					$this->assertElementNotPresent($doiMetaElement);
				}
			}
		} else {
			$expectedDoiPattern = "(^|.* )$expectedDoi($| .*)";
			$doiText = $this->getText($this->pages[$objectType]['visible']);
			if ($expectedDoi == '10.1234/t.v1i1.1') {
				$fata = 'morgana';
			}
			$this->assertText($this->pages[$objectType]['visible'], $expectedDoiPattern);
			foreach (array('DC-meta', 'Google-meta') as $doiMetaAttribute) {
				if (isset($this->pages[$objectType][$doiMetaAttribute])) {
					$this->assertAttribute(
						$this->pages[$objectType][$doiMetaAttribute],
						$expectedDoiPattern
					);
				}
			}
		}
	}

	/**
	 * Check DOI input and display on a single metadata page.
	 * @param $objectType string
	 * @param $editable boolean whether the DOI Suffix field should be editable.
	 * @param $expectedDoi string
	 */
	private function checkMetadataPage($objectType, $editable = false, $expectedDoi = null, $objectId = 1, $isPreview = false) {
		$objectType = strtolower_codesafe($objectType);
		$metadataPage = "metadata-$objectType";
		$url = $this->getUrl($metadataPage, $objectId);
		$this->verifyAndOpen($url);
		$doiText = $this->getText($this->pages[$metadataPage]['doi']);
		if ($editable) {
			if (!is_null($expectedDoi)) $this->assertValue($this->pages[$metadataPage]['doiInput'], $expectedDoi);
		} else {
			$this->assertElementNotPresent($this->pages[$metadataPage]['doiInput']);
			if (!is_null($expectedDoi)) $this->assertContains("DOI $expectedDoi", $doiText);
		}
		if ($isPreview) {
			$this->assertContains('What you see is a preview', $doiText);
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
		$this->assertElementPresent(
			str_replace('%id', 'doiSuffix', $this->pages[$metadataPage]['formError'])
		);
	}

	/**
	 * Reset to standard DOI settings.
	 */
	private function resetDoiSettings() {
		$this->openSettingsPage();
		$this->configureDoi();
	}

	/**
	 * Return an array with custom patterns
	 * for testing.
	 * @return array
	 */
	private function getCustomPatternArray() {
		return array(
			'Issue' => 'jor%j.%Y.vol%v',
			'Article' => 'jor%j.iss%i.art%a',
			'Galley' => 'jor%j.art%a.gal%g',
		);
	}
}

