<?php

/**
 * @file classes/plugins/ThemePlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ThemePlugin
 * @ingroup plugins
 *
 * @brief Abstract class for theme plugins
 */

// $Id$


class ThemePlugin extends Plugin {
	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		// This should not be used as this is an abstract class
		return 'ThemePlugin';
	}

	/**
	 * Get the display name of this plugin. This name is displayed on the
	 * Journal Manager's setup page 5, for example.
	 * @return String
	 */
	function getDisplayName() {
		// This name should never be displayed because child classes
		// will override this method.
		return 'Abstract Theme Plugin';
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return 'This is the ThemePlugin base class. Its functions can be overridden by subclasses to provide theming support.';
	}

	/**
	 * Activate the theme.
	 */
	function activate(&$templateMgr) {
		// Subclasses may override this function.

		if (($stylesheetFilename = $this->getStylesheetFilename()) != null) {
			$path = Request::getBaseUrl() . '/' . $this->getPluginPath() . '/' . $stylesheetFilename;
			$templateMgr->addStyleSheet($path);
		}
	}
}
?>
