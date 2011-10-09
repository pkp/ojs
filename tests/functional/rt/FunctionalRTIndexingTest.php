<?php

/**
 * @file tests/functional/rt/FunctionalRTIndexingTest.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalRTIndexingTest
 * @ingroup tests_functional_rt
 *
 * @brief Test the reading tools' indexing meta-data.
 */


import('lib.pkp.tests.WebTestCase');

class FunctionalRTIndexingTest extends WebTestCase {
	public function testDoi() {
		$this->open($this->baseUrl.'/index.php/test/rt/metadata/1/0');
		$this->assertText('//tr/td[text()="Digital Object Identifier"]/../td[last()]', 'exact:10.1234/t.v1i1.1');
	}
}
?>