<?php
/**
 * @defgroup plugins_metadata_dc11 Dublin Core 1.1 Metadata Format
 */

/**
 * @file plugins/metadata/dc11/PKPDc11MetadataPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPDc11MetadataPlugin
 * @ingroup plugins_metadata_dc11
 *
 * @brief Abstract base class for Dublin Core version 1.1 metadata plugins
 */


import('lib.pkp.classes.plugins.MetadataPlugin');

class PKPDc11MetadataPlugin extends MetadataPlugin {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Override protected template methods from Plugin
	//
	/**
	 * @copydoc Plugin::getName()
	 */
	function getName() {
		return 'Dc11MetadataPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.metadata.dc11.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.metadata.dc11.description');
	}
}

?>
