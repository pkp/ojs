<?php
/**
 * @file classes/security/authorization/HandlerOperationPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HandlerOperationPolicy
 * @ingroup security_authorization
 *
 * @brief Abstract base class that provides infrastructure
 *  to control access to handler operations.
 */

import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class HandlerOperationPolicy extends AuthorizationPolicy {
	/** @var PKPRequest */
	var $_request;

	/** @var array the target operations */
	var $_operations = array();

	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $operations array|string either a single operation or a list of operations that
	 *  this policy is targeting.
	 * @param $message string a message to be displayed if the authorization fails
	 */
	function __construct($request, $operations, $message = null) {
		parent::__construct($message);
		$this->_request =& $request;

		// Make sure a single operation doesn't have to
		// be passed in as an array.
		assert(is_string($operations) || is_array($operations));
		if (!is_array($operations)) {
			$operations = array($operations);
		}
		$this->_operations = $operations;
	}


	//
	// Setters and Getters
	//
	/**
	 * Return the request.
	 * @return PKPRequest
	 */
	function &getRequest() {
		return $this->_request;
	}

	/**
	 * Return the operations whitelist.
	 * @return array
	 */
	function getOperations() {
		return $this->_operations;
	}


	//
	// Private helper methods
	//
	/**
	 * Check whether the requested operation is on
	 * the list of permitted operations.
	 * @return boolean
	 */
	function _checkOperationWhitelist() {
		// Only permit if the requested operation has been whitelisted.
		$router = $this->_request->getRouter();
		$requestedOperation = $router->getRequestedOp($this->_request);
		assert(!empty($requestedOperation));
		return in_array($requestedOperation, $this->_operations);
	}
}

?>
