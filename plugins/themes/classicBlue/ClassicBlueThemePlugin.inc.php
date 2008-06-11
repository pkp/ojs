<?php

/**
 * @file ClassicBlueThemePlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.themes.classicBlue
 * @class ClassicBlueThemePlugin
 *
 * "ClassicBlue" theme plugin
 *
 * $Id$
 */

import('classes.plugins.ThemePlugin');

class ClassicBlueThemePlugin extends ThemePlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'ClassicBlueThemePlugin';
	}

	function getDisplayName() {
		return 'ClassicBlue Theme';
	}

	function getDescription() {
		return 'Classic blue layout';
	}

	function getStylesheetFilename() {
		return 'classicBlue.css';
	}

	function getLocaleFilename($locale) {
		return null; // No locale data
	}
}

?>
