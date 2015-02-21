<?php

/**
 * @defgroup plugins_metadata_nlm30
 */

/**
 * @file plugins/metadata/nlm30/Nlm30MetadataPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Nlm30MetadataPlugin
 * @ingroup plugins_metadata_nlm30
 *
 * @brief NLM 3.0 metadata plugin
 */


import('lib.pkp.plugins.metadata.nlm30.PKPNlm30MetadataPlugin');

class Nlm30MetadataPlugin extends PKPNlm30MetadataPlugin {
	/**
	 * Constructor
	 */
	function Nlm30MetadataPlugin() {
		parent::PKPNlm30MetadataPlugin();
	}
}

?>
