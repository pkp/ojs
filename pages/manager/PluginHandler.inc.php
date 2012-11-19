<?php

/**
 * @file pages/manager/PluginHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
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
		$templateMgr =& TemplateManager::getManager($request);
		$this->validate();

		$this->setupTemplate($request, true);
		$templateMgr->assign('pageTitle', 'manager.plugins.pluginManagement');
		$templateMgr->assign('pageHierarchy', array(
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
		));

		$templateMgr->assign('helpTopicId', 'journal.managementPages.plugins');

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
		$this->setupTemplate($request, true);

		$plugins =& PluginRegistry::loadCategory($category);
		$message = $messageParams = null;
		if (!isset($plugins[$plugin]) || !$plugins[$plugin]->manage($verb, $args, $message, $messageParams)) {
			if ($message) {
				$user =& $request->getUser();
				import('classes.notification.NotificationManager');
				$notificationManager = new NotificationManager();
				$notificationManager->createTrivialNotification($user->getId(), $message, $messageParams);
			}
			$request->redirect(null, null, 'plugins', array($category));
		}
	}
}

?>
