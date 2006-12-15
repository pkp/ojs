<?php

/**
 * PluginHelpMappingFile.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package help
 * 
 * Abstracts the plugin's help mapping XML files.
 *
 * $Id$
 */

import('help.HelpMappingFile');

class PluginHelpMappingFile extends HelpMappingFile {
	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 */
	function PluginHelpMappingFile(&$plugin) {
		parent::HelpMappingFile($plugin->getHelpMappingFilename());
		$this->plugin =& $plugin;
	}

	/**
	 * Return the filename for a plugin help TOC filename.
	 */
	function getTocFilename($tocId) {
		$help =& Help::getHelp();
		return $this->plugin->getPluginPath() . DIRECTORY_SEPARATOR . 'help' . DIRECTORY_SEPARATOR . $help->getLocale() . DIRECTORY_SEPARATOR . $tocId . '.xml';
	}

	/**
	 * Return the filename for a plugin help topic filename.
	 */
	function getTopicFilename($topicId) {
		$help =& Help::getHelp();
		return $this->plugin->getPluginPath() . DIRECTORY_SEPARATOR . 'help' . DIRECTORY_SEPARATOR . $help->getLocale() . DIRECTORY_SEPARATOR . $topicId . '.xml';
	}

	function getTopicIdForFilename($filename) {
		$parts = split('/', str_replace('\\', '/', $filename));
		array_shift($parts); // Knock off "plugins"
		array_shift($parts); // Knock off category
		array_shift($parts); // Knock off plugin name
		array_shift($parts); // Knock off "help"
		array_shift($parts); // Knock off locale
		return substr(join('/', $parts), 0, -4); // Knock off .xml
	}

	function getSearchPath($locale = null) {
		if ($locale == '') {
			$help =& Help::getHelp();
			$locale = $help->getLocale();
		}
		return $this->plugin->getPluginPath() . DIRECTORY_SEPARATOR . 'help' . DIRECTORY_SEPARATOR . $locale;
	}
}

?>
