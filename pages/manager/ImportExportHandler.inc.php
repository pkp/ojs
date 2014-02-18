<?php

/**
 * @file pages/manager/ImportExportHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ImportExportHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for import/export functions.
 */

define('IMPORTEXPORT_PLUGIN_CATEGORY', 'importexport');

import('pages.manager.ManagerHandler');

class ImportExportHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function ImportExportHandler() {
		parent::ManagerHandler();
		$this->addRoleAssignment(ROLE_ID_MANAGER, 'importexport');
	}

	/**
	 * Import or export data.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function importexport($args, $request) {
		$this->setupTemplate($request, true);

		PluginRegistry::loadCategory(IMPORTEXPORT_PLUGIN_CATEGORY);
		$templateMgr = TemplateManager::getManager($request);

		if (array_shift($args) === 'plugin') {
			$pluginName = array_shift($args);
			$plugin = PluginRegistry::getPlugin(IMPORTEXPORT_PLUGIN_CATEGORY, $pluginName);
			if ($plugin) return $plugin->display($args, $request);
		}
		$templateMgr->assign('plugins', PluginRegistry::getPlugins(IMPORTEXPORT_PLUGIN_CATEGORY));
		$templateMgr->display('manager/importexport/plugins.tpl');
	}
}

?>
