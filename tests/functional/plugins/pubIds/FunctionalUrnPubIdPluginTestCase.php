<?php

/**
 * @file tests/functional/plugins/pubIds/FunctionalUrnPubIdPluginTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalUrnPubIdPluginTest
 * @ingroup tests_functional_plugins_pubIds
 *
 * @brief Test URN plug-in.
 *
 * FEATURE: URN support -- settings
 *   AS A    journal manager
 *   I WANT  to be able to define rules how URNs should be
 *           used (assigned, generated, displayed)
 *   SO THAT they can be correctly and uniquely used and identified.
 *
 *   AS AN   editor
 *   I WANT  to be able to use URNs according to the rules:
 *           to assign them to the publishing items, to generate
 *           them (if not autoamtically) and to see them in
 *           the metadata
 *   SO THAT I am able to easily manage them.
 *
 *   AS A    reader
 *   I WANT  to be able to see URNs
 *   SO THAT I can persistently use them.
 *
 * FIXME-BB: I think there is quite a bit of duplicate code between this class
 * and the FunctionalDOIPubIdPluginTest which can be resolved by creating a common
 * subclass for both or moving common code to a helper class.
 */


import('lib.pkp.tests.WebTestCase');

class FunctionalUrnPubIdPluginTest extends WebTestCase {
	private
		$pages,
		$objectTypes = array('Article', 'Issue', 'Galley'); // order is significant!

	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array(
			'journal_settings', 'issues', 'issue_settings',
			'published_submissions', 'submissions', 'submission_settings',
			'submission_galleys', 'submission_galley_settings',
			'plugin_settings'
		);
	}

	/**
	 * @see WebTestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();

		// OJS pages and Selenium locators required for test.
		// the setup page
		// there coul be several pages for a object type
		// metadata pages
		$this->pages = array(
			// journal setup
			'journal-setup' => array(
				'url' => $this->baseUrl.'/index.php/test/manager/setup/%id'
			),

			// URN plug-in settings
			'settings' => array(
				'url' => $this->baseUrl.'/index.php/test/manager/plugin/pubIds/URNPubIdPlugin/settings',
				'urnPrefix' => 'id=urnPrefix',
				'clearURNs' => 'css=a[id^="urnSettingsForm-reassignURNs-button"]',
				'formError' => '//ul[@class="pkp_form_error_list"]//a[@href="#%id"]'
			),

			// object view pages
			'issue' => array(
				array(
					'url' => $this->baseUrl.'/index.php/test/issue/view/%id',
					'visible' => '//a[@id="pub-id::other::urn"]'
				)
			),
			'article' => array(
				array(
					'url' => $this->baseUrl.'/index.php/test/article/view/%id',
					'visible' => '//a[@id="pub-id::other::urn"]',
					'DC-meta' => '//meta[@name="DC.Identifier.URN"]@content',
					'Google-meta' => '//meta[@name="citation_urn"]@content'
				),
				array(
					'url' => $this->baseUrl.'/index.php/test/rt/metadata/%id/0',
					'visible' => '//tr/td[text()="Uniform Resource Name"]/../td[last()]'
				)
			),
			'galley' => array(
				array(
					'url' => $this->baseUrl.'/index.php/test/article/view/%id/1',
					'visible' => '//a[@id="pub-id::other::urn"]',
					'DC-meta' => '//meta[@name="DC.Identifier.URN"]@content',
					'Google-meta' => '//meta[@name="citation_urn"]@content'
				)
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
		);

		// The meta-data pages have uniform structure.
		foreach ($this->objectTypes as $objectType) {
			$objectType = strtolower_codesafe($objectType);
			$this->pages["metadata-$objectType"] += array(
				'urn' => '//div[@id="pub-id::other::urn"]',
				'urnInput' => '//div[@id="pub-id::other::urn"]//input[@name="urnSuffix"]',
				'formError' => '//ul[@class="pkp_form_error_list"]//a[@href="#urnSuffix"]'
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
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown() {
		// Restart the session so that we get access
		// to Selenium to clean up our configuration.
		$this->start();

		// Explicitly stop Selenium otherwise our session
		// will not be freed for re-use.
		$this->stop();

		parent::tearDown();
	}


	//
	// BACKGROUND:
    //    GIVEN	I am a journal manager
    //      AND	I am on the setting page
    //

	/**
	 * SCENARIO: No prefix
	 *    GIVEN	I have specified no prefix
	 *     WHEN	I click on the "Save" button
	 *     THEN	I see the error message "Please enter the URN prefix."
	 *
	 *
	 * SCENARIO OUTLINE: No object
	 *    GIVEN	I have selected no {object type}
	 *     WHEN	I click on the "Save" button
	 *     THEN	I see the error message "Please choose the objects URNs should be assigned to."
	 *
	 * EXAMPLES:
	 *        | object type	|
	 *        =================
	 *        | issue     	|
	 *        | article   	|
	 *        | galley    	|
	 */
	public function testRequiredFields() {
		// Define no prefix
		$this->type($this->pages['settings']['urnPrefix'], '');
		// Disable URNs for all objects.
		foreach($this->objectTypes as $objectType) {
			$enableObject = 'id=enable'.$objectType.'URN';
			$this->uncheck($enableObject);
		}
		// Select no namespace
		$this->select('id=namespace', 'label=Choose');
		// Define no resolver
		$this->type('id=urnResolver', '');

		// Try to save settings.
		$this->submitAjaxForm('urnSettingsForm');

		// Now we should find the error messages
		$formPrefixError = str_replace('%id', "urnPrefix", $this->pages['settings']['formError']);
		$formObjectError = str_replace('%id', "enableIssueURN", $this->pages['settings']['formError']);
		$formNamespaceError = str_replace('%id', "namespace", $this->pages['settings']['formError']);
		$formResolverError = str_replace('%id', "urnResolver", $this->pages['settings']['formError']);
		$this->assertElementPresent($formPrefixError);
		$this->assertElementPresent($formObjectError);
		$this->assertElementPresent($formNamespaceError);
		$this->assertElementPresent($formResolverError);
	}

	/**
	 * SCENARIO: Valid prefix
	 *    GIVEN	I have specified a prefix that is not in the form
	 *     WHEN	I click on the "Save" button
	 *     THEN	I see the error message "The URN prefix pattern must be in the form 'urn:<NID>:<NSS>'."
	 */
	public function testValidPrefix() {
		// Define wrong prefix
		$this->type($this->pages['settings']['urnPrefix'], 'asdfg');
		// Try to save settings.
		$this->submitAjaxForm('urnSettingsForm');

		// Now we should find the error message
		$formError = str_replace('%id', "urnPrefix", $this->pages['settings']['formError']);
		$this->assertElementPresent($formError);
	}

	/**
	 * SCENARIO OUTLINE: No custom suffix pattern
	 *    GIVEN	I have selected an {object type}
	 *      AND	I have chosen the custom suffix pattern
	 *      AND	I have specified no custom suffix pattern for that object
	 *     WHEN	I click on the "Save" button
	 *     THEN	I see the error message "Please enter the URN suffix pattern for {object type}."
	 *
	 * EXAMPLES:
	 *        | object type	|
	 *        =================
	 *        | issue     	|
	 *        | article   	|
	 *        | galley    	|
	 */
	public function testRequiredCustomSuffixPatterns() {
		// Select the custom suffix pattern option.
		$this->click('id=urnSuffixPattern');

		// Make sure that URNs for all object types
		// are enabled but that their suffix patterns are empty.
		foreach ($this->objectTypes as $objectType) {
			$this->check("id=enable${objectType}URN");
			$this->type("id=urn${objectType}SuffixPattern", '');
		}

		// Try to save settings.
		$this->submitAjaxForm('urnSettingsForm');

		// Now we should find error messages for all four object
		// types.
		foreach ($this->objectTypes as $objectType) {
			$formError = str_replace(
				'%id', "urn${objectType}SuffixPattern",
				$this->pages['settings']['formError']
			);
			$this->assertElementPresent($formError);
		}
	}


	// BACKGROUND:
	//     GIVEN	I am a journal manager, editor and reader
	//       AND	I selected an object type
	//       AND	I specified the prefix "urn:nbn:de:0000-"
	//       AND	I decided that the check number should be used

	/**
	 *SCENARIO OUTLINE: Default suffix pattern
	 *    GIVEN	I have chosen {default pattern}
	 *     WHEN	I am on {editor/view and reader/generation page}
	 *     THEN	I see {URN} displayed on that page
	 *
	 * EXAMPLES:
	 *        | object type	| custom pattern	| editor/view and reader/generation page            	| URN                       	|
	 *        =============================================================================================================================
	 *        | issue     	| %j.v%vi%i     	| .../editor/issueData/1                            	| urn:nbn:de:0000-t.v1i19   	|
	 *        | issue     	| %j.v%vi%i     	| .../issue/view/1										| urn:nbn:de:0000-t.v1i19   	|
	 *        | article   	| %j.v%vi%i.%a  	| .../editor/viewMetadata/1                         	| urn:nbn:de:0000-t.v1i1.18 	|
	 *        | article   	| %j.v%vi%i.%a  	| .../article/view/1 + DC <meta>, Google <meta>			| urn:nbn:de:0000-t.v1i1.18 	|
	 *        | galley    	| %j.v%vi%i.%a.g%g	| .../editor/editGalley/1/1                         	| urn:nbn:de:0000-t.v1i1.1.g17	|
	 *        | galley    	| %j.v%vi%i.%a.g%g	| .../article/view/1 + DC <meta>, Google <meta>			| urn:nbn:de:0000-t.v1i1.1.g17	|
	 */
	public function testDefaultSuffixPattern() {
		// Enable URNs with default settings for all objects.
		$this->configureURN();

		// Check whether the expected display formats exist for
		// the various object types and whether the suffix is
		// correctly generated according to the default pattern + checkNo.
		$expectedURNs = array(
			'issue' => 'urn:nbn:de:0000-t.v1i19',
			'article' => 'urn:nbn:de:0000-t.v1i1.18',
			'galley' => 'urn:nbn:de:0000-t.v1i1.1.g17',
		);

		$this->checkViewPages($expectedURNs);
	}

	/**
	 * SCENARIO OUTLINE: Custom suffix pattern
	 *    GIVEN	I specified {custom pattern}
	 *     WHEN	I am on {editor/view and reader/generation page}
	 *     THEN	I see {URN} displayed on that page
	 *
	 * EXAMPLES:
	 *        | object type	| custom pattern    	| editor/view and reader/generation page               	| URN                           	|
	 *        =====================================================================================================================================
	 *        | issue     	| test.%j.v%vi%i    	| .../editor/issueData/1                            	| urn:nbn:de:0000-test.t.v1i16  	|
	 *        | issue     	| test.%j.v%vi%i    	| .../issue/view/1										| urn:nbn:de:0000-test.t.v1i16  	|
	 *        | article   	| test.%j.v%vi%i.%a 	| .../editor/viewMetadata/1                         	| urn:nbn:de:0000-test.t.v1i1.10	|
	 *        | article   	| test.%j.v%vi%i.%a 	| .../article/view/1 + DC <meta>, Google <meta>			| urn:nbn:de:0000-test.t.v1i1.10	|
	 *        | galley    	| test.%j.v%vi%i.%a.g%g	| .../editor/editGalley/1/1                         	| urn:nbn:de:0000-test.t.v1i1.1.g14	|
	 *        | galley    	| test.%j.v%vi%i.%a.g%g	| .../article/view/1/1 + DC <meta>, Google <meta>		| urn:nbn:de:0000-test.t.v1i1.1.g14	|
	 */
	public function testCustomSuffixPattern() {
		// Configure custom pattern.
		$customPattern = array(
			'Issue' => 'test.%j.v%vi%i',
			'Article' => 'test.%j.v%vi%i.%a',
			'Galley' => 'test.%j.v%vi%i.%a.g%g',
		);
		$this->configureURN('urn:nbn:de:0000-', 'urnSuffixPattern', $customPattern);

		// Check the results.
		$expectedURNs = array(
			'issue' => 'urn:nbn:de:0000-test.t.v1i16',
			'article' => 'urn:nbn:de:0000-test.t.v1i1.10',
			'galley' => 'urn:nbn:de:0000-test.t.v1i1.1.g14',
		);
		$this->checkViewPages($expectedURNs);
	}

	/**
	 * SCENARIO OUTLINE: Custom public URL ID as the URN suffix
	 *    GIVEN	I specified {custom public id}
	 *     WHEN	I am on {editor/view and reader/generation page}
	 *     THEN	I see {URN} displayed on that page
	 *
	 * EXAMPLES:
	 *        | object type	| custom public id    	| editor/view and reader/generation page               	| URN                           	|
	 *        =====================================================================================================================================
	 *        | issue     	| issueurl1    			| .../editor/issueData/1                            	| urn:nbn:de:0000-issueurl17  		|
	 *        | issue     	| issueurl1    			| .../issue/view/1										| urn:nbn:de:0000-issueurl17  		|
	 *        | issue     	| 		    			| .../editor/issueData/1                            	| urn:nbn:de:0000-i16		  		|
	 *        | issue     	| 		    			| .../issue/view/1										| urn:nbn:de:0000-i16		  		|
	 *        | article   	| articleurl1 			| .../editor/viewMetadata/1                         	| urn:nbn:de:0000-articleurl12		|
	 *        | article   	| articleurl1 			| .../article/view/1 + DC <meta>, Google <meta>			| urn:nbn:de:0000-articleurl12		|
	 *        | article   	| 			 			| .../editor/viewMetadata/1                         	| urn:nbn:de:0000-15				|
	 *        | article   	| 			 			| .../article/view/1 + DC <meta>, Google <meta>			| urn:nbn:de:0000-15				|
	 *        | galley    	| galleyurl1			| .../editor/editGalley/1/1                         	| urn:nbn:de:0000-galleyurl14		|
	 *        | galley    	| galleyurl1			| .../article/view/1/1 + DC <meta>, Google <meta>		| urn:nbn:de:0000-galleyurl14		|
	 *        | galley    	| 						| .../editor/editGalley/1/1                         	| urn:nbn:de:0000-g16				|
	 *        | galley    	| 						| .../article/view/1/1 + DC <meta>, Google <meta>		| urn:nbn:de:0000-g16				|
	 */
	public function testCustomPublicURL() {
		// Enable custom URL suffixes.
		$this->configureURN('urn:nbn:de:0000-', 'urnSuffixPublisherId');

		// Disable public URL IDs
		$this->configurePublisherIds(false);
		// Expected URNs.
		$expectedURNs = array(
			'issue' => 'urn:nbn:de:0000-i16',
			'article' => 'urn:nbn:de:0000-15',
			'galley' => 'urn:nbn:de:0000-g16',
		);
		$this->checkViewPages($expectedURNs);

		// Delete existing URNs.
		$this->deleteExistingURNs();

		// Enable public URL IDs
		$this->configurePublisherIds(true);
		// Specify public URL IDs
		$urlIds = array(
			'issue' => 'issueurl1',
			'article' => 'articleurl1',
			'galley' => 'galleyurl1',
		);
		$this->setUrlIds($urlIds);
		// Expected URNs.
		$expectedURNs = array(
			'issue' => 'urn:nbn:de:0000-issueurl17',
			'article' => 'urn:nbn:de:0000-articleurl12',
			'galley' => 'urn:nbn:de:0000-galleyurl14',
		);
		$this->checkViewPages($expectedURNs);
	}

	/**
	 * SCENARIO OUTLINE: Check number is missing
	 *    GIVEN	I have chosen custom suffix
	 *      AND URN for an item hasn't been generated yet
	 *     WHEN	I am on {editor/view page} for that object
	 *      AND	I specify the {custom suffix}
	 *      AND	I click on the "Save" button
	 *     THEN	I see the error message "Check number is missing."
	 *
	 * EXAMPLES:
	 *        | object type	| custom suffix	| editor/view page                                   	|
	 *         ======================================================================================
	 *        | issue     	| issue1    	| .../editor/issueData/1                            	|
	 *        | article   	| article1  	| .../editor/viewMetadata/1                         	|
	 *        | galley    	| galley1   	| .../editor/editGalley/1/1                         	|
	 *
	 * SCENARIO OUTLINE: Define a custom suffix
	 *    GIVEN	I have chosen custom suffix
	 *      AND URN for an item hasn't been generated yet
	 *     WHEN	I am on {editor/view page} for that object
	 *      AND	I specify the {custom suffix}
	 *      AND	I click on the button "Calculate Check Number"
	 *     THEN	I see the calculated check number automaticaly added to that custom suffix and {new custom suffix} displayed
	 *
	 * EXAMPLES:
	 *        | object type	| custom suffix	| editor/view page                                   	| new custom suffix	|
	 *         =============================================================================================================
	 *        | issue     	| issue1    	| .../editor/issueData/1                            	| issue14       	|
	 *        | article   	| article1  	| .../editor/viewMetadata/1                         	| article13     	|
	 *        | galley    	| galley1   	| .../editor/editGalley/1/1                         	| galley18      	|
	 *
	 * SCENARIO OUTLINE: Custom suffix
	 *    GIVEN	I specified {custom suffix}
	 *      AND the URN for that object was generated
	 *     WHEN	I am on {editor/view and reader/generation page}
	 *     THEN	I see {URN} displayed on that page
	 *
	 * EXAMPLES:
	 *        | object type	| custom suffix	| editor/view and reader/generation page            	| URN                       	|
	 *        =========================================================================================================================
	 *        | issue     	| issue14   	| .../editor/issueData/1                            	| urn:nbn:de:0000-issue14   	|
	 *        | issue     	| issue14   	| .../issue/view/1										| urn:nbn:de:0000-issue14   	|
	 *        | article   	| article13 	| .../editor/viewMetadata/1                         	| urn:nbn:de:0000-article13 	|
	 *        | article   	| article13 	| .../article/view/1 + DC <meta>, Google <meta>			| urn:nbn:de:0000-article13 	|
	 *        | galley    	| galley18  	| .../editor/editGalley/1/1                         	| urn:nbn:de:0000-galley18  	|
	 *        | galley    	| galley18  	| .../article/view/1 + DC <meta>, Google <meta>			| urn:nbn:de:0000-galley18  	|
	 *
	 *
	 * SCENARIO OUTLINE: Duplicated custom suffix
	 *    GIVEN	the {URN} was generated for a galley object
	 *     WHEN	I am on {editor/view and reader/generation page} of another object
	 *      AND	I specify the {custom suffix}
	 *      AND	I click on the "Save" button
	 *     THEN	I see the error message "The given URN suffix is already in use for another published item. Please enter a unique URN suffix for each item."
	 *
	 * EXAMPLES:
	 *        | URN							| custom suffix	| editor/view page                                   	|
	 *        =========================================================================================================
	 *        | urn:nbn:de:0000-galley18   	| galley18  	| .../editor/viewMetadata/2                         	|
	 */
	public function testCustomSuffix() {
		// Change the suffix generation method.
		$this->configureURN('urn:nbn:de:0000-', 'urnSuffixCustomIdentifier');

		// checkNo for the custom suffix
		$objectCheckNo = array('issue'=>'4', 'article'=>'3', 'galley'=>'8');

		$existingSuffix = null;
		foreach ($this->objectTypes as $objectType) {
			$objectType = strtolower_codesafe($objectType);

			// An input field should be present on all meta-data pages
			// as long as no URN hasn't been stored.
			$this->checkMetadataPage($objectType, $editable = true);

			// Let's enter a custom URN.
			$metadataPage = $this->pages["metadata-$objectType"];
			$customSuffix = "${objectType}1";
			$customSuffixWithCheckNo = "${objectType}1".$objectCheckNo[$objectType];
			$this->type($metadataPage['urnInput'], $customSuffix);

			// Try to save metadata.
			// Check number is missing message.
			$this->clickAndWait('css=input.button.defaultButton');
			$this->assertElementPresent($metadataPage['formError']);

			// Calculate the check number.
			$this->click('name=checkNo');
			$this->assertValue($metadataPage['urnInput'], $customSuffixWithCheckNo);
			// Try to save metadata.
			$this->clickAndWait('css=input.button.defaultButton');
			$this->assertElementNotPresent($metadataPage['formError']);

			// Check whether the custom URN has been correctly generated.
			$expectedURN = "urn:nbn:de:0000-$customSuffixWithCheckNo";
			$this->checkURNDisplay($objectType, $expectedURN);

			// As soon as the URN has been generated the meta-data field
			// should no longer be editable and the generated URN should
			// be displayed instead.
			$this->checkMetadataPage($objectType, $editable = false, $expectedURN);
		}

		// duplicate custom suffix
		$metadataPage = $this->pages["metadata-article"];
		$this->open($this->getUrl($metadataPage, 2));
		$doubleCustomSuffix = 'galley18';
		$this->type($metadataPage['urnInput'], $doubleCustomSuffix);
		// Try to save metadata.
		$this->clickAndWait('css=input.button.defaultButton');
		$this->assertElementPresent($metadataPage['formError']);
	}

	/**
	 * SCENARIO: Change a setting
	 *     GIVEN	a URN is generated for an object
	 *      WHEN	I change a setting (object type, prefix, suffix generation, check number),
	 *     			e.g. choose another way the suffixes shoulg be generated
	 *      THEN	the URN for that object won't change
	 *
	 * SCENARIO: Change a setting and reasign
	 *     GIVEN	a URN is generated for an object
	 *      WHEN	I change a setting (object type, prefix, suffix generation, check number),
	 *     			e.g. choose another way the suffixes shoulg be generated
	 *       AND	I press the button "Reasing URNs"
	 *      THEN	I see the changed URN for that object
	 */
	public function testSettingChanges() {
		// Configure URN defaults and delete all existing URNs.
		$this->configureURN();

		// Generate a test URN.
		$this->checkURNDisplay('article', 'urn:nbn:de:0000-t.v1i1.1');

		// Change and save a URN setting without deleting URNs.
		$this->openSettingsPage();
		$this->type('id=urnPrefix', 'urn:nbn:de:0001-');
		$this->submitAjaxForm('urnSettingsForm');

		// Check that the URN didn't change.
		$this->checkURNDisplay('article', 'urn:nbn:de:0000-t.v1i1.1');

		// Check that only deleting the URNs will actually
		// re-assign a new URN.
		$this->deleteExistingURNs();
		$this->checkURNDisplay('article', 'urn:nbn:de:0001-t.v1i1.1');

		// Configure URN defaults and delete all existing URNs.
		$this->configureURN();

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
	private function getUrl($page, $articleId = null) {
		return str_replace('%id', (string) $articleId, $page['url']);
	}

	/**
	 * Open the settings page
	 */
	private function openSettingsPage() {
		$this->verifyAndOpen($this->baseUrl.'/index.php/test/manager/plugins');
		$this->waitForElementPresent('css=tr.elementURNPubIdPlugin');
		$this->click('css=a[id^="component-grid-settings-plugins-settingsplugingrid-category-pubIds-row-URNPubIdPlugin-settings-button"]');
		$this->waitForElementPresent('css=#urnSettingsForm .button');
	}

	/**
	 * Configures the URN prefix and suffix generation method
	 * and resets all URNs so that the new rules will be applied.
	 * @param $prefix string
	 * @param $suffixGenerationMethod string
	 * @param $pattern string
	 */
	private function configureURN($prefix = 'urn:nbn:de:0000-', $suffixGenerationMethod = 'urnSuffixDefault',
			$pattern = array('Issue' => '', 'Article' => '', 'Galley' => ''), $resolver = 'http://nbn-resolving.de/') {

		// Make sure the settings page is open.
		$this->openSettingsPage();

		// Check whether we have to change anything at all.
		foreach($this->objectTypes as $objectType) {
			$enableObject = 'id=enable'.$objectType.'URN';
			$this->verifyChecked($enableObject);
		}
		$this->verifyValue($this->pages['settings']['urnPrefix'], 'exact:'.$prefix);
		$this->verifyValue('id='.$suffixGenerationMethod, 'exact:on');
		$this->verifyChecked('id=checkNo');
		$this->verifyNotSelectedValue('id=namespace', '');
		$this->verifyValue('id=urnResolver', 'exact:'.$resolver);

		if (!$this->verified()) {
			// Enable/Disable URN generation.
			foreach($this->objectTypes as $objectType) {
				$enableObject = 'id=enable'.$objectType.'URN';
				$this->check($enableObject);
			}
			// Configure the prefix.
			$this->type($this->pages['settings']['urnPrefix'], $prefix);
			// Suffix generation method
			$this->click('id='.$suffixGenerationMethod);
			// Cehck number
			$this->check('id=checkNo');
			// Select no namespace
			$this->select('id=namespace', 'value=urn:nbn:de');
			// Configure the resolver.
			$this->type('id=urnResolver', $resolver);
			// Configure the suffix patterns.
			foreach ($pattern as $objectType => $suffixPattern) {
				$this->type(
					"id=urn${objectType}SuffixPattern",
					$suffixPattern
				);
			}
			// Save settings.
			$this->submitAjaxForm('urnSettingsForm');

			// Delete existing URNs.
			$this->deleteExistingURNs();
		}
	}

	/**
	 * Enable/Disable publisher IDs.
	 */
	private function configurePublisherIds($enabled = true) {
		// Enable publisher IDs for all objects.
		$this->open($this->getUrl($this->pages['journal-setup'], 4));
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
	private function setUrlIds($urlIds) {
		foreach ($urlIds as $objectType => $urlId) {
			$metadataPage = $this->pages["metadata-$objectType"];
			if (isset($metadataPage['urlSuffixPage'])) {
				$url = $metadataPage['urlSuffixPage'];
			} else {
				$url = $metadataPage['url'];
			}
			$this->open(str_replace('%id', '1', $url));
			$this->type($metadataPage['urlSuffix'], $urlId);
			$this->clickAndWait('css=input.button.defaultButton');
			if ($objectType == 'article') {
				$this->assertConfirmation('Save changes to table of contents?');
			}
		}
	}

	/**
	 * Delete all existing URNs.
	 */
	private function deleteExistingURNs() {
		$this->openSettingsPage();
		$this->click($this->pages['settings']['clearURNs']);
		$confirmationDivSelector = 'css=div:contains("Are you sure you wish to delete all existing URNs?")';
		$this->waitForElementPresent($confirmationDivSelector);
		$this->click($confirmationDivSelector . ' button.ui-button');
		$this->waitForElementNotPresent($confirmationDivSelector);
	}

	/**
	 * Check URN view on all pages.
	 * @param $expectedURNs string
	 */
	private function checkViewPages($expectedURNs) {
		foreach ($expectedURNs as $objectType => $expectedURN) {
			$this->checkURNDisplay($objectType, $expectedURN);
			$this->checkMetadataPage($objectType, $editable = false, $expectedURN);
		}
	}
	/**
	 * Check whether the given URN appears on the object's page.
	 * @param $objectType string
	 * @param $expectedURN string/boolean the expected URN or false if
	 *    no URN should be present.
	 */
	private function checkURNDisplay($objectType, $expectedURN) {
		foreach ($this->pages[$objectType] as $page) {
			$this->verifyLocation('exact:'.$this->getUrl($page, 1));
			if (!$this->verified()) {
				$this->open($this->getUrl($page, 1));
			}
			try {
				if(isset($page['visible'])) {
					$this->assertText($page['visible'], $expectedURN);
				}
				foreach (array('DC-meta', 'Google-meta') as $urnMetaAttribute) {
					if (isset($page[$urnMetaAttribute])) {
						$this->assertAttribute($page[$urnMetaAttribute], $expectedURN);
					}
				}
			}
		}
	}

	/**
	 * Check URN input and display on a single metadata page.
	 * @param $objectType string
	 * @param $editable boolean whether the URN Suffix field should be editable.
	 * @param $expectedURN string
	 */
	private function checkMetadataPage($objectType, $editable = false, $expectedURN = null) {
		try {
			$objectType = strtolower_codesafe($objectType);
			$metadataPage = "metadata-$objectType";
			$this->verifyLocation('exact:'.$this->getUrl($this->pages[$metadataPage], 1));
			if (!$this->verified()) {
				$this->open($this->getUrl($this->pages[$metadataPage], 1));
			}
			if ($editable) {
				$this->assertElementPresent($this->pages[$metadataPage]['urnInput']);
			} else {
				$this->assertElementNotPresent($this->pages[$metadataPage]['urnInput']);
				$this->assertText($this->pages[$metadataPage]['urn'], $expectedURN);
			}
		}
	}

	/**
	 * Check whether the given meta-data page denies
	 * entering an existing URN suffix across all object
	 * types.
	 * @param $objectType string
	 * @param $objectId string
	 * @param $existingSuffix string
	 */
	/*
	private function checkDuplicateURNSuffixError($objectType, $objectId, $existingSuffix) {
		// We should get an error when trying to enter an already existing
		// suffix for another object.
		$metadataPage = "metadata-$objectType";
		$this->open($this->getUrl($metadataPage, $objectId));
		$this->type($this->pages[$metadataPage]['urnInput'], $existingSuffix);
		$this->clickAndWait('css=input.button.defaultButton');
		$this->assertElementPresent($this->pages[$metadataPage]['formError']);
	}
	*/

}

