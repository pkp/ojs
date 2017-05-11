<?php
/**
 * @file classes/security/authorization/DataObjectRequiredPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataObjectRequiredPolicy
 * @ingroup security_authorization
 *
 * @brief Abstract base class for policies that check for a data object from a parameter.
 */

import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class DataObjectRequiredPolicy extends AuthorizationPolicy {
	/** @var PKPRequest */
	var $_request;

	/** @var array */
	var $_args;

	/** @var string */
	var $_parameterName;

	/** @var array */
	var $_operations;

	//
	// Getters and Setters
	//
	/**
	 * Return the request.
	 * @return PKPRequest
	 */
	function &getRequest() {
		return $this->_request;
	}

	/**
	 * Return the request arguments
	 * @return array
	 */
	function &getArgs() {
		return $this->_args;
	}

	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request parameters
	 * @param $parameterName string the request parameter we expect
	 * @param $message string
	 * @param $operations array Optional list of operations for which this check takes effect. If specified, operations outside this set will not be checked against this policy.
	 */
	function __construct($request, &$args, $parameterName, $message = null, $operations = null) {
		parent::__construct($message);
		$this->_request = $request;
		assert(is_array($args));
		$this->_args =& $args;
		$this->_parameterName = $parameterName;
		$this->_operations = $operations;
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		// Check if the object is required for the requested Op. (No operations means check for all.)
		if (is_array($this->_operations) && !in_array($this->_request->getRequestedOp(), $this->_operations)) {
			return AUTHORIZATION_PERMIT;
		} else {
			return $this->dataObjectEffect();
		}
	}

	//
	// Protected helper method
	//
	/**
	 * Test the data object's effect
	 * @return AUTHORIZATION_DENY|AUTHORIZATION_ACCEPT
	 */
	function dataObjectEffect() {
		// Deny by default. Must be implemented by subclass.
		return AUTHORIZATION_DENY;
	}

	/**
	 * Identifies a submission id in the request.
	 * @return integer|false returns false if no valid submission id could be found.
	 */
	function getDataObjectId() {
		// Identify the data object id.
		$router = $this->_request->getRouter();
		switch(true) {
			case is_a($router, 'PKPPageRouter'):
				if ( ctype_digit((string) $this->_request->getUserVar($this->_parameterName)) ) {
					// We may expect a object id in the user vars
					return (int) $this->_request->getUserVar($this->_parameterName);
				} else if (isset($this->_args[0]) && ctype_digit((string) $this->_args[0])) {
					// Or the object id can be expected as the first path in the argument list
					return (int) $this->_args[0];
				}
				break;

			case is_a($router, 'PKPComponentRouter'):
				// We expect a named object id argument.
				if (isset($this->_args[$this->_parameterName])
						&& ctype_digit((string) $this->_args[$this->_parameterName])) {
					return (int) $this->_args[$this->_parameterName];
				}
				break;

			default:
				assert(false);
		}

		return false;
	}
}

?>
