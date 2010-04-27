<?php

/**
 * @file BibtexCitationPlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class BibtexCitationPlugin
 * @ingroup plugins_citationFormats_bibtex
 *
 * @brief BibTeX citation format plugin
 */

// $Id$


import('classes.plugins.CitationPlugin');

class BibtexCitationPlugin extends CitationPlugin {
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->register_modifier('bibtex_escape', array(&$this, 'bibtexEscape'));

		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'BibtexCitationPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.citationFormats.bibtex.displayName');
	}

	function getCitationFormatName() {
		return Locale::translate('plugins.citationFormats.bibtex.citationFormatName');
	}

	function getDescription() {
		return Locale::translate('plugins.citationFormats.bibtex.description');
	}

	/**
	 * @function bibtex_escape Escape strings for inclusion in BibTeX cites
	 * @param $arg string
	 * @return string
	 */
	function bibtexEscape($arg) {
		return htmlspecialchars($returner = str_replace(
			array('{', '}', '$','"', '&apos;'),
			array('\\{', '\\}', '\\$', '\\"', '\''),
			html_entity_decode($arg, ENT_QUOTES, 'UTF-8')
		));
	}
}

?>
