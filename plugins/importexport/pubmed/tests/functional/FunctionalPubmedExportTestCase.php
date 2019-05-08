<?php

/**
 * @file plugins/importexport/pubmed/tests/functional/FunctionalPubmedExportTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalPubmedExportTest
 * @ingroup plugins_importexport_pubmed_tests_functional
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

