<?php

/**
 * @file plugins/generic/oas/OasPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OasPlugin
 * @ingroup plugins_generic_oas
 *
 * @brief OA-S plugin - turns OJS into a OA-S data provider.
 */


import('lib.pkp.classes.plugins.GenericPlugin');

class OasPlugin extends GenericPlugin {

	/**
	 * @see PKPPlugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if($success && $this->getEnabled()) {
			HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));
		}
		return $success;
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.oas.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.oas.description');
	}

	/**
	 * @see PKPPlugin::getInstallSchemaFile()
	 */
	function getInstallSchemaFile() {
		return $this->getPluginPath() . '/schema.xml';
	}

	/**
	 * @see PKPPlugin::isSitePlugin()
	 */
	function isSitePlugin() {
		return true;
	}

	/**
	 * Register the OA-S report plugin.
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		if ($category == 'reports') {
			$this->import('OasReportPlugin');
			$reportPlugin = new OasReportPlugin();
			$plugins[$reportPlugin->getSeq()][$reportPlugin->getPluginPath()] =& $reportPlugin;
		}
		return false;
	}
}

?>
