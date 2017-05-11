<?php

/**
 * @file tests/classes/form/validation/FormValidatorLocaleTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorLocaleTest
 * @ingroup tests_classes_form_validation
 * @see FormValidatorLocale
 *
 * @brief Test class for FormValidatorLocale.
 */


require_mock_env('env1');

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.form.Form');

class FormValidatorLocaleTest extends PKPTestCase {
	/**
	 * @covers FormValidatorLocale::getMessage
	 */
	public function testGetMessage() {
		$formValidator = new FormValidatorLocale($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('##some.message.key## (English)', $formValidator->getMessage());
	}

	/**
	 * @covers FormValidatorLocale::getFieldValue
	 */
	public function testGetFieldValue() {
		$form = new Form('some template');
		$formValidator = new FormValidatorLocale($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('', $formValidator->getFieldValue());

		$form->setData('testData', null);
		$formValidator = new FormValidatorLocale($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('', $formValidator->getFieldValue());

		$form->setData('testData', array('en_US' => null));
		$formValidator = new FormValidatorLocale($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('', $formValidator->getFieldValue());

		$form->setData('testData', array('en_US' => 0));
		$formValidator = new FormValidatorLocale($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('0', $formValidator->getFieldValue());

		$form->setData('testData', array('en_US' => '0'));
		$formValidator = new FormValidatorLocale($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('0', $formValidator->getFieldValue());

		$form->setData('testData', ' some text ');
		$formValidator = new FormValidatorLocale($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('', $formValidator->getFieldValue());

		$form->setData('testData', array('de_DE' => ' some text '));
		$formValidator = new FormValidatorLocale($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('', $formValidator->getFieldValue());

		$form->setData('testData', array('en_US' => ' some text '));
		$formValidator = new FormValidatorLocale($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame('some text', $formValidator->getFieldValue());

		$form->setData('testData', array('en_US' => array(' some text ')));
		$formValidator = new FormValidatorLocale($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key');
		self::assertSame(array(' some text '), $formValidator->getFieldValue());
	}
}
?>
