<?php

/**
 * @file tests/classes/form/validation/FormValidatorPostTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FormValidatorPostTest
 * @ingroup tests_classes_form_validation
 * @see FormValidatorPost
 *
 * @brief Test class for FormValidatorPost.
 */


require_mock_env('env1');

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.form.Form');
import('classes.core.Request'); // This will import the mock request

class FormValidatorPostTest extends PKPTestCase {
	/**
	 * @covers FormValidatorPost
	 * @covers FormValidator
	 */
	public function testIsValid() {
		// Instantiate test validator
		$form = new Form('some template');
		$validator = new FormValidatorPost($form, 'some.message.key');

		$this->markTestSkipped('Disabled for static invocation of Request.');

		Request::setRequestMethod('POST');
		self::assertTrue($validator->isValid());

		Request::setRequestMethod('GET');
		self::assertFalse($validator->isValid());
	}
}
?>
