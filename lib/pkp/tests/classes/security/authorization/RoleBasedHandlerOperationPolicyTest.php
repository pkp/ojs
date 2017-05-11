<?php

/**
 * @file tests/classes/security/authorization/RoleBasedHandlerOperationPolicyTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RoleBasedHandlerOperationPolicyTest
 * @ingroup tests_classes_security_authorization
 * @see RoleBasedHandlerOperation
 *
 * @brief Test class for the RoleBasedHandlerOperation class
 */

import('lib.pkp.tests.classes.security.authorization.PolicyTestCase');
import('lib.pkp.classes.security.authorization.AuthorizationDecisionManager');
import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');

define('ROLE_ID_TEST_2', 0x8888);
define('ROLE_ID_NON_AUTHORIZED', 0x7777);
define('ROLE_ID_OCS_MANAGERIAL_ROLE', 0x6666);

class RoleBasedHandlerOperationPolicyTest extends PolicyTestCase {

	/**
	 * @covers RoleBasedHandlerOperationPolicy
	 */
	public function testRoleAuthorization() {
		// Construct the user roles array.
		$userRoles = array(ROLE_ID_SITE_ADMIN, ROLE_ID_TEST);

		// Test the user-group/role policy with a default
		// authorized request.
		$request = $this->getMockRequest('permittedOperation');
		$rolePolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
		$rolePolicy->addPolicy($this->getAuthorizationContextManipulationPolicy());
		$rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, array(ROLE_ID_TEST), 'permittedOperation'));
		$decisionManager = new AuthorizationDecisionManager();
		$decisionManager->addPolicy($rolePolicy);
		self::assertEquals(AUTHORIZATION_PERMIT, $decisionManager->decide());

		// Test the user-group/role policy with a non-authorized role.
		$rolePolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
		$rolePolicy->addPolicy($this->getAuthorizationContextManipulationPolicy());
		$rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_NON_AUTHORIZED, 'permittedOperation'));
		$decisionManager = new AuthorizationDecisionManager();
		$decisionManager->addPolicy($rolePolicy);
		self::assertEquals(AUTHORIZATION_DENY, $decisionManager->decide());

		// Test the policy with an authorized role but a non-authorized operation.
		$request = $this->getMockRequest('privateOperation');
		$rolePolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
		$rolePolicy->addPolicy($this->getAuthorizationContextManipulationPolicy());
		$rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_SITE_ADMIN, 'permittedOperation'));
		$decisionManager = new AuthorizationDecisionManager();
		$decisionManager->addPolicy($rolePolicy);
		self::assertEquals(AUTHORIZATION_DENY, $decisionManager->decide());

		// Test the "all roles must match" feature.
		$request = $this->getMockRequest('permittedOperation');
		$rolePolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
		$rolePolicy->addPolicy($this->getAuthorizationContextManipulationPolicy());
		$rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, array(ROLE_ID_SITE_ADMIN, ROLE_ID_TEST), 'permittedOperation', 'some.message', true));
		$decisionManager = new AuthorizationDecisionManager();
		$decisionManager->addPolicy($rolePolicy);
		self::assertEquals(AUTHORIZATION_PERMIT, $decisionManager->decide());

		// Test again the "all roles must match" feature but this time
		// with one role not matching.
		$rolePolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
		$rolePolicy->addPolicy($this->getAuthorizationContextManipulationPolicy());
		$rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, array(ROLE_ID_TEST, ROLE_ID_SITE_ADMIN, ROLE_ID_NON_AUTHORIZED), 'permittedOperation', 'some.message', true, false));
		$decisionManager = new AuthorizationDecisionManager();
		$decisionManager->addPolicy($rolePolicy);
		self::assertEquals(AUTHORIZATION_DENY, $decisionManager->decide());
	}
}
?>
