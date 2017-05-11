<?php

/**
 * @file tests/classes/security/authorization/PKPProcessAccessPolicyTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPProcessAccessPolicyTest
 * @ingroup tests_classes_security_authorization
 * @see PKPProcessAccessPolicy
 *
 * @brief Test class for the PKPProcessAccessPolicy class
 */

import('lib.pkp.tests.classes.security.authorization.PolicyTestCase');
import('lib.pkp.classes.security.authorization.PKPProcessAccessPolicy');

class PKPProcessAccessPolicyTest extends PolicyTestCase {
	/**
	 * @covers PKPProcessAccessPolicy
	 */
	public function testPKPProcessAccessPolicy() {
		// Generate a test process.
		$processDao = DAORegistry::getDAO('ProcessDAO');
		$process = $processDao->insertObject(PROCESS_TYPE_CITATION_CHECKING, 1);
		self::assertInstanceOf('Process', $process);

		// Mock a request to a private method.
		$deniedRequest = $this->getMockRequest('privateOperation');

		// Generate a request argument array.
		$args = array('authToken' => $process->getId());

		// Instantiate the policy.
		$policy = new PKPProcessAccessPolicy($deniedRequest, $args, 'permittedOperation');

		// Test default message.
		self::assertEquals('user.authorization.processAuthenticationTokenRequired', $policy->getAdvice(AUTHORIZATION_ADVICE_DENY_MESSAGE));

		// Test the effect with an authorized process but with a private operation.
		self::assertEquals(AUTHORIZATION_DENY, $policy->effect());

		// Test the effect with an authorized process and a public operation.
		$permittedRequest = $this->getMockRequest('permittedOperation');
		$policy = new PKPProcessAccessPolicy($permittedRequest, $args, 'permittedOperation');
		self::assertEquals(AUTHORIZATION_PERMIT, $policy->effect());

		// Delete the process.
		$processDao->deleteObject($process);

		// Test the effect with an invalid authorization token.
		self::assertEquals(AUTHORIZATION_DENY, $policy->effect());

		// Test the effect without an authorization token.
		$args = array();
		$policy = new PKPProcessAccessPolicy($permittedRequest, $args, 'permittedOperation');
		self::assertEquals(AUTHORIZATION_DENY, $policy->effect());
	}
}
?>
