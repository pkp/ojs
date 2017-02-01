<?php

/**
 * @file controllers/grid/settings/plugins/SettingsPluginGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SettingsPluginGridHandler
 * @ingroup controllers_grid_settings_plugins
 *
 * @brief Handle plugin grid requests.
 */

import('lib.pkp.classes.controllers.grid.plugins.PluginGridHandler');

class SettingsPluginGridHandler extends PluginGridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		$roles = array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER);
		$this->addRoleAssignment($roles, array('manage'));
		parent::__construct($roles);
	}


	//
	// Extended methods from PluginGridHandler
	//
	/**
	 * @copydoc PluginGridHandler::loadData()
	 */
	function loadCategoryData($request, $categoryDataElement, $filter) {
		$plugins = parent::loadCategoryData($request, $categoryDataElement, $filter);
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

		$showSitePlugins = false;
		if (in_array(ROLE_ID_SITE_ADMIN, $userRoles)) {
			$showSitePlugins = true;
		}

		if ($showSitePlugins) {
			return $plugins;
		} else {
			$contextLevelPlugins = array();
			foreach ($plugins as $plugin) {
				if (!$plugin->isSitePlugin()) {
					$contextLevelPlugins[$plugin->getName()] = $plugin;
				}
				unset($plugin);
			}
			return $contextLevelPlugins;
		}
	}

	//
	// Overriden template methods.
	//
	/**
	 * @copydoc CategoryGridHandler::getCategoryRowInstance()
	 */
	protected function getRowInstance() {
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

		import('controllers.grid.plugins.PluginGridRow');
		return new PluginGridRow($userRoles, CONTEXT_JOURNAL);
	}

	/**
	 * @copydoc GridHandler::authorize()
	 */
	function authorize($request, $args, $roleAssignments) {
		$categoryName = $request->getUserVar('category');
		$pluginName = $request->getUserVar('plugin');
		if ($categoryName && $pluginName) {
			import('classes.security.authorization.OjsPluginAccessPolicy');
			switch ($request->getRequestedOp()) {
				case 'enable':
				case 'disable':
				case 'manage':
					$accessMode = ACCESS_MODE_MANAGE;
					break;
				default:
					$accessMode = ACCESS_MODE_ADMIN;
					break;
			}
			$this->addPolicy(new OjsPluginAccessPolicy($request, $args, $roleAssignments, $accessMode));
		}
		return parent::authorize($request, $args, $roleAssignments);
	}
}

?>
