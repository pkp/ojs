<?php

/**
 * @file plugins/oaiMetadataFormats/nlm/OAIMetadataFormatPlugin_NLM.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin_NLM
 * @ingroup oai_format_nlm
 * @see OAI
 *
 * @brief NLM Journal Article metadata format plugin for OAI.
 */

import('lib.pkp.classes.plugins.OAIMetadataFormatPlugin');

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
		return __('plugins.oaiMetadata.nlm.displayName');
	}

	function getDescription() {
		return __('plugins.oaiMetadata.nlm.description');
	}

	function getFormatClass() {
		return 'OAIMetadataFormat_NLM';
	}

	static function getMetadataPrefix() {
		return 'nlm';
	}

	static function getSchema() {
		return 'http://dtd.nlm.nih.gov/publishing/2.3/xsd/journalpublishing.xsd';
	}

	static function getNamespace() {
		return 'http://td.nlm.nih.gov/publishing/2.3';
	}
}

?>
