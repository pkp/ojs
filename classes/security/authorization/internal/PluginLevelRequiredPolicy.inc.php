<?php
/**
 * @file classes/security/authorization/internal/PluginLevelRequiredPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PluginLevelRequiredPolicy
 * @ingroup security_authorization_internal
 *
 * @brief Class to test the plugin level.
 *
 */

import('lib.pkp.classes.security.authorization.AuthorizationPolicy');

class PluginLevelRequiredPolicy extends AuthorizationPolicy {

	/** @var int */
	var $_contextLevel;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 */
	function __construct($request, $contextLevel) {
		parent::__construct();
		$this->_contextLevel =& $contextLevel;
	}

	//
	// Implement template methods from AuthorizationPolicy
	//
	/**
	 * @see AuthorizationPolicy::effect()
	 */
	function effect() {
		// Get the plugin.
		$plugin = $this->getAuthorizedContextObject(ASSOC_TYPE_PLUGIN);
		if (!is_a($plugin, 'Plugin')) return AUTHORIZATION_DENY;

		// Test the plugin level.
		if ($this->_contextLevel === CONTEXT_SITE) {
			if ($plugin->isSitePlugin()) {
				return AUTHORIZATION_PERMIT;
			} else {
				return AUTHORIZATION_DENY;
			}
		} elseif ($this->_contextLevel & CONTEXT_JOURNAL) {
			if ($plugin->isSitePlugin()) {
				return AUTHORIZATION_DENY;
			} else {
				return AUTHORIZATION_PERMIT;
			}
		}
		return AUTHORIZATION_DENY;
	}
}

?>
