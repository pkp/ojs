<?php
/**
 * @file classes/security/authorization/PluginAccessPolicy.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PluginAccessPolicy
 * @ingroup security_authorization
 *
 * @brief Class to control access to plugins.
 */

import('lib.pkp.classes.security.authorization.PolicySet');
import('lib.pkp.classes.security.authorization.internal.PluginLevelRequiredPolicy');
import('lib.pkp.classes.security.authorization.internal.PluginRequiredPolicy');
import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');

define('ACCESS_MODE_MANAGE', 0x01);
define('ACCESS_MODE_ADMIN', 0x02);

class PluginAccessPolicy extends PolicySet {
	/**
	 * Constructor
	 * @param $request PKPRequest
	 * @param $args array request arguments
	 * @param $roleAssignments array
	 * @param $accessMode int
	 */
	function __construct($request, &$args, $roleAssignments, $accessMode = ACCESS_MODE_ADMIN) {
		parent::__construct();

		// A valid plugin is required.
		$this->addPolicy(new PluginRequiredPolicy($request));

		// Managers and site admin have access to plugins. We'll have to define
		// differentiated policies for those roles in a policy set.
		$pluginAccessPolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);
		$pluginAccessPolicy->setEffectIfNoPolicyApplies(AUTHORIZATION_DENY);

		//
		// Managerial role
		//
		if (isset($roleAssignments[ROLE_ID_MANAGER])) {
			if ($accessMode & ACCESS_MODE_MANAGE) {
				// Managers have edit settings access mode...
				$managerPluginAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
				$managerPluginAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_MANAGER, $roleAssignments[ROLE_ID_MANAGER]));

				// ...only to context-level plugins.
				$managerPluginAccessPolicy->addPolicy(new PluginLevelRequiredPolicy($request, true));

				$pluginAccessPolicy->addPolicy($managerPluginAccessPolicy);
			}
		}

		//
		// Site administrator role
		//
		if (isset($roleAssignments[ROLE_ID_SITE_ADMIN])) {
			// Site admin have access to all plugins...
			$siteAdminPluginAccessPolicy = new PolicySet(COMBINING_DENY_OVERRIDES);
			$siteAdminPluginAccessPolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, ROLE_ID_SITE_ADMIN, $roleAssignments[ROLE_ID_SITE_ADMIN]));

			if ($accessMode & ACCESS_MODE_MANAGE) {
				// ...of site level only.
				$siteAdminPluginAccessPolicy->addPolicy(new PluginLevelRequiredPolicy($request, false));
			}

			$pluginAccessPolicy->addPolicy($siteAdminPluginAccessPolicy);
		}

		$this->addPolicy($pluginAccessPolicy);
	}
}

?>
