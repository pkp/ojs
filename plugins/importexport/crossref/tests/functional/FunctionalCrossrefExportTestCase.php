<?php

/**
 * @file plugins/importexport/crossref/tests/functional/FunctionalCrossrefExportTest.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalCrossrefExportTest
 * @ingroup plugins_importexport_crossref_tests_functional
 *
 * @brief Test CrossRef export.
 */

import('lib.pkp.tests.functional.plugins.importexport.FunctionalImportExportBaseTestCase');

class FunctionalCrossrefExportTest extends FunctionalImportExportBaseTestCase {

	/**
	 * SCENARIO OUTLINE: Export article into CrossRef deposit format XML files
	 *   GIVEN a DOI has been assigned for a given {publishing object}
	 *    WHEN I export the corresponding article in CrossRef deposit format
	 *    THEN the object's DOI data (ID and URL) will be accessible in the
	 *         XML file at the correct {CrossRef XPath}. DOIs will not be exported
	 *         for galleys.
	 *
	 * EXAMPLES:
	 *   publishing object | CrossRef XPath
	 *   ==================|=====================================================
	 *   issue             | body/journal/journal_issue/doi_data/{doi|resource}
	 *   article           | body/journal/journal_article/doi_data/{doi|resource}
	 *                     |   component/{titles|doi_data/{doi|resource}}
	 */
	public function testDoi() {
		$export = $this->getXpathOnExport('CrossRefExportPlugin/exportArticle/1');
		$export->registerNamespace('cr', 'http://www.crossref.org/schema/4.3.0');

		$basePath = '/cr:doi_batch/cr:body/cr:journal';
		$testCases = array(
			'cr:journal_issue/cr:doi_data/cr:doi' => '10.1234/t.v1i1',
			'cr:journal_issue/cr:doi_data/cr:resource' => $this->baseUrl . '/index.php/test/issue/view/1',
			'cr:journal_article/cr:doi_data/cr:doi' => '10.1234/t.v1i1.1',
			'cr:journal_article/cr:doi_data/cr:resource' => $this->baseUrl . '/index.php/test/article/view/1',
			'cr:journal_article/cr:component_list/cr:component/cr:doi_data/cr:doi' => '10.1234/t.v1i1.1.s1',
		);
		foreach($testCases as $xPath => $expectedDoi) {
			self::assertEquals(
				$expectedDoi,
				$export->evaluate("string($basePath/$xPath)"),
				"Error while evaluating $xPath:"
			);
		}
	}
}

