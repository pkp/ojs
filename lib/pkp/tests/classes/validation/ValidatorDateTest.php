<?php

/**
 * @file tests/classes/validation/ValidatorDateTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ValidatorDateTest
 * @ingroup tests_classes_validation
 * @see ValidatorDate
 *
 * @brief Test class for ValidatorDate.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.validation.ValidatorDate');

class ValidatorDateTest extends PKPTestCase {
	/**
	 * @covers ValidatorDate
	 * @covers ValidatorRegExp
	 */
	public function testValidatorDate() {
		$validator = new ValidatorDate();
		self::assertTrue($validator->isValid('2010-05-14'));
		self::assertTrue($validator->isValid('2010-05'));
		self::assertTrue($validator->isValid('2010'));
		self::assertFalse($validator->isValid('2010-05-14', VALIDATOR_DATE_SCOPE_YEAR, VALIDATOR_DATE_SCOPE_MONTH)); // Must not resolve to a day
		self::assertTrue($validator->isValid('2010-05', VALIDATOR_DATE_SCOPE_YEAR, VALIDATOR_DATE_SCOPE_MONTH)); // Must not resolve to a day
		self::assertFalse($validator->isValid('2010', VALIDATOR_DATE_SCOPE_MONTH, VALIDATOR_DATE_SCOPE_DAY)); // Must resolve to a month or a day
		self::assertTrue($validator->isValid('2010-05', VALIDATOR_DATE_SCOPE_MONTH, VALIDATOR_DATE_SCOPE_DAY)); // Must resolve to a month or a day
		self::assertFalse($validator->isValid('2010-05', VALIDATOR_DATE_SCOPE_DAY, VALIDATOR_DATE_SCOPE_DAY)); // Must resolve to a day
		self::assertTrue($validator->isValid('2010-05-14', VALIDATOR_DATE_SCOPE_DAY, VALIDATOR_DATE_SCOPE_DAY)); // Must resolve to a day
		self::assertFalse($validator->isValid('2010-05-14', VALIDATOR_DATE_SCOPE_DAY, VALIDATOR_DATE_SCOPE_YEAR)); // Failed parameters: must be at least a day, but not greater than a year
		self::assertFalse($validator->isValid(''));
		self::assertFalse($validator->isValid('2010-00'));
		self::assertFalse($validator->isValid('2010-13'));
		self::assertFalse($validator->isValid('2010-05-00'));
		self::assertFalse($validator->isValid('2010-04-31'));
		self::assertTrue($validator->isValid('2008-02-29'));
		self::assertFalse($validator->isValid('2009-02-29'));
		self::assertFalse($validator->isValid('anything else'));
	}
}
?>
