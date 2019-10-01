<?php

/**
 * @file plugins/importexport/native/tests/functional/FunctionalNativeExportTest.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalNativeExportTest
 * @ingroup plugins_importexport_native_tests_functional
 *
 * @brief Test native OJS export.
 */

import('lib.pkp.tests.functional.plugins.importexport.FunctionalImportExportBaseTestCase');

class FunctionalNativeExportTest extends FunctionalImportExportBaseTestCase {

	public function testDoi() {
		$export = $this->getXpathOnExport('NativeImportExportPlugin/exportIssue/1');
		$testCases = array(
			'/issue/id[@type="doi"]' => '10.1234/t.v1i1',
			'/issue/id[@type="other::urn"]' => 'urn:nbn:de:0000-t.v1i19',
			'/issue/section/article[1]/id[@type="doi"]' => '10.1234/t.v1i1.1',
			'/issue/section/article[1]/id[@type="other::urn"]' => 'urn:nbn:de:0000-t.v1i1.18',
			'/issue/section/article[1]/galley[1]/id[@type="doi"]' => '10.1234/t.v1i1.1.g1',
			'/issue/section/article[1]/galley[1]/id[@type="other::urn"]' => 'urn:nbn:de:0000-t.v1i1.1.g17',
		);
		foreach($testCases as $xPath => $expectedDoi) {
			self::assertEquals(
				$expectedDoi,
				$export->evaluate("string($xPath)"),
				"Error while evaluating xPath for $expectedDoi:"
			);
		}
	}
}

