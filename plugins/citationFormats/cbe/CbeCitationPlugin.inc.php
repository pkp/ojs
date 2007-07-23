<?php

/**
 * @file CbeCitationPlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 * @class CbeCitationPlugin
 *
 * CBE citation format plugin
 *
 * $Id$
 */

import('classes.plugins.CitationPlugin');

class CbeCitationPlugin extends CitationPlugin {
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'CbeCitationPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.citationFormats.cbe.displayName');
	}

	function getCitationFormatName() {
		return Locale::translate('plugins.citationFormats.cbe.citationFormatName');
	}

	function getDescription() {
		return Locale::translate('plugins.citationFormats.cbe.description');
	}

}

?>
