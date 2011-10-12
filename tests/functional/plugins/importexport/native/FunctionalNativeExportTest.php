<?php

/**
 * @file tests/functional/plugins/importexport/native/FunctionalNativeExportTest.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalNativeExportTest
 * @ingroup tests_functional_plugins_importexport_native
 *
 * @brief Test native OJS export.
 */


import('lib.pkp.tests.functional.plugins.importexport.FunctionalImportExportBaseTestCase');

class FunctionalNativeExportTest extends FunctionalImportExportBaseTestCase {

	public function testDoi() {
		$export = $this->getXpathOnExport('NativeImportExportPlugin/exportIssue/1');
		self::assertEquals('10.1234/t.v1i1.1', $export->evaluate('string(/article/id[@type="doi"])'));
	}
}
?>