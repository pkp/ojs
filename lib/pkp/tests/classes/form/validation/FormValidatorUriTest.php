<?php

/**
 * @file tests/classes/form/validation/FormValidatorUriTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorUriTest
 * @ingroup tests_classes_form_validation
 * @see FormValidatorUri
 *
 * @brief Test class for FormValidatorUri.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.form.Form');

class FormValidatorUriTest extends PKPTestCase {
	/**
	 * @covers FormValidatorUri
	 * @covers FormValidator
	 */
	public function testIsValid() {
		$form = new Form('some template');

		// test valid urls
		$form->setData('testUrl', 'http://some.domain.org/some/path?some=query#fragment');
		$validator = new FormValidatorUri($form, 'testUrl', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', array('http'));
		self::assertTrue($validator->isValid());

		$form->setData('testUrl', 'https://some.domain.org:8080');
		$validator = new FormValidatorUri($form, 'testUrl', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', array('https'));
		self::assertTrue($validator->isValid());

		$form->setData('testUrl', 'ftp://192.168.0.1/');
		$validator = new FormValidatorUri($form, 'testUrl', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertTrue($validator->isValid());

		// test invalid urls
		$form->setData('testUrl', 'gopher://some.domain.org/');
		$validator = new FormValidatorUri($form, 'testUrl', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', array('http'));
		self::assertFalse($validator->isValid());

		$form->setData('testUrl', 'http://some.domain.org/#frag1#frag2');
		$validator = new FormValidatorUri($form, 'testUrl', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', array('http'));
		self::assertFalse($validator->isValid());

		$form->setData('testUrl', 'http://256.168.0.1/');
		$validator = new FormValidatorUri($form, 'testUrl', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', array('http'));
		self::assertFalse($validator->isValid());
	}
}
?>
