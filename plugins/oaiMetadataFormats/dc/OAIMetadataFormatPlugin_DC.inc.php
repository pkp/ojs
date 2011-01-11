<?php

/**
 * @file plugins/oaiMetadata/dc/OAIMetadataFormatPlugin_DC.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin_DC
 * @ingroup oai_format
 * @see OAI
 *
 * @brief dc metadata format plugin for OAI.
 */

import('classes.plugins.OAIMetadataFormatPlugin');

class OAIMetadataFormatPlugin_DC extends OAIMetadataFormatPlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'OAIMetadataFormatPlugin_DC';
	}

	function getDisplayName() {
		return Locale::translate('plugins.oaiMetadata.dc.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.oaiMetadata.dc.description');
	}

	function getFormatClass() {
		return 'OAIMetadataFormat_DC';
	}

	function getMetadataPrefix() {
		return 'oai_dc';
	}

	function getSchema() {
		return 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd';
	}

	function getNamespace() {
		return 'http://www.openarchives.org/OAI/2.0/oai_dc/';
	}
}

?>
