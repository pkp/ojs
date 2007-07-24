<?php

/**
 * @file EndNoteCitationPlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.citationFormats.endNote
 * @class EndNoteCitationPlugin
 *
 * EndNote citation format plugin
 *
 * $Id$
 */

import('classes.plugins.CitationPlugin');

class EndNoteCitationPlugin extends CitationPlugin {
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
		return Locale::translate('plugins.citationFormats.endNote.displayName');
	}

	function getCitationFormatName() {
		return Locale::translate('plugins.citationFormats.endNote.citationFormatName');
	}

	function getDescription() {
		return Locale::translate('plugins.citationFormats.endNote.description');
	}

	/**
	 * Return a custom-formatted citation.
	 * @param $article object
	 * @param $issue object
	 */
	function cite(&$article, &$issue) {
		header('Content-Disposition: attachment; filename="' . $article->getArticleId() . '-endNote.enw"');
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->display($this->getTemplatePath() . '/citation.tpl', 'application/x-endnote-refer');
	}
}

?>
