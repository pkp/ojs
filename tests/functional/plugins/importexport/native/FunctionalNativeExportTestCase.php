<?php

/**
 * @file tests/functional/plugins/importexport/native/FunctionalNativeExportTest.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
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
		$testCases = array(
			'/issue/id[@type="doi"]' => '10.1234/t.v1i1',
			'/issue/id[@type="other::urn"]' => 'urn:nbn:de:0000-t.v1i19',
			'/issue/section/article[1]/id[@type="doi"]' => '10.1234/t.v1i1.1',
			'/issue/section/article[1]/id[@type="other::urn"]' => 'urn:nbn:de:0000-t.v1i1.18',
			'/issue/section/article[1]/galley[1]/id[@type="doi"]' => '10.1234/t.v1i1.1.g1',
			'/issue/section/article[1]/galley[1]/id[@type="other::urn"]' => 'urn:nbn:de:0000-t.v1i1.1.g17',
			'/issue/section/article[1]/supplemental_file[1]/id[@type="doi"]' => '10.1234/t.v1i1.1.s1',
			'/issue/section/article[1]/supplemental_file[1]/id[@type="other::urn"]' => 'urn:nbn:de:0000-t.v1i1.1.s19'
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
?>
