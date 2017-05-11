<?php

/**
 * @file tests/classes/form/validation/FormValidatorLocaleEmailTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorLocaleEmailTest
 * @ingroup tests_classes_form_validation
 * @see FormValidatorLocaleEmail
 *
 * @brief Test class for FormValidatorLocaleEmail.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.form.Form');

class FormValidatorLocaleEmailTest extends PKPTestCase {
	/**
	 * @covers FormValidatorLocaleEmail
	 * @covers FormValidatorLocale
	 * @covers FormValidator
	 */
	public function testIsValid() {
		$form = new Form('some template');

		$form->setData('testData', array('en_US' => 'some.address@gmail.com'));
		$validator = new FormValidatorLocaleEmail($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertTrue($validator->isValid());

		$form->setData('testData', 'some.address@gmail.com');
		$validator = new FormValidatorLocaleEmail($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertFalse($validator->isValid());

		$form->setData('testData', array('en_US' => 'anything else'));
		$validator = new FormValidatorLocaleEmail($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertFalse($validator->isValid());
	}
}
?>
