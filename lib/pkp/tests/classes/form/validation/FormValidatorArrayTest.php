<?php

/**
 * @file tests/classes/form/validation/FormValidatorArrayTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorArrayTest
 * @ingroup tests_classes_form_validation
 * @see FormValidatorArray
 *
 * @brief Test class for FormValidatorArray.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.form.Form');

class FormValidatorArrayTest extends PKPTestCase {
	/**
	 * @covers FormValidatorArray
	 * @covers FormValidator
	 */
	public function testIsValid() {
		$form = new Form('some template');

		// Tests are completely bypassed when the validation type is "optional" values.
		$form->setData('testData', '');
		$validator = new FormValidatorArray($form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key');
		self::assertTrue($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());

		// Field data must be an array, otherwise validation fails
		$form->setData('testData', '');
		$validator = new FormValidatorArray($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertFalse($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());

		// We can either require all sub-fields (which is default)...
		$form->setData('testData', array('subfield1' => 'abc', 'subfield2' => '0'));
		$validator = new FormValidatorArray($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertTrue($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());

		$form->setData('testData', array('subfield1' => '', 'subfield2' => null));
		$validator = new FormValidatorArray($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertFalse($validator->isValid());
		self::assertEquals(array('testData[subfield1]', 'testData[subfield2]'), $validator->getErrorFields());

		// ..or the same explicit sub-sub-fields within all sub-fields.
		$testArray = array(
			'subfield1' => array('subsubfield1' => 'abc', 'subsubfield2' => 'def'),
			'subfield2' => array('subsubfield1' => '0', 'subsubfield2' => 0) // also test allowed boarder conditions
		);
		$form->setData('testData', $testArray);
		$validator = new FormValidatorArray($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', array('subsubfield1', 'subsubfield2'));
		self::assertTrue($validator->isValid());
		self::assertEquals(array(), $validator->getErrorFields());

		$testArray = array(
			'subfield1' => array('subsubfield1' => 'abc', 'subsubfield2' => 'def'),
			'subfield2' => array('subsubfield1' => '', 'subsubfield2' => 'xyz')
		);
		$form->setData('testData', $testArray);
		$validator = new FormValidatorArray($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', array('subsubfield1', 'subsubfield2'));
		self::assertFalse($validator->isValid());
		self::assertEquals(array('testData[subfield2][subsubfield1]'), $validator->getErrorFields());

		// Test border conditions...
		// ...pass in a one-dimensional array where a two-dimensional array is expected
		$testArray = array('subfield1' => 'abc', 'subfield2' => 'def');
		$form->setData('testData', $testArray);
		$validator = new FormValidatorArray($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', array('subsubfield'));
		self::assertFalse($validator->isValid());
		self::assertEquals(array('testData[subfield1]', 'testData[subfield2]'), $validator->getErrorFields());

		// ...pass in a two-dimensional array but leave out expected subsubfields
		$testArray = array(
			'subfield1' => array('subsubfield1' => 'abc', 'subsubfield2' => null),
			'subfield2' => array('subsubfield2' => 'xyz')
		);
		$form->setData('testData', $testArray);
		$validator = new FormValidatorArray($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', array('subsubfield1', 'subsubfield2'));
		self::assertFalse($validator->isValid());
		self::assertEquals(array('testData[subfield1][subsubfield2]', 'testData[subfield2][subsubfield1]'), $validator->getErrorFields());
	}
}
?>
