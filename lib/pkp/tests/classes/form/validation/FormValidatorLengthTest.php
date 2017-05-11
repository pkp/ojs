<?php

/**
 * @file tests/classes/form/validation/FormValidatorLengthTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorLengthTest
 * @ingroup tests_classes_form_validation
 * @see FormValidatorLength
 *
 * @brief Test class for FormValidatorLength.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.form.Form');

class FormValidatorLengthTest extends PKPTestCase {
	/**
	 * @covers FormValidatorLength
	 * @covers FormValidator
	 */
	public function testIsValid() {
		$form = new Form('some template');
		$form->setData('testData', 'test');

		// Encode the tests to be run against the validator
		$tests = array(
			array('==', 4, true),
			array('==', 5, false),
			array('==', 3, false),
			array('!=', 4, false),
			array('!=', 5, true),
			array('!=', 3, true),
			array('<', 5, true),
			array('<', 4, false),
			array('>', 3, true),
			array('>', 4, false),
			array('<=', 4, true),
			array('<=', 5, true),
			array('<=', 3, false),
			array('>=', 4, true),
			array('>=', 3, true),
			array('>=', 5, false),
			array('...', 3, false)
		);

		foreach($tests as $test) {
			$validator = new FormValidatorLength($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $test[0], $test[1]);
			self::assertSame($test[2], $validator->isValid());
		}

		// Test optional validation type
		$form->setData('testData', '');
		$validator = new FormValidatorLength($form, 'testData', FORM_VALIDATOR_OPTIONAL_VALUE, 'some.message.key', '==', 4);
		self::assertTrue($validator->isValid());
	}
}
?>
