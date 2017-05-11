<?php

/**
 * @file plugins/oaiMetadataFormats/dc/PKPOAIMetadataFormatPlugin_DC.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPOAIMetadataFormatPlugin_DC
 * @see OAI
 *
 * @brief dc metadata format plugin for OAI.
 */

import('lib.pkp.classes.plugins.OAIMetadataFormatPlugin');

class PKPOAIMetadataFormatPlugin_DC extends OAIMetadataFormatPlugin {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'OAIMetadataFormatPlugin_DC';
	}

	function getDisplayName() {
		return __('plugins.oaiMetadata.dc.displayName');
	}

	function getDescription() {
		return __('plugins.oaiMetadata.dc.description');
	}

	function getFormatClass() {
		return 'OAIMetadataFormat_DC';
	}

	static function getMetadataPrefix() {
		return 'oai_dc';
	}

	static function getSchema() {
		return 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd';
	}

	static function getNamespace() {
		return 'http://www.openarchives.org/OAI/2.0/oai_dc/';
	}
}

?>
