<?php

/**
 * @file classes/plugins/MetadataPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for metadata plugins
 */


import('lib.pkp.classes.plugins.Plugin');

// Define the well-known file name for controlled vocabulary data.
define('METADATA_PLUGIN_VOCAB_DATAFILE', 'controlledVocabs.xml');

abstract class MetadataPlugin extends Plugin {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Override public methods from Plugin
	//
	/**
	 * @see Plugin::register()
	 */
	function register($category, $path) {
		if (!parent::register($category, $path)) return false;
		$this->addLocaleData();
		return true;
	}

	/**
	 * This implementation looks for files that contain controlled
	 * vocabulary data. It can discover and return more than one file.
	 * @see Plugin::getInstallControlledVocabFiles()
	 * @return array|null
	 */
	function getInstallControlledVocabFiles() {
		// Search the well-known locations for vocabulary data files. If
		// one is found then return it.
		$pluginPath = $this->getPluginPath();
		$wellKnownVocabLocations = array(
			'./'.$pluginPath.'/schema/'.METADATA_PLUGIN_VOCAB_DATAFILE,
			'./lib/pkp/'.$pluginPath.'/schema/'.METADATA_PLUGIN_VOCAB_DATAFILE
		);

		$controlledVocabFiles = parent::getInstallControlledVocabFiles();
		foreach ($wellKnownVocabLocations as $wellKnownVocabLocation) {
			if (file_exists($wellKnownVocabLocation)) $controlledVocabFiles[] = $wellKnownVocabLocation;
		}
		return $controlledVocabFiles;
	}
}

?>
