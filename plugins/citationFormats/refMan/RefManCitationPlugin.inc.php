<?php

/**
 * @file plugins/citationFormats/refMan/RefManCitationPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RefManCitationPlugin
 * @ingroup plugins_citationFormats_refMan
 *
 * @brief Reference Manager citation format plugin
 */

import('classes.plugins.CitationPlugin');

class RefManCitationPlugin extends CitationPlugin {
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
		return 'RefManCitationPlugin';
	}

	function getDisplayName() {
		return __('plugins.citationFormats.refMan.displayName');
	}

	function getCitationFormatName() {
		return __('plugins.citationFormats.refMan.citationFormatName');
	}

	function getDescription() {
		return __('plugins.citationFormats.refMan.description');
	}

	/**
	 * Display a custom-formatted citation.
	 * @param $article object
	 * @param $issue object
	 * @param $journal object
	 */
	function displayCitation(&$article, &$issue, &$journal) {
		header('Content-Disposition: attachment; filename="' . $article->getId() . '-refMan.ris"');
		header('Content-Type: application/x-Research-Info-Systems');
		echo parent::fetchCitation($article, $issue, $journal);
	}
}

?>
