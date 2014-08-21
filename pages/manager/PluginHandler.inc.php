<?php

/**
 * @file pages/manager/PluginHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PluginHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for plugin management functions.
 */


import('pages.manager.ManagerHandler');

class PluginHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function PluginHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of plugins along with management options.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function plugins($args, &$request) {
		$category = isset($args[0])?$args[0]:null;
		$categories = PluginRegistry::getCategories();

		$templateMgr =& TemplateManager::getManager();
		$this->validate();

		if (isset($category) && in_array($category, $categories)) {
			// The user specified a category of plugins to view;
			// get the plugins in that category only.
			$mainPage = false;
			$plugins =& PluginRegistry::loadCategory($category);

			$this->setupTemplate(false);
			$templateMgr->assign('pageTitle', 'plugins.categories.' . $category);
			$templateMgr->assign('pageHierarchy', $this->setBreadcrumbs($request, true));
		} else {
			// No plugin specified; display all.
			$mainPage = true;
			$plugins = array();
			foreach ($categories as $category) {
				$newPlugins =& PluginRegistry::loadCategory($category);
				if (isset($newPlugins)) {
					$plugins = array_merge($plugins, PluginRegistry::loadCategory($category));
				}
			}

			$this->setupTemplate(true);
			$templateMgr->assign('pageTitle', 'manager.plugins.pluginManagement');
			$templateMgr->assign('pageHierarchy', $this->setBreadcrumbs($request, false));
		}

		$templateMgr->assign_by_ref('plugins', $plugins);
		$templateMgr->assign_by_ref('categories', $categories);
		$templateMgr->assign('mainPage', $mainPage);
		$templateMgr->assign('isSiteAdmin', Validation::isSiteAdmin());
		$templateMgr->assign('helpTopicId', 'journal.managementPages.plugins');

		$site =& $request->getSite();
		$preventManagerPluginManagement = $site->getSetting('preventManagerPluginManagement');
		$templateMgr->assign('preventManagerPluginManagement', !Validation::isSiteAdmin() && $preventManagerPluginManagement);

		$templateMgr->display('manager/plugins/plugins.tpl');
	}

	/**
	 * Perform plugin-specific management functions.
	 * @param $args array
	 * @param $request object
	 */
	function plugin($args, &$request) {
		$category = array_shift($args);
		$plugin = array_shift($args);
		$verb = array_shift($args);

		$this->validate();
		$this->setupTemplate(true);

		$plugins =& PluginRegistry::loadCategory($category);
		$message = $messageParams = null;
		if (!isset($plugins[$plugin]) || !$plugins[$plugin]->manage($verb, $args, $message, $messageParams, $request)) {
			HookRegistry::call('PluginHandler::plugin', array($verb, $args, $message, $messageParams, $plugins[$plugin]));
			if ($message) {
				$user =& $request->getUser();
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationManager->createTrivialNotification($user->getId(), $message, $messageParams);
			}
			$request->redirect(null, null, 'plugins', array($category));
		}
	}

	/**
	 * Set the page's breadcrumbs
	 * @param $request PKPRequest
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($request, $subclass = false) {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				$request->url(null, 'user'),
				'navigation.user',
				false
			),
			array(
				$request->url(null, 'manager'),
				'manager.journalManagement',
				false
			)
		);

		if ($subclass) {
			$pageCrumbs[] = array(
				$request->url(null, 'manager', 'plugins'),
				'manager.plugins.pluginManagement',
				false
			);
		}

		return $pageCrumbs;
	}
}

?>
