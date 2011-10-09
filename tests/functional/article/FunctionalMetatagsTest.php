<?php

/**
 * @file tests/functional/article/FunctionalMetatagsTest.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalMetatagsTest
 * @ingroup tests_functional_article
 *
 * @brief Test presence of meta-tags on the article abstract page.
 */


import('lib.pkp.tests.WebTestCase');

class FunctionalMetatagsTest extends WebTestCase {
	public function testDoi() {
		$this->open($this->baseUrl.'/index.php/test/article/view/1');
		foreach (array('DC.Identifier.DOI', 'citation_doi') as $metaElement) {
			$this->assertAttribute("//meta[@name='$metaElement']@content", 'exact:10.1234/t.v1i1.1');
		}
	}
}
?>