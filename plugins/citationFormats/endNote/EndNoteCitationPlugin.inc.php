<?php

/**
 * @file plugins/citationFormats/endNote/EndNoteCitationPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EndNoteCitationPlugin
 * @ingroup plugins_citationFormats_endNote
 *
 * @brief EndNote citation format plugin
 */

import('classes.plugins.DownloadableCitationPlugin');

class EndNoteCitationPlugin extends DownloadableCitationPlugin {
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
		return 'EndNoteCitationPlugin';
	}

	function getDisplayName() {
		return __('plugins.citationFormats.endNote.displayName');
	}

	function getCitationFormatName() {
		return __('plugins.citationFormats.endNote.citationFormatName');
	}

	function getDescription() {
		return __('plugins.citationFormats.endNote.description');
	}

	/**
	 * Set the headers for a downloadable citation.
	 *
	 * @param $article object
	 * @param $issue object
	 * @param $journal object
	 */
	function setHeaders($article, $issue, $journal) {
		header('Content-Disposition: attachment; filename="' . $article->getId() . '-endNote.enw"');
		header('Content-Type: application/x-endnote-refer');
	}
}

?>
