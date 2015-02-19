<?php

/**
 * @defgroup plugins_metadata_mods34
 */

/**
 * @file plugins/metadata/mods34/Mods34MetadataPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Mods34MetadataPlugin
 * @ingroup plugins_metadata_mods34
 *
 * @brief MODS 3.4 metadata plugin
 */


import('lib.pkp.plugins.metadata.mods34.PKPMods34MetadataPlugin');

class Mods34MetadataPlugin extends PKPMods34MetadataPlugin {
	/**
	 * Constructor
	 */
	function Mods34MetadataPlugin() {
		parent::PKPMods34MetadataPlugin();
	}
}

?>
