<?php

/**
 * @file tests/classes/form/validation/FormValidatorInSetTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorInSetTest
 * @ingroup tests_classes_form_validation
 * @see FormValidatorInSet
 *
 * @brief Test class for FormValidatorInSet.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.form.Form');

class FormValidatorInSetTest extends PKPTestCase {
	/**
	 * @covers FormValidatorInSet
	 * @covers FormValidator
	 */
	public function testIsValid() {
		$form = new Form('some template');

		// Instantiate test validator
		$acceptedValues = array('val1', 'val2');
		$validator = new FormValidatorInSet($form, 'testData', FORM_VALIDATOR_REQUIRED_VALUE, 'some.message.key', $acceptedValues);

		$form->setData('testData', 'val1');
		self::assertTrue($validator->isValid());

		$form->setData('testData', 'anything else');
		self::assertFalse($validator->isValid());
	}
}
?>
