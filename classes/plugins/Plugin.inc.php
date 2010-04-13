<?php

/**
 * @file classes/plugins/Plugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Plugin
 * @ingroup plugins
 *
 * @brief Abstract class for plugins
 */

// $Id$


import('plugins.PKPPlugin');

class Plugin extends PKPPlugin {
	/**
	 * Constructor
	 */
	function Plugin() {
		parent::PKPPlugin();
	}

	function getSetting($journalId, $name) {
		if (defined('RUNNING_UPGRADE')) {
			// Bug #2504: Make sure plugin_settings table is not
			// used if it's not available.
			$versionDao =& DAORegistry::getDAO('VersionDAO');
			$version =& $versionDao->getCurrentVersion(null, true);
			if ($version->compare('2.1.0') < 0) return null;
		}
		return parent::getSetting(array($journalId), $name);
	}

	/**
	 * Update a plugin setting.
	 * @param $journalId int
	 * @param $name string The name of the setting
	 * @param $value mixed
	 * @param $type string optional
	 */
	function updateSetting($journalId, $name, $value, $type = null) {
		parent::updateSetting(array($journalId), $name, $value, $type);
	}

	/**
	 * Get the filename of the settings data for this plugin to install
	 * when a journal is created (i.e. journal-level plugin settings).
	 * Subclasses using default settings should override this.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		// The default implementation delegates to the old
		// method for backwards compatibility.
		return $this->getNewJournalPluginSettingsFile();
	}

	/**
	 * For backwards compatibility only.
	 *
	 * New plug-ins should override getContextSpecificPluginSettingsFile
	 */
	function getNewJournalPluginSettingsFile() {
		return null;
	}
}

?>
