<?php

/**
 * @file plugins/articleGalleys/pdfArticleGalley/PdfArticleGalleyPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DownloadableFileArticleGalleyPlugin
 * @ingroup plugins_articleGalleys_downloadableFileArticleGalley
 *
 * @brief Class for DownloadableFileArticleGalleyPlugin plugin
 */

import('classes.plugins.ArticleGalleyPlugin');

class DownloadableFileArticleGalleyPlugin extends ArticleGalleyPlugin {
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
		return __('plugins.articleGalleys.downloadableFileArticleGalley.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.articleGalleys.downloadableFileArticleGalley.description');
	}

	/**
	 * @see ArticleGalleyPlugin::getArticleGalley
	 */
	function getArticleGalley(&$templateMgr, $request = null, $params) {
		$journal = $request->getJournal();
		if (!$journal) return '';

		return parent::getArticleGalley($templateMgr, $request, $params);
	}
}

?>
