<?php

/**
 * @file plugins/generic/usageStats/UsageStatsHandler.inc.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsHandler
 * @ingroup plugins_generic_usageStatus
 *
 * @brief Handle usage stats page requests (opt-out, privacy information)
 */

import('classes.handler.Handler');

class UsageStatsHandler extends Handler {

	/**
	 * Constructor
	 */
	function UsageStatsHandler() {
		parent::Handler();
	}


	//
	// Public operations
	//
	/**
	 * Show a page with privacy information and an
	 * opt-out option.
	 *
	 * @param $args array
	 * @param $request Request
	 */
	function privacyInformation($args, $request) {
		$this->validate(null, $request);

		// Check whether this is an opt-out request.
		if ($request->isPost()) {
			if ($request->getUserVar('opt-out')) {
				// Set a cookie that is valid for one year.
				$request->setCookieVar('usageStats-opt-out', true, time() + 60*60*24*365);
			}
			if ($request->getUserVar('opt-in')) {
				// Delete the opt-out cookie.
				$request->setCookieVar('usageStats-opt-out', false, time() - 60*60);
			}
		}

		$router = $request->getRouter(); /* @var $router PageRouter */
		$privacyStatementUrl = $router->url($request, null, 'about', 'submissions', null, null, 'privacyStatement');

		// Display the privacy info page.
		$this->setupTemplate($request);
		$plugin = $this->_getPlugin();
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pageTitle', 'plugins.generic.usageStats.optout.title');
		$templateMgr->assign('usageStatsDisplayPrivacyInfo', true);
		$templateMgr->assign('hasOptedOut', ($request->getCookieVar('usageStats-opt-out') ? true : false));
		$templateMgr->assign('privacyStatementUrl', $privacyStatementUrl);
		$templateMgr->display($plugin->getTemplatePath().'privacyInformation.tpl');
	}

	//
	// Private helper methods
	//
	/**
	 * Get the Usage Stats plugin object
	 * @return UsageStatsPlugin
	 */
	function &_getPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', USAGESTATS_PLUGIN_NAME);
		return $plugin;
	}
}

?>
