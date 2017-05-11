<?php

/**
 * @file tests/classes/form/validation/FormValidatorBooleanTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorBooleanTest
 * @ingroup tests_classes_form_validation
 * @see FormValidatorBoolean
 *
 * @brief Test class for FormValidatorBoolean.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.form.Form');

class FormValidatorBooleanTest extends PKPTestCase {
	/**
	 * @covers FormValidatorBoolean
	 * @covers FormValidator
	 */
	public function testIsValid() {
		$form = new Form('some template');

		// Instantiate test validator
		$validator = new FormValidatorBoolean($form, 'testData', 'some.message.key');

		$form->setData('testData', '');
		self::assertTrue($validator->isValid());

		$form->setData('testData', 'on');
		self::assertTrue($validator->isValid());

		$form->setData('testData', true);
		self::assertTrue($validator->isValid());

		$form->setData('testData', false);
		self::assertTrue($validator->isValid());

		$form->setData('testData', 'anything else');
		self::assertFalse($validator->isValid());
	}
}
?>
