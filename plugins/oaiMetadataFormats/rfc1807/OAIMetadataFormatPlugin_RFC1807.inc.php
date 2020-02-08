<?php

/**
 * @file plugins/oaiMetadataFormats/rfc1807/OAIMetadataFormatPlugin_RFC1807.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin_RFC1807
 * @ingroup oai_format
 * @see OAI
 *
 * @brief rfc1807 metadata format plugin for OAI.
 */

import('lib.pkp.classes.plugins.OAIMetadataFormatPlugin');

class OAIMetadataFormatPlugin_RFC1807 extends OAIMetadataFormatPlugin {

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'OAIFormatPlugin_RFC1807';
	}

	function getDisplayName() {
		return __('plugins.OAIMetadata.rfc1807.displayName');
	}

	function getDescription() {
		return __('plugins.OAIMetadata.rfc1807.description');
	}

	function getFormatClass() {
		return 'OAIMetadataFormat_RFC1807';
	}

	static function getMetadataPrefix() {
		return 'rfc1807';
	}

	static function getSchema() {
		return 'http://www.openarchives.org/OAI/1.1/rfc1807.xsd';
	}

	static function getNamespace() {
		return 'http://info.internet.isi.edu:80/in-notes/rfc/files/rfc1807.txt';
	}
}


