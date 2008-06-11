<?php

/**
 * @file ApaCitationPlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.citationFormats.apa
 * @class ApaCitationPlugin
 *
 * APA citation format plugin
 *
 * $Id$
 */

import('classes.plugins.CitationPlugin');

class ApaCitationPlugin extends CitationPlugin {
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
		return 'ApaCitationPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.citationFormats.apa.displayName');
	}

	function getCitationFormatName() {
		return Locale::translate('plugins.citationFormats.apa.citationFormatName');
	}

	function getDescription() {
		return Locale::translate('plugins.citationFormats.apa.description');
	}

	/**
	 * Return an HTML-formatted citation. Default implementation displays
	 * an HTML-based citation using the citation.tpl template in the plugin
	 * path.
	 * @param $article object
	 * @param $issue object
	 */
	function cite(&$article, &$issue) {
		$loweredTitle = String::strtolower($article->getArticleTitle());
		$apaCapitalized = String::ucfirst($loweredTitle);

		HookRegistry::register('Template::RT::CaptureCite', array(&$this, 'displayCitation'));
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('citationPlugin', $this);
		$templateMgr->assign('apaCapitalized', $apaCapitalized);
		$templateMgr->display('rt/captureCite.tpl');
	}
}

?>
