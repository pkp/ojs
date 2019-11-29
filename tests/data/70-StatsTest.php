<?php

/**
 * @file tests/data/70-StatsTest.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StatsTest
 * @ingroup tests_data
 *
 * @brief Data build suite: Create stats
 */

import('lib.pkp.tests.data.PKPStatsTest');

use Facebook\WebDriver\Interactions\WebDriverActions;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverBy;

class StatsTest extends PKPStatsTest {
	/**
	 * Configure article usage stats
	 */
	function testPublicationStats() {
		$this->generateUsageStats();
		$this->goToStats('dbarnes', 'dbarnesdbarnes', 'Preprints');
		$this->checkGraph(
			'Total abstract views by date',
			'Abstract Views',
			'Files',
			'Total file views by date',
			'File Views'
		);
		/** FIXME: Needs published items!
		$this->checkTable(
			'Preprint Details',
			'articles',
			['Mwandenga', 'Karbasizaed']
		);
		*/
		$this->checkFilters([
			'Preprints',
			'Reviews',
		]);
		$this->logOut();
	}
}
