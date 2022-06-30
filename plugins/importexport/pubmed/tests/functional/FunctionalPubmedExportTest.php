<?php

/**
 * @file plugins/importexport/pubmed/tests/functional/FunctionalPubmedExportTest.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FunctionalPubmedExportTest
 * @ingroup plugins_importexport_pubmed_tests_functional
 *
 * @brief Test PubMed export.
 */

namespace APP\plugins\importexport\pubmed\tests\functional;

use PKP\tests\PKPTestCase;

class FunctionalPubmedExportTest extends PKPTestCase
{
    public function testDoi()
    {
        $this->markTestSkipped('Broken test due to missing class FunctionalImportExportBaseTestCase');
        $export = $this->getXpathOnExport('PubMedExportPlugin/exportArticle/1');
        self::assertEquals('10.1234/t.v1i1.1', $export->evaluate('string(/ArticleSet/Article/ELocationID[@EIdType="doi"])'));
    }
}
