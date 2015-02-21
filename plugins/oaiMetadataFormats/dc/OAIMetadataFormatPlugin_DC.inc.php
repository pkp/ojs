<?php

/**
 * @file plugins/oaiMetadataFormats/dc/OAIMetadataFormatPlugin_DC.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin_DC
 * @ingroup oai_format
 * @see OAI
 *
 * @brief dc metadata format plugin for OAI.
 */

import('lib.pkp.plugins.oaiMetadataFormats.dc.PKPOAIMetadataFormatPlugin_DC');

class OAIMetadataFormatPlugin_DC extends PKPOAIMetadataFormatPlugin_DC {
	/**
	 * Constructor
	 */
	function OAIMetadataFormatPlugin_DC() {
		parent::PKPOAIMetadataFormatPlugin_DC();
	}
}

?>
