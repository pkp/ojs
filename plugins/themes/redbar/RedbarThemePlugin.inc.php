<?php

/**
 * @file RedbarThemePlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RedbarThemePlugin
 * @ingroup plugins_themes_redbar
 *
 * @brief "Redbar" theme plugin
 */

// $Id$


import('classes.plugins.ThemePlugin');

class RedbarThemePlugin extends ThemePlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'RedbarThemePlugin';
	}

	function getDisplayName() {
		return 'Redbar Theme';
	}

	function getDescription() {
		return 'Redbar layout';
	}

	function getStylesheetFilename() {
		return 'redbar.css';
	}

	function getLocaleFilename($locale) {
		return null; // No locale data
	}
}

?>
