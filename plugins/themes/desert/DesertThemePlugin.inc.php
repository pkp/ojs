<?php

/**
 * @file DesertThemePlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DesertThemePlugin
 * @ingroup plugins_themes_desert
 *
 * @brief "Desert" theme plugin
 */

// $Id$


import('classes.plugins.ThemePlugin');

class DesertThemePlugin extends ThemePlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'DesertThemePlugin';
	}

	function getDisplayName() {
		return 'Desert Theme';
	}

	function getDescription() {
		return 'Desert layout';
	}

	function getStylesheetFilename() {
		return 'desert.css';
	}

	function getLocaleFilename($locale) {
		return null; // No locale data
	}
}

?>
