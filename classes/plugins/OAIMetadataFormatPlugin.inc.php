<?php

/**
 * @file classes/plugins/OAIMetadataFormatPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for OAI Metadata format plugins
 */

// $Id$


import('classes.plugins.Plugin');
import('lib.pkp.classes.oai.OAIStruct');

class OAIMetadataFormatPlugin extends Plugin {
	function OAIMetadataFormatPlugin() {
		parent::Plugin();
	}

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			HookRegistry::register('OAI::metadataFormats', array(&$this, 'callback_formatRequest'));
			return true;
		}
		return false;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category, and should be suitable for part of a filename
	 * (ie short, no spaces, and no dependencies on cases being unique).
	 * @return String name of plugin
	 */
	function getName() {
		assert(false); // Should always be overridden
	}

	/**
	 * Get the display name for this plugin.
	 */
	function getDisplayName() {
		assert(false); // Should always be overridden
	}

	/**
	 * Get a description of this plugin.
	 */
	function getDescription() {
		assert(false); // Should always be overridden
	}

	/**
	 * Get the metadata prefix for this plugin's format.
	 */
	function getMetadataPrefix() {
		assert(false); // Should always be overridden
	}

	function getSchema() {
		return '';
	}

	function getNamespace() {
		return '';
	}

	/**
	 * Get a hold of the class that does the formatting.
	 */
	function getFormatClass() {
		assert(false); // Should always be overridden
	}

	function callback_formatRequest($hookName, $args) {
		$namesOnly = $args[0];
		$identifier = $args[1];
		$formats =& $args[2];

		if ($namesOnly) {
			$formats = array_merge($formats,array($this->getMetadataPrefix()));
		} else {
			$formatClass = $this->getFormatClass();
			$formats = array_merge(
				$formats,
				array($this->getMetadataPrefix() => new $formatClass($this->getMetadataPrefix(), $this->getSchema(), $this->getNamespace()))
			);
		}
		return false;
	}
}

?>
