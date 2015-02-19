<?php

/**
 * @file plugins/oaiMetadataFormats/marc/OAIMetadataFormatPlugin_MARC.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin_MARC
 * @ingroup oai_format
 * @see OAI
 *
 * @brief marc metadata format plugin for OAI.
 */

import('lib.pkp.classes.plugins.OAIMetadataFormatPlugin');

class OAIMetadataFormatPlugin_MARC extends OAIMetadataFormatPlugin {

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'OAIFormatPlugin_MARC';
	}

	function getDisplayName() {
		return __('plugins.OAIMetadata.marc.displayName');
	}

	function getDescription() {
		return __('plugins.OAIMetadata.marc.description');
	}

	function getFormatClass() {
		return 'OAIMetadataFormat_MARC';
	}

	function getMetadataPrefix() {
		return 'oai_marc';
	}

	function getSchema() {
		return 'http://www.openarchives.org/OAI/1.1/oai_marc.xsd';
	}

	function getNamespace() {
		return 'http://www.openarchives.org/OAI/1.1/oai_marc';
	}
}

?>
