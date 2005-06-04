<?php

/**
 * ImportExportHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package pages.manager
 *
 * Handle requests for import/export functions. 
 *
 * $Id$
 */

define('IMPORTEXPORT_PLUGIN_CATEGORY', 'importexport');

class ImportExportHandler extends ManagerHandler {
	function importexport($args) {
		parent::validate();
		parent::setupTemplate(true);

		PluginRegistry::loadCategory(IMPORTEXPORT_PLUGIN_CATEGORY);
		$templateMgr = &TemplateManager::getManager();

		if (array_shift($args) === 'plugin') {
			$pluginName = array_shift($args);
			$plugin = &PluginRegistry::getPlugin(IMPORTEXPORT_PLUGIN_CATEGORY, $pluginName); 
			if ($plugin) return $plugin->display($templateMgr, $args);
		}
		$templateMgr->assign('plugins', PluginRegistry::getPlugins(IMPORTEXPORT_PLUGIN_CATEGORY));
		$templateMgr->display('manager/importexport/plugins.tpl');
	}
}
?>
