<?php

/**
 * @file plugins/generic/metadataExport/MetadataExportBlockPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataExportBlockPlugin
 * @ingroup plugins_generic_metadataExport
 *
 * @brief Class for block component of metadata export plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class MetadataExportBlockPlugin extends BlockPlugin {
	
	/* @var string Name of the parent plugin object */
	var $parentPluginName;
	
	/* @var array Plugins of the metadataexport category */
	var $metadataExportPlugins;

	
	/**
	 * Constructor
	 * @param $parentPluginName String
	 */
	function MetadataExportBlockPlugin($parentPluginName) {
		$this->parentPluginName = $parentPluginName;
	}
	
	/**
	 * @copydoc PKPPlugin::getTemplatePath()
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}
	
	/**
	 * @copydoc PKPPlugin::getName()
	 */
	function getName() {
		return 'MetadataExportBlockPlugin';
	}

	/**
	 * @copydoc PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.metadataExport.displayName');
	}

	/**
	 * @copydoc PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.metadataExport.description');
	}
	
	/**
	 * @copydoc PKPPlugin::getPluginPath()
	 */
	function getPluginPath() {
		$plugin = PluginRegistry::getPlugin('generic', $this->parentPluginName);
		return $plugin->getPluginPath();
	}
	
	/**
	 * @copydoc BlockPlugin::getContents()
	 */
	function getContents($templateMgr) {
		$journal = Request::getJournal();
		if ($journal) {
			$this->metadataExportPlugins = PluginRegistry::loadCategory('generic/metadataExport/metadataExportFormats');
			uasort($this->metadataExportPlugins, create_function('$a, $b', 'return strcmp($a->getDisplayName(), $b->getDisplayName());'));
			
			$templateMgr->assign('journalId', $journal->getId());
			$templateMgr->assign('metadataExportPlugins', $this->metadataExportPlugins);
			
			return parent::getContents($templateMgr);
		}
	}
}
?>