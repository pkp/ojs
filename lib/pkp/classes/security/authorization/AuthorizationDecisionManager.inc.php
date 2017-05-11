<?php
/**
 * @file classes/security/authorization/AuthorizationDecisionManager.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorizationDecisionManager
 * @ingroup security_authorization
 *
 * @brief A class that can take a list of authorization policies, apply
 *  them to the current authorization request context and return an
 *  authorization decision.
 *
 *  This decision manager implements the following logic to combine
 *  authorization policies:
 *  - If any of the given policies applies with a result of
 *    AUTHORIZATION_DENY then the decision manager will deny access
 *    (=deny overrides policy).
 *  - If none of the given policies applies then the decision
 *    manager will deny access (=whitelist approach, deny if none
 *    applicable).
 */

import('lib.pkp.classes.security.authorization.PolicySet');

define('AUTHORIZATION_NOT_APPLICABLE', 0x03);

class AuthorizationDecisionManager {
	/** @var PolicySet the root policy set */
	var $_rootPolicySet;

	/** @var array */
	var $_authorizationMessages = array();

	/** @var array authorized objects provided by authorization policies */
	var $_authorizedContext = array();

	/**
	 * Constructor
	 */
	function __construct() {
		// Instantiate the main policy set we'll add root policies to.
		$this->_rootPolicySet = new PolicySet(COMBINING_DENY_OVERRIDES);
	}


	//
	// Setters and Getters
	//
	/**
	 * Set the default decision if none of the
	 * policies in the root policy set applies.
	 * @param $decisionIfNoPolicyApplies integer
	 */
	function setDecisionIfNoPolicyApplies($decisionIfNoPolicyApplies) {
		$this->_rootPolicySet->setEffectIfNoPolicyApplies($decisionIfNoPolicyApplies);
	}

	/**
	 * Add an authorization policy or a policy set.
	 *
	 * @param $policyOrPolicySet AuthorizationPolicy|PolicySet
	 * @param $addToTop boolean whether to insert the new policy
	 *  to the top of the list.
	 */
	function addPolicy($policyOrPolicySet, $addToTop = false) {
		$this->_rootPolicySet->addPolicy($policyOrPolicySet, $addToTop);
	}

	/**
	 * Add an authorization message
	 * @param $message string
	 */
	function addAuthorizationMessage($message) {
		$this->_authorizationMessages[] = $message;
	}

	/**
	 * Return all authorization messages
	 * @return array
	 */
	function getAuthorizationMessages() {
		return $this->_authorizationMessages;
	}

	/**
	 * Retrieve an object from the authorized context
	 * @param $assocType integer
	 * @return mixed will return null if the context
	 *  for the given assoc type does not exist.
	 */
	function &getAuthorizedContextObject($assocType) {
		if (isset($this->_authorizedContext[$assocType])) {
			return $this->_authorizedContext[$assocType];
		} else {
			$nullVar = null;
			return $nullVar;
		}
	}

	/**
	 * Get the authorized context.
	 * @return array
	 */
	function &getAuthorizedContext() {
		return $this->_authorizedContext;
	}


	//
	// Public methods
	//
	/**
	 * Take an authorization decision.
	 *
	 * @return integer one of AUTHORIZATION_PERMIT or
	 *  AUTHORIZATION_DENY.
	 */
	function decide() {
		// Decide the root policy set which will recursively decide
		// all nested policy sets and return a single decision.
		$callOnDeny = null;
		$decision = $this->_decidePolicySet($this->_rootPolicySet, $callOnDeny);
		assert($decision !== AUTHORIZATION_NOT_APPLICABLE);

		// Call the "call on deny" advice
		if ($decision === AUTHORIZATION_DENY && !is_null($callOnDeny)) {
			assert(is_array($callOnDeny) && count($callOnDeny) == 3);
			list($classOrObject, $method, $parameters) = $callOnDeny;
			$methodCall = array($classOrObject, $method);
			assert(is_callable($methodCall));
			call_user_func_array($methodCall, $parameters);
		}

		return $decision;
	}


