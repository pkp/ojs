<?php

/**
 * @file plugins/generic/pdfArticleGalley/PdfArticleGalleyPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DownloadableFileArticleGalleyPlugin
 * @ingroup plugins_generic_downloadableFileArticleGalley
 *
 * @brief Class for DownloadableFileArticleGalleyPlugin plugin
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class DownloadableFileArticleGalleyPlugin extends GenericPlugin {
	/**
	 * Install default settings on journal creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.generic.downloadableFileArticleGalley.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.generic.downloadableFileArticleGalley.description');
	}

	/**
	 * @see ViewableFilePlugin::displayArticleGalley
	 */
	function displayArticleGalley($templateMgr, $request, $params) {
		$journal = $request->getJournal();
		if (!$journal) return '';

		return parent::displayArticleGalley($templateMgr, $request, $params);
	}
}

?>
