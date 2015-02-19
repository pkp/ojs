<?php

/**
 * @file BlueSteelThemePlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BlueSteelThemePlugin
 * @ingroup plugins_themes_blueSteel
 *
 * @brief "BlueSteel" theme plugin
 */

import('classes.plugins.ThemePlugin');

class BlueSteelThemePlugin extends ThemePlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'BlueSteelThemePlugin';
	}

	function getDisplayName() {
		return 'BlueSteel Theme';
	}

	function getDescription() {
		return 'Stylesheet with blue header bar and embossed text';
	}

	function getStylesheetFilename() {
		return 'blueSteel.css';
	}

	function getLocaleFilename($locale) {
		return null; // No locale data
	}
}

?>