	//
	// Private helper methods
	//
	/**
	 * Recursively decide the given policy set.
	 * @param $policySet PolicySet
	 * @param $callOnDeny A "call-on-deny" advice will be passed
	 *  back by reference if found.
	 * @return integer one of the AUTHORIZATION_* values.
	 */
	function _decidePolicySet(&$policySet, &$callOnDeny) {
		// Configure the decision algorithm.
		$combiningAlgorithm = $policySet->getCombiningAlgorithm();
		switch($combiningAlgorithm) {
			case COMBINING_DENY_OVERRIDES:
				$dominantEffect = AUTHORIZATION_DENY;
				$overriddenEffect = AUTHORIZATION_PERMIT;
				break;

			case COMBINING_PERMIT_OVERRIDES:
				$dominantEffect = AUTHORIZATION_PERMIT;
				$overriddenEffect = AUTHORIZATION_DENY;
				break;

			default:
				assert(false);
		}

		// Set the default decision.
		$decision = $policySet->getEffectIfNoPolicyApplies();

		// The following flag will record when the
		// overridden decision state is returned by
		// at least one policy.
		$decidedByOverriddenEffect = false;

		// Separated from below for bug #6821.
		$context =& $this->getAuthorizedContext();

		// Go through all policies within the policy set
		// and combine them with the configured algorithm.
		foreach($policySet->getPolicies() as $policy) {
			// Treat policies and policy sets differently.
			switch (true) {
				case is_a($policy, 'AuthorizationPolicy'):
					// Make sure that the policy can access the latest authorized context.
					// NB: The authorized context is set by reference. This means that it
					// will change globally if changed by the policy which is intended
					// behavior so that policies can access authorized objects provided
					// by policies called earlier in the authorization process.
					$policy->setAuthorizedContext($context);

					// Check whether the policy applies.
					if ($policy->applies()) {
						// If the policy applies then retrieve its effect.
						$effect = $policy->effect();
					} else {
						$effect = AUTHORIZATION_NOT_APPLICABLE;
					}
					break;

				case is_a($policy, 'PolicySet'):
					// We found a nested policy set.
					$effect = $this->_decidePolicySet($policy, $callOnDeny);
					break;

				default:
					assert(false);
			}

			// Try the next policy if this policy didn't apply.
			if ($effect === AUTHORIZATION_NOT_APPLICABLE) continue;
			assert($effect === AUTHORIZATION_PERMIT || $effect === AUTHORIZATION_DENY);

			// "Deny" decision may cause a message to the end user.
			if (is_a($policy, 'AuthorizationPolicy') && $effect == AUTHORIZATION_DENY
					&& $policy->hasAdvice(AUTHORIZATION_ADVICE_DENY_MESSAGE)) {
				$this->addAuthorizationMessage($policy->getAdvice(AUTHORIZATION_ADVICE_DENY_MESSAGE));
			}

			// Process the effect.
			if ($effect === $overriddenEffect) {
				$decidedByOverriddenEffect = true;
			} else {
				// In case of a "deny overrides" we allow a "call-on-deny" advice.
				if (is_a($policy, 'AuthorizationPolicy') && $dominantEffect == AUTHORIZATION_DENY
						&& $policy->hasAdvice(AUTHORIZATION_ADVICE_CALL_ON_DENY)) {
					$callOnDeny = $policy->getAdvice(AUTHORIZATION_ADVICE_CALL_ON_DENY);
				}

				// Only one dominant effect overrides all other effects
				// so we don't even have to evaluate other policies.
				return $dominantEffect;
			}
		}

		// Only return an overridden effect if at least one
		// policy returned that effect and none returned the
		// dominant effect.
		if ($decidedByOverriddenEffect) $decision = $overriddenEffect;
		return $decision;
	}
}

?>
