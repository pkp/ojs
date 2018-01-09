<?php

/**
 * @file tests/functional/plugins/importexport/pubmed/FunctionalPubmedExportTest.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalPubmedExportTest
 * @ingroup tests_functional_plugins_importexport_pubmed
 *
 * @brief Test PubMed export.
 */


import('lib.pkp.tests.functional.plugins.importexport.FunctionalImportExportBaseTestCase');

class FunctionalPubmedExportTest extends FunctionalImportExportBaseTestCase {

	public function testDoi() {
		$export = $this->getXpathOnExport('PubMedExportPlugin/exportArticle/1');
		self::assertEquals('10.1234/t.v1i1.1', $export->evaluate('string(/ArticleSet/Article/ELocationID[@EIdType="doi"])'));
	}
}
?>
