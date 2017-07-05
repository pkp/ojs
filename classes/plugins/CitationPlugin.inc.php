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
	 * Return an HTML-formatted citation. Default implementation displays
	 * an HTML-based citation using the citation.tpl template in the plugin
	 * path.
	 * @param $article Article
	 * @param $issue Issue
	 * @param $journal Journal
	 */
	function fetchCitation($article, $issue, $journal) {
		$templateMgr = TemplateManager::getManager($this->getRequest());
		$templateMgr->assign(array(
			'citationPlugin' => $this,
			'article' => $article,
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
