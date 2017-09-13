<?php

/**
 * @file plugins/oaiMetadataFormats/jats/OAIMetadataFormatPlugin_JATS.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormatPlugin_JATS
 * @ingroup oai_format_jats
 * @see OAI
 *
 * @brief JATS XML format plugin for OAI.
 */

import('lib.pkp.classes.plugins.OAIMetadataFormatPlugin');

class OAIMetadataFormatPlugin_JATS extends OAIMetadataFormatPlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'OAIMetadataFormatPlugin_JATS';
	}

	function getDisplayName() {
		return __('plugins.oaiMetadata.jats.displayName');
	}

	function getDescription() {
		return __('plugins.oaiMetadata.jats.description');
	}

	/**
	 * Determine whether the plugin can be enabled.
	 * @return boolean
	 */
	function getCanEnable() {
		return true;
	}

	/**
	 * Determine whether the plugin can be disabled.
	 * @return boolean
	 */
	function getCanDisable() {
		return true;
	}

	/**
	 * Determine whether the plugin is enabled.
	 * @return boolean
	 */
	function getEnabled() {
		$request = PKPApplication::getRequest();
		if (!$request) return false;
		$context = $request->getContext();
		if (!$context) return false;
		return $this->getSetting($context->getId(), 'enabled');
	}

	/**
	 * Set whether the plugin is enabled.
	 * @param $enabled boolean
	 */
	function setEnabled($enabled) {
		$request = PKPApplication::getRequest();
		$context = $request->getContext();
		$this->updateSetting($context->getId(), 'enabled', $enabled, 'bool');
	}

	function getFormatClass() {
		return 'OAIMetadataFormat_JATS';
	}

	static function getMetadataPrefix() {
		return 'jats';
	}

	static function getSchema() {
		return 'https://jats.nlm.nih.gov/publishing/0.4/xsd/JATS-journalpublishing0.xsd';
	}

	static function getNamespace() {
		return 'http://jats.nlm.nih.gov';
	}
}

?>
