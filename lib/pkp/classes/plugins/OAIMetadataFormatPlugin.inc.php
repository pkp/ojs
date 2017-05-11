<?php

/**
 * @file lib/pkp/classes/plugins/OAIMetadataFormatPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for OAI Metadata format plugins
 */

import('lib.pkp.classes.plugins.Plugin');
import('lib.pkp.classes.oai.OAIStruct');

abstract class OAIMetadataFormatPlugin extends Plugin {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		if (!parent::register($category, $path)) return false;
		$this->addLocaleData();
		HookRegistry::register('OAI::metadataFormats', array($this, 'callback_formatRequest'));
		return true;
	}

	/**
	 * Get the metadata prefix for this plugin's format.
	 */
	static function getMetadataPrefix() {
		assert(false); // Should always be overridden
	}

	static function getSchema() {
		return '';
	}

	static function getNamespace() {
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
