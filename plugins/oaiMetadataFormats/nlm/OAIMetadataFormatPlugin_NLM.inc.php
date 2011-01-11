<?php

/**
 * @file plugins/oaiMetadata/dc/OAIMetadataFormatPlugin_NLM.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin_NLM
 * @ingroup oai_format_nlm
 * @see OAI
 *
 * @brief NLM Journal Article metadata format plugin for OAI.
 */

import('classes.plugins.OAIMetadataFormatPlugin');

class OAIMetadataFormatPlugin_NLM extends OAIMetadataFormatPlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'OAIMetadataFormatPlugin_NLM';
	}

	function getDisplayName() {
		return Locale::translate('plugins.oaiMetadata.nlm.displayName');
	}

	function getDescription() {
		return Locale::translate('plugins.oaiMetadata.nlm.description');
	}

	function getFormatClass() {
		return 'OAIMetadataFormat_NLM';
	}

	function getMetadataPrefix() {
		return 'nlm';
	}

	function getSchema() {
		return 'http://dtd.nlm.nih.gov/publishing/2.3/xsd/journalpublishing.xsd';
	}

	function getNamespace() {
		return 'http://td.nlm.nih.gov/publishing/2.3';
	}
}

?>
