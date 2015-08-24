<?php

/**
 * @file classes/plugins/CitationPlugin.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CitationPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for citation plugins
 */

import('lib.pkp.classes.plugins.Plugin');

abstract class CitationPlugin extends Plugin {
	/**
	 * Constructor
	 */
	function CitationPlugin() {
		parent::Plugin();
	}

	/**
	 * Get the citation format name for this plugin.
	 */
	abstract function getCitationFormatName();

	/**
	 * Used by the cite function to embed an HTML citation in the
	 * templates/rt/captureCite.tpl template, which ships with OJS.
	 * @param $hookName string Hook name
	 * @param $args array Hook arguments
	 * @return boolean Hook processing status
	 */
	function displayCitationHook($hookName, $args) {
		$params =& $args[0];
		$templateMgr =& $args[1];
		$output =& $args[2];

		$output .= $templateMgr->fetch($this->getTemplatePath() . '/citation.tpl');
		return true;
	}

	/**
	 * Display an HTML-formatted citation. Default implementation displays
	 * an HTML-based citation using the citation.tpl template in the plugin
	 * path.
	 * @param $article Article
	 * @param $issue Issue
	 * @param $journal Journal
	 */
	function displayCitation($article, $issue, $journal) {
		HookRegistry::register('Template::RT::CaptureCite', array($this, 'displayCitationHook'));
		$templateMgr = TemplateManager::getManager($this->getRequest());
		$templateMgr->assign('citationPlugin', $this);
		$templateMgr->assign('article', $article);
		$templateMgr->assign('issue', $issue);
		$templateMgr->assign('journal', $journal);
		$templateMgr->display('rt/captureCite.tpl');
	}

	/**
	 * Return an HTML-formatted citation. Default implementation displays
	 * an HTML-based citation using the citation.tpl template in the plugin
	 * path.
	 * @param $article Article
	 * @param $issue Issue
	 * @param $journal Journal
	 */
	function fetchCitation($article, $issue, $journal) {
		$templateMgr = TemplateManager::getManager($this->getRequest());
		$templateMgr->assign('citationPlugin', $this);
		$templateMgr->assign('article', $article);
		$templateMgr->assign('issue', $issue);
		$templateMgr->assign('journal', $journal);
		return $templateMgr->fetch($this->getTemplatePath() . '/citation.tpl');
	}
}

?>
