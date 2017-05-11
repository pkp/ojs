<?php

/**
 * @file tests/classes/security/authorization/PolicyTestCase.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PolicyTestCase
 * @ingroup tests_classes_security_authorization
 * @see RoleBasedHandlerOperation
 *
 * @brief Abstract base test class that provides infrastructure
 *  for several types of policy tests.
 */

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.security.UserGroup');
import('lib.pkp.classes.core.PKPRequest');

define('ROLE_ID_TEST', 0x9999);

abstract class PolicyTestCase extends PKPTestCase {
	/** @var Array of context object(s) */
	private $contextObjects;

	/** @var AuthorizationContext internal state variable that contains the policy that will be used to manipulate the authorization context */
	private $authorizationContextManipulationPolicy;

	/**
	 * @copydoc PKPTestCase::getMockedRegistryKeys()
	 */
	protected function getMockedRegistryKeys() {
		return array('user');
	}

	/**
	 * Return an array with context object(s).
	 * @return array
	 */
	private function getContextObjects() {
		return $this->contextObjects;
	}

	/**
	 * Set an array with context object(s).
	 * @param Array $contextObjects
	 */
	private function setContextObjects($contextObjects) {
		$this->contextObjects = $contextObjects;
	}

	/**
	 * Create an authorization context manipulation policy.
	 *
	 * @return $testPolicy AuthorizationPolicy the policy that
	 *  will be used by the decision manager to call this
	 *  mock method.
	 */
	protected function getAuthorizationContextManipulationPolicy() {
		if (is_null($this->authorizationContextManipulationPolicy)) {
			// Use a policy to prepare an authorized context
			// with a user group.
			$policy = $this->getMock('AuthorizationPolicy', array('effect'));
			$policy->expects($this->any())
			       ->method('effect')
			       ->will($this->returnCallback(array($this, 'mockEffect')));
			$this->authorizationContextManipulationPolicy = $policy;
		}
		return $this->authorizationContextManipulationPolicy;
	}

	/**
	 * Callback method that will be called in place of the effect()
	 * method of a mock policy.
	 * @return integer AUTHORIZATION_PERMIT
	 */
	public function mockEffect() {
		// Add a user group to the authorized context
		// of the authorization context manipulation policy.
		$policy = $this->getAuthorizationContextManipulationPolicy();
		$userGroup = new UserGroup();
		$userGroup->setRoleId(ROLE_ID_TEST);
		$policy->addAuthorizedContextObject(ASSOC_TYPE_USER_GROUP, $userGroup);

		// Add user roles array to the authorized context.
		$userRoles = array(ROLE_ID_TEST, ROLE_ID_SITE_ADMIN);
		$policy->addAuthorizedContextObject(ASSOC_TYPE_USER_ROLES, $userRoles);
		return AUTHORIZATION_PERMIT;
	}

	/**
	 * Instantiate a mock request to the given operation.
	 * @param $requestedOp string the requested operation
	 * @param $context array request context object(s) to be
	 * returned by the router.
	 * @param $user User a user to be put into the registry.
	 * @return PKPRequest
	 */
	protected function getMockRequest($requestedOp, $context = null, $user = null) {
		// Mock a request to the permitted operation.
		$request = new PKPRequest();

		$this->setContextObjects($context);

		// Mock a router.
		$router = $this->getMock('PKPRouter', array('getRequestedOp', 'getContext'));

		// Mock the getRequestedOp() method.
		$router->expects($this->any())
		       ->method('getRequestedOp')
		       ->will($this->returnValue($requestedOp));

		// Mock the getContext() method.
		$router->expects($this->any())
		       ->method('getContext')
		       ->will($this->returnCallback(array($this, 'mockGetContext')));

		// Put a user into the registry if one has been
		// passed in.
		if ($user instanceof User) {
			Registry::set('user', $user);
		}

		$request->setRouter($router);
		return $request;
	}

	/**
	 * Callback used by PKPRouter created in
	 * getMockRequest().
	 * @see PKPRouter::getContext()
	 * @return mixed Context object or null
	 */
	public function mockGetContext() {
		$functionArgs = func_get_args();
		$contextLevel = $functionArgs[1];

		$contextObjects = $this->getContextObjects();
		if (!empty($contextObjects[$contextLevel - 1])) {
			return $contextObjects[$contextLevel - 1];
		}
		return null;
	}
}
?>
