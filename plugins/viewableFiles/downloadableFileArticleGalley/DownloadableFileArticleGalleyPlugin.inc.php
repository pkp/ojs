<?php

/**
 * @file plugins/viewableFiles/pdfArticleGalley/PdfArticleGalleyPlugin.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DownloadableFileArticleGalleyPlugin
 * @ingroup plugins_viewableFiles_downloadableFileArticleGalley
 *
 * @brief Class for DownloadableFileArticleGalleyPlugin plugin
 */

import('classes.plugins.ViewableFilePlugin');

class DownloadableFileArticleGalleyPlugin extends ViewableFilePlugin {
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
		return __('plugins.viewableFiles.downloadableFileArticleGalley.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.viewableFiles.downloadableFileArticleGalley.description');
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
