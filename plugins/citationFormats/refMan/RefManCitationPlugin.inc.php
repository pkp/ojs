<?php

/**
 * RefManCitationPlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Reference Manager citation format plugin
 *
 * $Id$
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
		return Locale::translate('plugins.citationFormats.refMan.displayName');
	}

	function getCitationFormatName() {
		return Locale::translate('plugins.citationFormats.refMan.citationFormatName');
	}

	function getDescription() {
		return Locale::translate('plugins.citationFormats.refMan.description');
	}

	/**
	 * Return a custom-formatted citation.
	 * @param $article object
	 * @param $issue object
	 */
	function cite(&$article, &$issue) {
		header('Content-Disposition: attachment; filename="' . $article->getArticleId() . '-refMan.ris"');
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->display($this->getTemplatePath() . '/citation.tpl', 'application/x-Research-Info-Systems');
	}
}

?>
