<?php

/**
 * @file tests/classes/form/validation/FormValidatorArrayCustomTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorArrayCustomTest
 * @ingroup tests_classes_form_validation
 * @see FormValidatorArrayCustom
 *
 * @brief Test class for FormValidatorArrayCustom.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.form.Form');

class FormValidatorArrayCustomTest extends PKPTestCase {
	private
		$checkedValues = array(),
		$form,
		$subfieldValidation,
		$localeFieldValidation;

	protected function setUp() {
		parent::setUp();
		$this->form = new Form('some template');
		$this->subfieldValidation = array($this, 'userFunctionForSubfields');
		$this->localeFieldValidation = array($this, 'userFunctionForLocaleFields');
	}

	/**
	 * @covers FormValidatorArrayCustom
	 * @covers FormValidator
	 */
	public function testIsValidOptionalAndEmpty() {
		// Tests are completely bypassed when the validation type is
		// "optional" and the test data are empty. We make sure this is the
		// case by always returning 'false' for the custom validation function.
		$this->form->setData('testData', '');
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key', $this->subfieldValidation, array(false));
		self::assertTrue($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());
		self::assertSame(array(), $this->checkedValues);

		$this->form->setData('testData', array());
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key', $this->subfieldValidation, array(false));
		self::assertTrue($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());
		self::assertSame(array(), $this->checkedValues);

		// The data are valid when they contain only empty (sub-)sub-fields and the validation type is "optional".
		$this->form->setData('testData', array('subfield1' => array(), 'subfield2' => ''));
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key', $this->subfieldValidation, array(false));
		self::assertTrue($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());
		self::assertSame(array(), $this->checkedValues);

		$this->form->setData('testData', array('subfield1' => array('subsubfield1' => array(), 'subsubfield2' => '')));
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key', $this->subfieldValidation, array(false), false, array('subsubfield1', 'subsubfield2'));
		self::assertTrue($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());
		self::assertSame(array(), $this->checkedValues);
	}

	/**
	 * @covers FormValidatorArrayCustom
	 * @covers FormValidator
	 */
	public function testIsValidNoArray() {
		// Field data must be an array, otherwise validation fails
		$this->form->setData('testData', '');
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->subfieldValidation, array(true));
		self::assertFalse($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());
		self::assertSame(array(), $this->checkedValues);
	}

	/**
	 * Check all sub-fields (default behavior of isValid)
	 * @covers FormValidatorArrayCustom
	 * @covers FormValidator
	 */
	public function testIsValidCheckAllSubfields() {
		// Check non-locale data
		$this->form->setData('testData', array('subfield1' => 'abc', 'subfield2' => '0'));
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->subfieldValidation, array(true));
		self::assertTrue($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());
		self::assertSame(array('abc', '0'), $this->checkedValues);
		$this->checkedValues = array();

		// Check complement return
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->subfieldValidation, array(false), true);
		self::assertTrue($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());
		self::assertSame(array('abc', '0'), $this->checkedValues);
		$this->checkedValues = array();

		// Simulate invalid data (check function returns false)
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->subfieldValidation, array(false));
		self::assertFalse($validator->isValid());
		self::assertEquals(array('testData[subfield1]', 'testData[subfield2]'), $validator->getErrorFields());
		self::assertSame(array('abc', '0'), $this->checkedValues);
		$this->checkedValues = array();

		// Check locale data
		$this->form->setData('testData', array('en_US' => 'abc', 'de_DE' => 'def'));
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->localeFieldValidation, array(true), false, array(), true);
		self::assertTrue($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());
		self::assertSame(array('en_US' => array('abc'), 'de_DE' => array('def')), $this->checkedValues);
		$this->checkedValues = array();

		// Simulate invalid locale data
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->localeFieldValidation, array(false), false, array(), true);
		self::assertFalse($validator->isValid());
		self::assertEquals(array('en_US' => 'testData[en_US]', 'de_DE' => 'testData[de_DE]'), $validator->getErrorFields());
		self::assertSame(array('en_US' => array('abc'), 'de_DE' => array('def')), $this->checkedValues);
		$this->checkedValues = array();
	}

	/**
	 * Check explicitly given sub-sub-fields within all sub-fields
	 * @covers FormValidatorArrayCustom
	 * @covers FormValidator
	 */
	public function testIsValidCheckExplicitSubsubfields() {
		// Check non-locale data
		$testArray = array(
			'subfield1' => array('subsubfield1' => 'abc', 'subsubfield2' => 'def'),
			'subfield2' => array('subsubfield1' => '0', 'subsubfield2' => 0) // also test allowed boarder conditions
		);
		$this->form->setData('testData', $testArray);
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->subfieldValidation, array(true), false, array('subsubfield1', 'subsubfield2'));
		self::assertTrue($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());
		self::assertSame(array('abc', 'def', '0', 0), $this->checkedValues);
		$this->checkedValues = array();

		// Check complement return
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->subfieldValidation, array(false), true, array('subsubfield1', 'subsubfield2'));
		self::assertTrue($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());
		self::assertSame(array('abc', 'def', '0', 0), $this->checkedValues);
		$this->checkedValues = array();

		// Simulate invalid data (check function returns false)
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->subfieldValidation, array(false), false, array('subsubfield1', 'subsubfield2'));
		self::assertFalse($validator->isValid());
		$expectedErrors = array(
			'testData[subfield1][subsubfield1]', 'testData[subfield1][subsubfield2]',
			'testData[subfield2][subsubfield1]', 'testData[subfield2][subsubfield2]'
		);
		self::assertEquals($expectedErrors, $validator->getErrorFields());
		self::assertSame(array('abc', 'def', '0', 0), $this->checkedValues);
		$this->checkedValues = array();

		// Check locale data
		$testArray = array(
			'en_US' => array('subsubfield1' => 'abc', 'subsubfield2' => 'def'),
			'de_DE' => array('subsubfield1' => 'uvw', 'subsubfield2' => 'xyz')
		);
		$this->form->setData('testData', $testArray);
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->localeFieldValidation, array(true), false, array('subsubfield1', 'subsubfield2'), true);
		self::assertTrue($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());
		self::assertSame(array('en_US' => array('abc', 'def'), 'de_DE' => array('uvw', 'xyz')), $this->checkedValues);
		$this->checkedValues = array();

		// Simulate invalid locale data
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->localeFieldValidation, array(false), false, array('subsubfield1', 'subsubfield2'), true);
		self::assertFalse($validator->isValid());
		$expectedErrors = array(
			'en_US' => array(
				'testData[en_US][subsubfield1]', 'testData[en_US][subsubfield2]'
			),
			'de_DE' => array(
				'testData[de_DE][subsubfield1]', 'testData[de_DE][subsubfield2]'
			)
		);
		self::assertEquals($expectedErrors, $validator->getErrorFields());
		self::assertSame(array('en_US' => array('abc', 'def'), 'de_DE' => array('uvw', 'xyz')), $this->checkedValues);
		$this->checkedValues = array();
	}

	/**
	 * Check a few border conditions
	 * @covers FormValidatorArrayCustom
	 * @covers FormValidator
	 */
	public function testIsValidWithBorderConditions() {
		// Make sure that we get 'null' in the user function
		// whenever an expected field doesn't exist in the value array.
		$testArray = array(
			'subfield1' => array('subsubfield1' => null, 'subsubfield2' => ''),
			'subfield2' => array('subsubfield2' => 0)
		);
		$this->form->setData('testData', $testArray);
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->subfieldValidation, array(true), false, array('subsubfield1', 'subsubfield2'));
		self::assertTrue($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());
		self::assertSame(array(null, '', null, 0), $this->checkedValues);
		$this->checkedValues = array();

		// Pass in a one-dimensional array where a two-dimensional array is expected
		$testArray = array('subfield1' => 'abc', 'subfield2' => 'def');
		$this->form->setData('testData', $testArray);
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->subfieldValidation, array(true), false, array('subsubfield'));
		self::assertFalse($validator->isValid());
		self::assertEquals(array('testData[subfield1]', 'testData[subfield2]'), $validator->getErrorFields());
		self::assertSame(array(), $this->checkedValues);
		$this->checkedValues = array();

		// Pass in a one-dimensional locale array where a two-dimensional array is expected
		$testArray = array('en_US' => 'abc', 'de_DE' => 'def');
		$this->form->setData('testData', $testArray);
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->localeFieldValidation, array(true), false, array('subsubfield'), true);
		self::assertFalse($validator->isValid());
		self::assertEquals(array('en_US' => 'testData[en_US]', 'de_DE' => 'testData[de_DE]'), $validator->getErrorFields());
		self::assertSame(array(), $this->checkedValues);
		$this->checkedValues = array();
	}

	/**
	 * Check explicitly given sub-sub-fields within all sub-fields
	 * @covers FormValidatorArrayCustom::isArray
	 */
	public function testIsArray() {
		$this->form->setData('testData', array('subfield' => 'abc'));
		$validator = new FormValidatorArrayCustom($this->form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $this->subfieldValidation);
		self::assertTrue($validator->isArray());

		$this->form->setData('testData', 'field');
		self::assertFalse($validator->isArray());
	}

	/**
	 * This function is used as a custom validation callback for
	 * one-dimensional data fields.
	 * It simply reflects the additional argument so that we can
	 * easily manipulate its return value. The values passed in
	 * to this method are saved internally for later inspection.
	 * @param $value string
	 * @param $additionalArgument boolean
	 * @return boolean the value passed in to $additionalArgument
	 */
	public function userFunctionForSubfields($value, $additionalArgument) {
		$this->checkedValues[] = $value;
		return $additionalArgument;
	}

	/**
	 * This function is used as a custom validation callback for
	 * two-dimensional data fields.
	 * It simply reflects the additional argument so that we can
	 * easily manipulate its return value. The keys and values
	 * passed in to this method are saved internally for later
	 * inspection.
	 * @param $value string
	 * @param $key string
	 * @param $additionalArgument boolean
	 * @return boolean the value passed in to $additionalArgument
	 */
	public function userFunctionForLocaleFields($value, $key, $additionalArgument) {
		if (!isset($this->checkedValues[$key])) $this->checkedValues[$key] = array();
		$this->checkedValues[$key][] = $value;
		return $additionalArgument;
	}
}
?>
