<?php

/**
 * @file MlaCitationPlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.citationFormats.mla
 * @class MlaCitationPlugin
 *
 * MLA citation format plugin
 *
 * $Id$
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
		return Locale::translate('plugins.citationFormats.mla.displayName');
	}

	function getCitationFormatName() {
		return Locale::translate('plugins.citationFormats.mla.citationFormatName');
	}

	function getDescription() {
		return Locale::translate('plugins.citationFormats.mla.description');
	}

}

?>
