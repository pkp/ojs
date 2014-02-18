<?php

/**
 * @file controllers/grid/settings/plugins/SettingsPluginGridHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
	function SettingsPluginGridHandler() {
		$roles = array(ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER);
		$this->addRoleAssignment($roles, array('plugin'));
		parent::PluginGridHandler($roles);
	}


	//
	// Extended methods from PluginGridHandler
	//
	/**
	 * @copydoc PluginGridHandler::loadData()
	 */
	function getCategoryData($categoryDataElement, $filter) {
		$plugins = parent::getCategoryData($categoryDataElement, $filter);

		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getAll();
		$singleJournal = false;
		if ($journals->getCount() == 1) {
			$singleJournal = true;
		}

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
	function getRowInstance() {
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

		import('controllers.grid.plugins.PluginGridRow');
		return new PluginGridRow($userRoles, CONTEXT_JOURNAL);
	}

	/**
	 * @copydoc GridHandler::authorize()
	 */
	function authorize($request, $args, $roleAssignments) {
		$category = $request->getUserVar('category');
		$pluginName = $request->getUserVar('plugin');
		$verb = $request->getUserVar('verb');

		if ($category && $pluginName) {
			import('classes.security.authorization.OjsPluginAccessPolicy');
			if ($verb) {
				$accessMode = ACCESS_MODE_MANAGE;
			} else {
				$accessMode = ACCESS_MODE_ADMIN;
			}

			$this->addPolicy(new OjsPluginAccessPolicy($request, $args, $roleAssignments, $accessMode));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}
}

?>
