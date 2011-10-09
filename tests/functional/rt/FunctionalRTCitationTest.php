<?php

/**
 * @file tests/functional/rt/FunctionalRTCitationTest.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalRTCitationTest
 * @ingroup tests_functional_rt
 *
 * @brief Test reading tools' citation support.
 */


import('lib.pkp.tests.WebTestCase');

class FunctionalRTCitationTest extends WebTestCase {
	public function testApaDoiOutput() {
		$this->open($this->baseUrl.'/index.php/test/rt/captureCite/1/0/ApaCitationPlugin');
		$this->assertText('id=citation', 'doi:10.1234/t.v1i1.1');
	}
}
?>