<?php

/**
 * @file tests/functional/plugins/importexport/pubmed/FunctionalPubmedExportTest.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalPubmedExportTest
 * @ingroup tests_functional_plugins_importexport_pubmed
 *
 * @brief Test PubMed export.
 */


import('lib.pkp.tests.functional.plugins.importexport.FunctionalExportBaseTestCase');

class FunctionalPubmedExportTest extends FunctionalExportBaseTestCase {

	/**
	 * @see FunctionalExportBaseTestCase::getExportUrl()
	 */
	protected function getExportUrl() {
		return 'PubMedExportPlugin/exportArticle/1';
	}

	public function testDoi() {
		self::assertEquals('10.1234/t.v1i1.1', $this->xPath->evaluate('string(/ArticleSet/Article/ELocationID[@EIdType="doi"])'));
	}
}
?>