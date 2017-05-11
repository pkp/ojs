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

	/** @var boolean */
	var $_contextPresent;

	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $contextPresent boolean
	 */
	function __construct($request, $contextPresent) {
		parent::__construct('user.authorization.pluginLevel');
		$this->_contextPresent = $contextPresent;
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

		if (!$this->_contextPresent) { // Site context
			return $plugin->isSitePlugin()?AUTHORIZATION_PERMIT:AUTHORIZATION_DENY;
		}
		return $plugin->isSitePlugin()?AUTHORIZATION_DENY:AUTHORIZATION_PERMIT;
	}
}

?>
