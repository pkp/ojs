<?php

/**
 * @file plugins/generic/oas/OasHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OasHandler
 * @ingroup plugins_generic_oas
 *
 * @brief Handle OA-S page requests (opt-out, privacy information, etc.)
 */

import('classes.handler.Handler');

class OasHandler extends Handler {

	/**
	 * Constructor
	 */
	function OasHandler() {
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
		if ($request->isPost() && $request->getUserVar('opt-out')) {
			// Set a cookie that is valid for one year.
			$request->setCookieVar('oas-opt-out', true, time() + 60*60*24*365);
		}

		// Display the privacy info page.
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pageTitle', 'plugins.generic.oas.optout.title');
		$templateMgr->assign('oasDisplayPrivacyInfo', true);
		$templateMgr->assign('hasOptedOut', ($request->getCookieVar('oas-opt-out') ? true : false));
		$plugin = $this->_getPlugin();
		$templateMgr->display($plugin->getTemplatePath().'privacyInformation.tpl');
	}


	//
	// Private helper methods
	//
	/**
	 * Get the OA-S plugin object
	 * @return OasPlugin
	 */
	function &_getPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', OAS_PLUGIN_NAME);
		return $plugin;
	}
}

?>
