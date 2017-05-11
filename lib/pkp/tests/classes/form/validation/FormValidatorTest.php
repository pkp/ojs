<?php

/**
 * @file tests/classes/form/validation/FormValidatorTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorTest
 * @ingroup tests_classes_form_validation
 * @see FormValidator
 *
 * @brief Test class for FormValidator.
 */


require_mock_env('env1');

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.form.Form');

class FormValidatorTest extends PKPTestCase {
	private
		$form;

	protected function setUp() {
		parent::setUp();
		$this->form = new Form('some template');
	}

	/**
	 * @covers FormValidator::__construct
	 * @covers FormValidator::getField
	 * @covers FormValidator::getForm
	 * @covers FormValidator::getValidator
	 * @covers FormValidator::getType
	 */
	public function testConstructor() {
		// Instantiate a test validator
		import('lib.pkp.classes.validation.ValidatorUrl');
		$validator = new ValidatorUrl();

		// Test CSS validation flags
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key');
		self::assertEquals(array('testData' => array()), $this->form->cssValidation);
		self::assertSame(FORM_VALIDATOR_OPTIONAL_VALUE, $formValidator->getType());

		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $validator);
		self::assertEquals(array('testData' => array('required')), $this->form->cssValidation);
		self::assertSame(FORM_VALIDATOR_REQUIRED_VALUE, $formValidator->getType());

		// Test getters
		self::assertSame('testData', $formValidator->getField());
		self::assertSame($this->form, $formValidator->getForm());
		self::assertSame($validator, $formValidator->getValidator());
	}

	/**
	 * @covers FormValidator::getMessage
	 */
	public function testGetMessage() {
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('##some.message.key##', $formValidator->getMessage());
	}

	/**
	 * @covers FormValidator::getFieldValue
	 */
	public function testGetFieldValue() {
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('', $formValidator->getFieldValue());

		$this->form->setData('testData', null);
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('', $formValidator->getFieldValue());

		$this->form->setData('testData', 0);
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('0', $formValidator->getFieldValue());

		$this->form->setData('testData', '0');
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('0', $formValidator->getFieldValue());

		$this->form->setData('testData', ' some text ');
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('some text', $formValidator->getFieldValue());

		$this->form->setData('testData', array(' some text '));
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame(array(' some text '), $formValidator->getFieldValue());
	}

	/**
	 * @covers FormValidator::isEmptyAndOptional
	 */
	public function testIsEmptyAndOptional() {
		// When the validation type is "required" then the method should return
		// false even if the given data field is empty.
		$this->form->setData('testData', '');
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertFalse($formValidator->isEmptyAndOptional());

		// If the validation type is "optional" but the given data field is not empty
		// then the method should also return false.
		$this->form->setData('testData', 'something');
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key');
		self::assertFalse($formValidator->isEmptyAndOptional());

		$this->form->setData('testData', array('something'));
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key');
		self::assertFalse($formValidator->isEmptyAndOptional());

		// When the validation type is "optional" and the value empty then return true
		$this->form->setData('testData', '');
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key');
		self::assertTrue($formValidator->isEmptyAndOptional());

		// Test border conditions
		$this->form->setData('testData', null);
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key');
		self::assertTrue($formValidator->isEmptyAndOptional());

		$this->form->setData('testData', 0);
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key');
		self::assertFalse($formValidator->isEmptyAndOptional());

		$this->form->setData('testData', '0');
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key');
		self::assertFalse($formValidator->isEmptyAndOptional());

		$this->form->setData('testData', array());
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key');
		self::assertTrue($formValidator->isEmptyAndOptional());
	}

	/**
	 * @covers FormValidator::isValid
	 */
	public function testIsValid() {
		// We don't need to test the case where a validator is set, this
		// is sufficiently tested by the other FormValidator* tests.

		// Test default validation (without internal validator set and optional values)
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key');
		self::assertTrue($formValidator->isValid());

		// Test default validation (without internal validator set and required values)
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertFalse($formValidator->isValid());

		$this->form->setData('testData', array());
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertFalse($formValidator->isValid());

		$this->form->setData('testData', 'some value');
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertTrue($formValidator->isValid());

		$this->form->setData('testData', array('some value'));
		$formValidator = new FormValidator($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertTrue($formValidator->isValid());
	}
}
?>
