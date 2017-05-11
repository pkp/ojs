<?php

/**
 * @file tests/classes/form/validation/FormValidatorUsernameTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorUsernameTest
 * @ingroup tests_classes_form_validation
 * @see FormValidatorUsername
 *
 * @brief Test class for FormValidatorUsername.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.form.Form');

class FormValidatorUsernameTest extends PKPTestCase {
	/**
	 * @covers FormValidatorUsername
	 * @covers FormValidator
	 */
	public function testIsValid() {
		$form = new Form('some template');

		// Allowed characters are a-z, 0-9, -, _. The characters - and _ are
		// not allowed at the start of the string.
		$form->setData('testData', 'a-z0123_bkj');
		$validator = new FormValidatorUsername($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertTrue($validator->isValid());

		// Test invalid strings
		$form->setData('testData', '-z0123_bkj');
		$validator = new FormValidatorUsername($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertFalse($validator->isValid());

		$form->setData('testData', 'abc#def');
		$validator = new FormValidatorUsername($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertFalse($validator->isValid());
	}
}
?>
