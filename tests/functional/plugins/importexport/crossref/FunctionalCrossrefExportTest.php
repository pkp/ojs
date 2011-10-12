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
	public function testDoi() {
		$export = $this->getXpathOnExport('CrossRefExportPlugin/exportArticle/1');
		self::assertEquals('10.1234/t.v1i1.1', $export->evaluate('string(/cr:doi_batch/cr:body/cr:journal/cr:journal_article/cr:doi_data/cr:doi)'));
	}
}
?>