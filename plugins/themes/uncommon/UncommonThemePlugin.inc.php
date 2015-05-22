<?php

/**
 * @file plugins/themes/uncommon/UncommonThemePlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UncommonThemePlugin
 * @ingroup plugins_themes_uncommon
 *
 * @brief "Uncommon" theme plugin
 */

import('classes.plugins.ThemePlugin');

class UncommonThemePlugin extends ThemePlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'UncommonThemePlugin';
	}

	function getDisplayName() {
		return 'Uncommon Theme';
	}

	function getDescription() {
		return 'Chunky, blue, solid layout';
	}

	function getStylesheetFilename() {
		return 'uncommon.css';
	}
	function getLocaleFilename($locale) {
		return null; // No locale data
	}
}

?>
