<?php

/**
 * @file classes/plugins/CitationPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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
	function __construct() {
		parent::__construct();

		if ($this->getEnabled()) {
			HookRegistry::register('TemplateManager::display', array($this, 'loadJavaScript'));
		}
	}

	/**
	 * Get the citation format name for this plugin.
	 */
	abstract function getCitationFormatName();

	/**
	 * Load the JavaScript file to retrieve citation formats
	 *
	 * @param $hookName string Hook name
	 * @param $args array Hook arguments. See `TemplateManager::display`
	 * @return null
	 */
	function loadJavaScript($hookName, $args) {
		$templateMgr =& $args[0];

		$templateMgr->addJavaScript(
			'citationFormats',
			$this->getRequest()->getBaseUrl() . '/js/plugins/citationFormats.js',
			array(
				'context' => 'frontend-article-view',
			)
		);
	}

	/**
	 * Return an HTML-formatted citation. Default implementation displays
	 * an HTML-based citation using the citation.tpl template in the plugin
	 * path.
	 * @param $article Article
	 * @param $issue Issue
	 * @param $journal Journal
	 */
	function fetchCitation($article, $issue, $journal, $version) {
		$templateMgr = TemplateManager::getManager($this->getRequest());
		$templateMgr->assign(array(
			'citationPlugin' => $this,
			'article' => $article,
			'version' => $version,
			'issue' => $issue,
			'journal' => $journal,
		));
		return $templateMgr->fetch($this->getTemplatePath() . '/citation.tpl');
	}

	/**
	 * Whether this citation format is a downloadable file format (eg - EndNote)
	 *
	 * @return bool
	 */
	function isDownloadable() {
		return false;
	}

}

?>
