<?php

/**
 * @file plugins/citationFormats/mla/MlaCitationPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MlaCitationPlugin
 * @ingroup plugins_citationFormats_mla
 *
 * @brief MLA citation format plugin
 */

import('classes.plugins.CitationPlugin');

class MlaCitationPlugin extends CitationPlugin {
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
		return 'MlaCitationPlugin';
	}

	function getDisplayName() {
		return __('plugins.citationFormats.mla.displayName');
	}

	function getCitationFormatName() {
		return __('plugins.citationFormats.mla.citationFormatName');
	}

	function getDescription() {
		return __('plugins.citationFormats.mla.description');
	}

}

?>
