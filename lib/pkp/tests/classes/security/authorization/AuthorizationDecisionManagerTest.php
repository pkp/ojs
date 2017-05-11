<?php

/**
 * @file tests/classes/security/authorization/AuthorizationDecisionManagerTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorizationDecisionManagerTest
 * @ingroup tests_classes_security_authorization
 * @see AuthorizationDecisionManager
 *
 * @brief Test class for the AuthorizationDecisionManager class
 */

import('lib.pkp.tests.classes.security.authorization.PolicyTestCase');
import('lib.pkp.classes.security.authorization.AuthorizationDecisionManager');
import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class AuthorizationDecisionManagerTest extends PolicyTestCase {
	private $decisionManager;

	protected function setUp() {
		parent::setUp();
		$this->decisionManager = new AuthorizationDecisionManager();
	}

	/**
	 * @covers AuthorizationDecisionManager
	 */
	public function testDecideIfNoPolicyApplies() {
		// Mock a policy that doesn't apply.
		$mockPolicy = $this->getMock('AuthorizationPolicy', array('applies'));
		$mockPolicy->expects($this->any())
		           ->method('applies')
		           ->will($this->returnValue(false));
		$this->decisionManager->addPolicy($mockPolicy);

		// The default decision should be "deny".
		self::assertEquals(AUTHORIZATION_DENY, $this->decisionManager->decide());

		// Try a non-default decision.
		$this->decisionManager->setDecisionIfNoPolicyApplies(AUTHORIZATION_PERMIT);
		self::assertEquals(AUTHORIZATION_PERMIT, $this->decisionManager->decide());
	}

	/**
	 * @covers AuthorizationDecisionManager
	 */
	public function testAuthorizationMessages() {
		// Create policies that deny access.
		$denyPolicy1 = new AuthorizationPolicy('message 1');
		$denyPolicy2 = new AuthorizationPolicy('message 2');

		// Mock a policy that permits access.
		$permitPolicy = $this->getMock('AuthorizationPolicy', array('effect'), array('message 3'));
		$permitPolicy->expects($this->any())
		             ->method('effect')
		             ->will($this->returnValue(AUTHORIZATION_PERMIT));

		// Create a permit overrides policy set to make sure that
		// all policies will be tested even if several deny access.
		$policySet = new PolicySet(COMBINING_PERMIT_OVERRIDES);
		$policySet->addPolicy($denyPolicy1);
		$policySet->addPolicy($denyPolicy2);
		$policySet->addPolicy($permitPolicy);

		// Let the decision manager decide the policy set.
		$this->decisionManager->addPolicy($policySet);
		self::assertEquals(AUTHORIZATION_PERMIT, $this->decisionManager->decide());

		// Check that the messages for the policies that denied access
		// can be retrieved from the decision manager.
		self::assertEquals(array('message 1', 'message 2'), $this->decisionManager->getAuthorizationMessages());
	}

	/**
	 * @covers AuthorizationDecisionManager
	 */
	public function testAuthorizationContext() {
		// Create a test environment that can be used to
		// manipulate the authorization context.
		$this->decisionManager->addPolicy($this->getAuthorizationContextManipulationPolicy());

		// Make sure that the authorization context is initially empty.
		self::assertNull($this->decisionManager->getAuthorizedContextObject(ASSOC_TYPE_USER_GROUP));

		// Check whether the authorized context is correctly returned from the policy.
		self::assertEquals(AUTHORIZATION_PERMIT, $this->decisionManager->decide());
		self::assertInstanceOf('UserGroup', $this->decisionManager->getAuthorizedContextObject(ASSOC_TYPE_USER_GROUP));
	}

	/**
	 * @covers AuthorizationDecisionManager
	 */
	public function testDecide() {
		// We have to test policies and policy sets
		// as well as different combining algorithms.
		$denyPolicy = new AuthorizationPolicy();
		$permitPolicy = $this->getMock('AuthorizationPolicy', array('effect'));
		$permitPolicy->expects($this->any())
		             ->method('effect')
		             ->will($this->returnCallback(array($this, 'mockEffect')));

		// deny overrides
		// - permit policy
		// - deny policy
		$decisionManager = new AuthorizationDecisionManager();
		$decisionManager->addPolicy($permitPolicy);
		$decisionManager->addPolicy($denyPolicy);
		self::assertEquals(AUTHORIZATION_DENY, $decisionManager->decide());

		// deny overrides
		// - permit policy
		// - permit policy
		$decisionManager = new AuthorizationDecisionManager();
		$decisionManager->addPolicy($permitPolicy);
		$decisionManager->addPolicy($permitPolicy);
		self::assertEquals(AUTHORIZATION_PERMIT, $decisionManager->decide());

		// deny overrides
		// - permit policy
		// - allow overrides
		// -- deny policy
		// -- deny policy
		$decisionManager = new AuthorizationDecisionManager();
		$decisionManager->addPolicy($permitPolicy);
		$policySet = new PolicySet();
		$policySet->addPolicy($denyPolicy);
		$policySet->addPolicy($denyPolicy);
		$decisionManager->addPolicy($policySet);
		self::assertEquals(AUTHORIZATION_DENY, $decisionManager->decide());

		// deny overrides
		// - permit policy
		// - allow overrides
		// -- deny policy
		// -- permit policy
		$decisionManager = new AuthorizationDecisionManager();
		$decisionManager->addPolicy($permitPolicy);
		$policySet = new PolicySet(COMBINING_PERMIT_OVERRIDES);
		$policySet->addPolicy($denyPolicy);
		$policySet->addPolicy($permitPolicy);
		$decisionManager->addPolicy($policySet);
		self::assertEquals(AUTHORIZATION_PERMIT, $decisionManager->decide());
	}

	/**
	 * @covers AuthorizationDecisionManager
	 */
	public function testCallOnDeny() {
		// Create a policy with a call-on-deny advice.
		$policy = $this->getMock('AuthorizationPolicy', array('callOnDeny'));
		$policy->expects($this->once())
		       ->method('callOnDeny')
		       ->will($this->returnCallback(array($this, 'mockCallOnDeny')));
		$callOnDenyAdvice = array(
			$policy,
			'callOnDeny',
			array('argument')
		);
		$policy->setAdvice(AUTHORIZATION_ADVICE_CALL_ON_DENY, $callOnDenyAdvice);

		// Configure and execute the decision manager.
		$this->decisionManager->addPolicy($policy);
		self::assertEquals(AUTHORIZATION_DENY, $this->decisionManager->decide());
	}

	/**
	 * Mock method used in testCallOnDeny() to test the
	 * call-on-deny feature.
	 * @param $argument string
	 */
	public function mockCallOnDeny($argument) {
		// Test whether the argument was correctly passed
		// on to this method.
		self::assertEquals('argument', $argument);
	}
}
?>
