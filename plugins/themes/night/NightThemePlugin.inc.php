<?php

/**
 * @file NightThemePlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NightThemePlugin
 * @ingroup plugins_themes_night
 *
 * @brief "Night" theme plugin
 */

// $Id$


import('classes.plugins.ThemePlugin');

class NightThemePlugin extends ThemePlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'NightThemePlugin';
	}

	function getDisplayName() {
		return 'Night Theme';
	}

	function getDescription() {
		return 'Dark layout';
	}

	function getStylesheetFilename() {
		return 'night.css';
	}

	function getLocaleFilename($locale) {
		return null; // No locale data
	}
}

?>
