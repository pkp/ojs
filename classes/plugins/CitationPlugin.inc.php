<?php

/**
 * @file classes/plugins/CitationPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CitationPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for citation plugins
 */

import('classes.plugins.Plugin');

class CitationPlugin extends Plugin {
	function CitationPlugin() {
		parent::Plugin();
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		assert(false); // Should always be overridden
	}

	/**
	 * Get the display name of this plugin. This name is displayed on the
	 * Journal Manager's setup page 5, for example.
	 * @return String
	 */
	function getDisplayName() {
		// This name should never be displayed because child classes
		// will override this method.
		return 'Abstract Citation Plugin';
	}

	/**
	 * Get the citation format name for this plugin.
	 */
	function getCitationFormatName() {
		// must be implemented by sub-classes
		assert(false);
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return 'This is the CitationPlugin base class. Its functions can be overridden by subclasses to provide citation support.';
	}

	/**
	 * Used by the cite function to embed an HTML citation in the
	 * templates/rt/captureCite.tpl template, which ships with OJS.
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
	 * @param $article object
	 * @param $issue object
	 */
	function displayCitation(&$article, &$issue, &$journal) {
		HookRegistry::register('Template::RT::CaptureCite', array(&$this, 'displayCitationHook'));
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('citationPlugin', $this);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign_by_ref('journal', $journal);
		$templateMgr->display('rt/captureCite.tpl');
	}

	/**
	 * Return an HTML-formatted citation. Default implementation displays
	 * an HTML-based citation using the citation.tpl template in the plugin
	 * path.
	 * @param $article object
	 * @param $issue object
	 */
	function fetchCitation(&$article, &$issue, &$journal) {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('citationPlugin', $this);
		$templateMgr->assign_by_ref('article', $article);
		$templateMgr->assign_by_ref('issue', $issue);
		$templateMgr->assign_by_ref('journal', $journal);
		return $templateMgr->fetch($this->getTemplatePath() . '/citation.tpl');
	}
}

?>
