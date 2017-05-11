<?php

/**
 * @file tests/classes/validation/ValidatorUrlTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ValidatorUrlTest
 * @ingroup tests_classes_validation
 * @see ValidatorUrl
 *
 * @brief Test class for ValidatorUrl.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.validation.ValidatorUrl');

class ValidatorUrlTest extends PKPTestCase {
	/**
	 * @covers ValidatorUrl
	 * @covers ValidatorUri
	 * @covers ValidatorRegExp
	 * @covers Validator
	 */
	public function testValidatorUrlAndUri() {
		$validator = new ValidatorUrl();
		self::assertTrue($validator->isValid('ftp://some.download.com/'));
		self::assertTrue($validator->isValid('http://some.site.org/'));
		self::assertFalse($validator->isValid('gopher://another.site.org/'));
		self::assertFalse($validator->isValid('anything else'));
		self::assertTrue($validator->isValid('http://189.63.74.2/'));
		self::assertFalse($validator->isValid('http://257.63.74.2/'));
		self::assertFalse($validator->isValid('http://189.63.74.2.7/'));

		$validator = new ValidatorUri(array('gopher'));
		self::assertTrue($validator->isValid('gopher://another.site.org/'));
		self::assertFalse($validator->isValid('http://some.site.org/'));
		self::assertFalse($validator->isValid('anything else'));

		$validator = new ValidatorUri();
		self::assertTrue($validator->isValid('gopher://another.site.org/'));
		self::assertTrue($validator->isValid('http://some.site.org/'));
		self::assertFalse($validator->isValid('anything else'));

		self::assertEquals('&^(?:(http|https|ftp):)?(?://(?:((?:%[0-9a-f]{2}|[-a-z0-9_.!~*\'();:\&=+$,])*)@)?(?:((?:[a-z0-9](?:[-a-z0-9]*[a-z0-9])?\.)*[a-z](?:[a-z0-9]+)?\.?)|([0-9]{1,3}(?:\.[0-9]{1,3}){3}))(?::([0-9]*))?)((?:/(?:%[0-9a-f]{2}|[-a-z0-9_.!~*\'():@\&=+$,;])*)*/?)?(?:\?([^#]*))?(?:\#((?:%[0-9a-f]{2}|[-a-z0-9_.!~*\'();/?:@\&=+$,])*))?$&i', ValidatorUrl::getRegexp());
	}
}
?>
