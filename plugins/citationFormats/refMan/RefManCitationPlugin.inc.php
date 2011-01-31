<?php

/**
 * @file RefManCitationPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RefManCitationPlugin
 * @ingroup plugins_citationFormats_refMan
 *
 * @brief Reference Manager citation format plugin
 */

// $Id$


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
		return Locale::translate('plugins.citationFormats.refMan.displayName');
	}

	function getCitationFormatName() {
		return Locale::translate('plugins.citationFormats.refMan.citationFormatName');
	}

	function getDescription() {
		return Locale::translate('plugins.citationFormats.refMan.description');
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
