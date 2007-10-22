<?php

/**
 * @file VanillaThemePlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.themes.vanilla
 * @class VanillaThemePlugin
 *
 * "Vanilla" theme plugin
 *
 * $Id$
 */

import('classes.plugins.ThemePlugin');

class VanillaThemePlugin extends ThemePlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'VanillaThemePlugin';
	}

	function getDisplayName() {
		return 'Vanilla Theme';
	}

	function getDescription() {
		return 'Light, plain, spacious layout';
	}

	function getStylesheetFilename() {
		return 'vanilla.css';
	}

	function getLocaleFilename($locale) {
		return null; // No locale data
	}
}

?>
