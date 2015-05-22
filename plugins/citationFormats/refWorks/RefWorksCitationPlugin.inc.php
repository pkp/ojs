<?php

/**
 * @file plugins/citationFormats/refWorks/RefWorksCitationPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RefWorksCitationPlugin
 * @ingroup plugins_citationFormats_refWorks
 *
 * @brief RefWorks citation format plugin
 */

import('classes.plugins.CitationPlugin');

class RefWorksCitationPlugin extends CitationPlugin {
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'RefWorksCitationPlugin';
	}

	function getDisplayName() {
		return __('plugins.citationFormats.refWorks.displayName');
	}

	function getCitationFormatName() {
		return __('plugins.citationFormats.refWorks.citationFormatName');
	}

	function getDescription() {
		return __('plugins.citationFormats.refWorks.description');
	}

}

?>
