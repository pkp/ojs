<?php

/**
 * LilacThemePlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * "Lilac" theme plugin
 *
 * $Id$
 */

import('classes.plugins.ThemePlugin');

class LilacThemePlugin extends ThemePlugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'LilacThemePlugin';
	}

	function getDisplayName() {
		return 'Lilac Theme';
	}

	function getDescription() {
		return 'Lilac-themed layout';
	}

	function getStylesheetFilename() {
		return 'lilac.css';
	}

}

?>
