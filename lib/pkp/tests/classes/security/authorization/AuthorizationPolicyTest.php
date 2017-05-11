<?php

/**
 * @file tests/classes/security/authorization/AuthorizationPolicyTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorizationPolicyTest
 * @ingroup tests_classes_security_authorization
 * @see AuthorizationPolicy
 *
 * @brief Test class for AuthorizationPolicy
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class AuthorizationPolicyTest extends PKPTestCase {
	/**
	 * @covers AuthorizationPolicy
	 */
	public function testAuthorizationPolicy() {
		$policy = new AuthorizationPolicy('some message');

		// Test advice.
		self::assertTrue($policy->hasAdvice(AUTHORIZATION_ADVICE_DENY_MESSAGE));
		self::assertFalse($policy->hasAdvice(AUTHORIZATION_ADVICE_CALL_ON_DENY));
		self::assertEquals('some message', $policy->getAdvice(AUTHORIZATION_ADVICE_DENY_MESSAGE));
		self::assertNull($policy->getAdvice(AUTHORIZATION_ADVICE_CALL_ON_DENY));

		// Test authorized context objects.
		self::assertFalse($policy->hasAuthorizedContextObject(ASSOC_TYPE_USER_GROUP));
		$someContextObject = new DataObject();
		$someContextObject->setData('test1', 'test1');
		$policy->addAuthorizedContextObject(ASSOC_TYPE_USER_GROUP, $someContextObject);
		self::assertTrue($policy->hasAuthorizedContextObject(ASSOC_TYPE_USER_GROUP));
		self::assertEquals($someContextObject, $policy->getAuthorizedContextObject(ASSOC_TYPE_USER_GROUP));
		self::assertEquals(array(ASSOC_TYPE_USER_GROUP => $someContextObject), $policy->getAuthorizedContext());

		// Test authorized context.
		$someOtherContextObject = new DataObject();
		$someOtherContextObject->setData('test2', 'test2');
		$authorizedContext = array(ASSOC_TYPE_USER_GROUP => $someOtherContextObject);
		$policy->setAuthorizedContext($authorizedContext);
		self::assertEquals($authorizedContext, $policy->getAuthorizedContext());

		// Test default policies.
		self::assertTrue($policy->applies());
		self::assertEquals(AUTHORIZATION_DENY, $policy->effect());
	}
}
?>
