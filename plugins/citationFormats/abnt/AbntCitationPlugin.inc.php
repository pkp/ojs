<?php

/**
 * @file AbntCitationPlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.citationFormats.abnt
 * @class AbntCitationPlugin
 *
 * ABNT citation format plugin
 *
 * $Id$
 */

import('classes.plugins.CitationPlugin');

class AbntCitationPlugin extends CitationPlugin {
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
		return 'AbntCitationPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.citationFormats.abnt.displayName');
	}

	function getCitationFormatName() {
		return Locale::translate('plugins.citationFormats.abnt.citationFormatName');
	}

	function getDescription() {
		return Locale::translate('plugins.citationFormats.abnt.description');
	}

}

?>
