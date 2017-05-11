<?php

/**
 * @file tests/classes/security/authorization/PolicySetTest.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PolicySetTest
 * @ingroup tests_classes_security_authorization
 * @see PolicySet
 *
 * @brief Test class for the PolicySet class
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.security.authorization.PolicySet');
import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class PolicySetTest extends PKPTestCase {
	/**
	 * @covers PolicySet
	 */
	public function testPolicySet() {
		// Test combining algorithm and default effect.
		$policySet = new PolicySet();
		self::assertEquals(COMBINING_DENY_OVERRIDES, $policySet->getCombiningAlgorithm());
		self::assertEquals(AUTHORIZATION_DENY, $policySet->getEffectIfNoPolicyApplies());
		$policySet = new PolicySet(COMBINING_PERMIT_OVERRIDES);
		$policySet->setEffectIfNoPolicyApplies(AUTHORIZATION_PERMIT);
		self::assertEquals(COMBINING_PERMIT_OVERRIDES, $policySet->getCombiningAlgorithm());
		self::assertEquals(AUTHORIZATION_PERMIT, $policySet->getEffectIfNoPolicyApplies());

		// Test adding policies.
		$policySet->addPolicy($policy1 = new AuthorizationPolicy('policy1'));
		$policySet->addPolicy($policy2 = new AuthorizationPolicy('policy2'));
		$policySet->addPolicy($policy3 = new AuthorizationPolicy('policy3'), $addToTop = true);
		self::assertEquals(array($policy3, $policy1, $policy2), $policySet->getPolicies());
	}
}
?>
