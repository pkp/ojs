<?php

/**
 * @file PluginHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 * @class PluginHandler
 *
 * Handle requests for plugin management functions.
 *
 * $Id$
 */

class PluginHandler extends ManagerHandler {
	/**
	 * Display a list of plugins along with management options.
	 */
	function plugins($args) {
		$category = isset($args[0])?$args[0]:null;

		parent::validate();

		$categories = PluginRegistry::getCategories();

		if (isset($category)) {
			// The user specified a category of plugins to view;
			// get the plugins in that category only.
			$plugins =& PluginRegistry::loadCategory($category);
		} else {
			// No plugin specified; display all.
			$plugins = array();
			foreach ($categories as $category) {
				$newPlugins =& PluginRegistry::loadCategory($category);
				if (isset($newPlugins)) {
					$plugins = array_merge($plugins, PluginRegistry::loadCategory($category));
				}
			}
		}

		parent::setupTemplate(true);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('plugins', $plugins);
		$templateMgr->assign_by_ref('categories', $categories);
		$templateMgr->assign('isSiteAdmin', Validation::isSiteAdmin());
		$templateMgr->assign('helpTopicId', 'journal.managementPages.plugins');

		$templateMgr->display('manager/plugins/plugins.tpl');
	}

	/**
	 * Perform plugin-specific management functions.
	 */
	function plugin($args) {
		$category = array_shift($args);
		$plugin = array_shift($args);
		$verb = array_shift($args);

		parent::validate();

		$plugins =& PluginRegistry::loadCategory($category);
		if (!isset($plugins[$plugin]) || !$plugins[$plugin]->manage($verb, $args)) {
			Request::redirect(null, null, 'plugins');
		}
	}
}

?>
