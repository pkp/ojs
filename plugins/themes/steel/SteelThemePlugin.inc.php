<?php

/**
 * @file SteelThemePlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SteelThemePlugin
 * @ingroup plugins_themes_steel
 *
 * @brief "Steel" theme plugin
 */

// $Id$


import('classes.plugins.ThemePlugin');

class SteelThemePlugin extends ThemePlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'SteelThemePlugin';
	}

	function getDisplayName() {
		return 'Steel Theme';
	}

	function getDescription() {
		return 'Steel layout';
	}

	function getStylesheetFilename() {
		return 'steel.css';
	}

	function getLocaleFilename($locale) {
		return null; // No locale data
	}
}

?>
