<?php

/**
 * @file tests/classes/form/validation/FormValidatorCustomTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorCustomTest
 * @ingroup tests_classes_form_validation
 * @see FormValidatorCustom
 *
 * @brief Test class for FormValidatorCustom.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.form.Form');

class FormValidatorCustomTest extends PKPTestCase {
	private
		$checkedValue;

	/**
	 * @covers FormValidatorCustom
	 * @covers FormValidator
	 */
	public function testIsValid() {
		$form = new Form('some template');
		$validationFunction = array($this, 'userValidationFunction');

		// Tests are completely bypassed when the validation type is
		// "optional" and the test field is empty. We make sure this is the
		// case by returning 'false' for the custom validation function.
		$form->setData('testData', '');
		$validator = new FormValidatorCustom($form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key', $validationFunction, array(false));
		self::assertTrue($validator->isValid());
		self::assertSame(null, $this->checkedValue);

		// Simulate valid data
		$form->setData('testData', 'xyz');
		$validator = new FormValidatorCustom($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $validationFunction, array(true));
		self::assertTrue($validator->isValid());
		self::assertSame('xyz', $this->checkedValue);

		// Simulate invalid data
		$form->setData('testData', 'xyz');
		$validator = new FormValidatorCustom($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $validationFunction, array(false));
		self::assertFalse($validator->isValid());
		self::assertSame('xyz', $this->checkedValue);

		// Simulate valid data with negation of the user function return value
		$form->setData('testData', 'xyz');
		$validator = new FormValidatorCustom($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $validationFunction, array(false), true);
		self::assertTrue($validator->isValid());
		self::assertSame('xyz', $this->checkedValue);
	}

	/**
	 * This function is used as a custom validation callback for
	 * fields.
	 * It simply reflects the additional argument so that we can
	 * easily manipulate its return value. The value passed in
	 * to this method is saved internally for later inspection.
	 * @param $value string
	 * @param $additionalArgument boolean
	 * @return boolean the value passed in to $additionalArgument
	 */
	public function userValidationFunction($value, $additionalArgument) {
		$this->checkedValue = $value;
		return $additionalArgument;
	}
}
?>
