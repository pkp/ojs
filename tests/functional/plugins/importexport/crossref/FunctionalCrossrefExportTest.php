<?php

/**
 * @file tests/functional/plugins/importexport/crossref/FunctionalCrossrefExportTest.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalCrossrefExportTest
 * @ingroup tests_functional_plugins_importexport_crossref
 *
 * @brief Test CrossRef export.
 */


import('lib.pkp.tests.functional.plugins.importexport.FunctionalImportExportBaseTestCase');

class FunctionalCrossrefExportTest extends FunctionalImportExportBaseTestCase {

	/**
	 * SCENARIO: Export article into CrossRef deposit format XML files
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
	 *   supp-file         | body/journal/journal_article/component_list/
	 *                     |   component/{titles|doi_data/{doi|resource}}
	 */
	public function testDoi() {
		$export = $this->getXpathOnExport('CrossRefExportPlugin/exportArticle/1');
		self::assertEquals('10.1234/t.v1i1.1', $export->evaluate('string(/cr:doi_batch/cr:body/cr:journal/cr:journal_article/cr:doi_data/cr:doi)'));

		$this->markTestIncomplete('Export article into CrossRef deposit format XML files');
	}
}
?>