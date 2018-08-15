<?php

/**
 * @file plugins/generic/lucene/tests/functional/FunctionalLucenePluginCustomRankingTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginCustomRankingTest
 * @ingroup plugins_generic_lucene_tests_functional
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the custom ranking feature of
 * the lucene plug-in.
 *
 * FEATURE: custom ranking
 */

import('plugins.generic.lucene.tests.functional.FunctionalLucenePluginBaseTestCase');
import('plugins.generic.lucene.classes.SolrWebService');

class FunctionalLucenePluginCustomRankingTest extends FunctionalLucenePluginBaseTestCase {

	//
	// Implement template methods from WebTestCase
	//
	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array('plugin_settings');
	}


	//
	// Tests
	//
	/**
	 * SCENARIO: ranking weight selector
	 *   GIVEN I enabled the custom ranking feature
	 *    WHEN I go to the section editing page
	 *    THEN I see a drop down box with custom ranking factors:
	 *         "never show", "rank lower", "normal" and
	 *         "rank higher"
	 *     AND the ranking weight "normal" is selected by default.
	 */
	function testRankingWeightSelector() {
		// Enable the custom ranking feature.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'customranking', true);

		// Go to the section editing page.
		$this->logIn();
		$this->verifyAndOpen($this->baseUrl . '/index.php/lucene-test/manager/createSection');

		// Check that I see a drop down box with custom ranking factors.
		$rankingBoostOptions = $this->getSelectOptions('rankingBoostOption');
		self::assertEquals(
			array('Never Show', 'Rank Lower', 'Normal', 'Rank Higher'),
			$rankingBoostOptions
		);

		// Check that the ranking weight "normal" is selected by default.
		$defaultRankingBoost = $this->getSelectedLabel('rankingBoostOption');
		self::assertEquals('Normal', $defaultRankingBoost);
	}

	/**
	 * SCENARIO: ranking weight editing and effect
	 *   GIVEN I disabled the custom ranking feature
	 *     AND I executed a search that shows four articles from four
	 *         different sections with ranking weights such that their
	 *         ranking is uniquely defined as 1) "article 1", 2) "article 2",
	 *         3) "article 3", 4) "article 4" [e.g. '+ranking
	 *         +("article 1"^1.5 "article 2"^1.3 "article 3"^1.1
	 *         "article 4")']
	 *     AND I saved a ranking weight "rank lower" for the section
	 *         of article 1
	 *     AND I saved a ranking weight "normal" for the section
	 *         of article 2
	 *     AND I saved a ranking weight "rank higher" for the section
	 *         of article 3
	 *     AND I saved a ranking weight "never show" for the section
	 *         of article 4
	 *    WHEN I enable the custom ranking feature
	 *     AND I re-execute the exact same search
	 *    THEN I'll no longer see "article 4" in the result set
	 *     AND I'll see the ranking order of the remaining articles
	 *         reversed: 1) "article 3", 2) "article 2", 3) "article 1".
	 */
	function testRankingWeightEffect() {
		// Disable the custom ranking feature.
		$pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /* @var $pluginSettingsDao PluginSettingsDAO */
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'customranking', false);

		// Execute a search that shows four articles from four
		// different sections and check that they are presented
		// in the expected order.
		$this->simpleSearch('+ranking +("article 1"^1.5 "article 2"^1.3 "article 3" "article 4")');
		for ($article = 1, $row = 3; $article <= 4; $article++, $row += 4) {
			$articleTitle = $this->getTable("css=table.listing.$row.1");
			self::assertEquals("Ranking Test Article $article", $articleTitle);
		}

		// Enable the custom ranking feature.
		$pluginSettingsDao->updateSetting(0, 'luceneplugin', 'customranking', true);

		// Re-execute the same search.
		$this->simpleSearch('+ranking +("article 1"^1.5 "article 2"^1.3 "article 3" "article 4")');

		// Check that article 4 no longer is in the table;
		$listing = $this->getText('css=table.listing');
		$this->assertNotContains('Ranking Test Article 4', $listing);

		// Check that the ranking order of the remaining articles
		// was reversed.
		$this->simpleSearch('+ranking +("article 1"^1.5 "article 2"^1.3 "article 3" "article 4")');
		for ($article = 3, $row = 3; $article >= 1; $article--, $row += 4) {
			$articleTitle = $this->getTable("css=table.listing.$row.1");
			self::assertEquals("Ranking Test Article $article", $articleTitle);
		}

	}
}

