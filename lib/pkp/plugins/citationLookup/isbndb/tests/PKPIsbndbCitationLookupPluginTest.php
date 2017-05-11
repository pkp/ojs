<?php
/**
 * @defgroup plugins_citationLookup_isbndb_tests ISBNDB Plugin Test Suite
 */

/**
 * @file plugins/citationLookup/isbndb/tests/PKPIsbndbCitationLookupPluginTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPIsbndbCitationLookupPluginTest
 * @ingroup plugins_citationLookup_isbndb_tests
 * @see PKPIsbndbCitationLookupPlugin
 *
 * @brief Test class for PKPIsbndbCitationLookupPlugin.
 */


import('lib.pkp.tests.plugins.PluginTestCase');

class PKPIsbndbCitationLookupPluginTest extends PluginTestCase {
	/**
	 * @covers PKPIsbndbCitationLookupPlugin
	 */
	public function testIsbndbCitationLookupPlugin() {
		$this->markTestSkipped('ISBNDB API key daily limit too low.');

		if (!file_exists('plugins/citationLookup/isbndb/version.xml')) {
			$this->markTestSkipped('Plugin does not exist in application!');
		}

		// Delete the ISBNdb generic sequencer filter.
		$filterDao = DAORegistry::getDAO('FilterDAO'); /* @var $filterDao FilterDAO */
		$filterFactory = $filterDao->getObjectsByGroupAndClass('nlm30-element-citation=>nlm30-element-citation', 'lib.pkp.classes.filter.GenericSequencerFilter', 0, true);
		foreach($filterFactory->toArray() as $filter) {
			if ($filter->getDisplayName() == 'ISBNdb') $filterDao->deleteObject($filter);
		}

		$this->executePluginTest(
			'citationLookup',
			'isbndb',
			'IsbndbCitationLookupPlugin',
			array('nlm30-element-citation=>isbn', 'isbn=>nlm30-element-citation')
		);
	}
}
?>
