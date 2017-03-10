<?php
/**
 * @file classes/plugins/DownloadableCitationPlugin.inc.php
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

import('classes.plugins.CitationPlugin');

abstract class DownloadableCitationPlugin extends CitationPlugin {

	/**
	 * Return an HTML-formatted citation. Default implementation displays
	 * an HTML-based citation using the citation.tpl template in the plugin
	 * path.
	 * @param $article Article
	 * @param $issue Issue
	 * @param $journal Journal
	 */
	function downloadCitation($article, $issue, $journal, $version) {
		$output = parent::fetchCitation($article, $issue, $journal, $version);
		$this->setHeaders($article, $issue, $journal);
		echo $output;
	}

	/**
	 * Set the headers for a downloadable citation.
	 *
	 * @param $article Article
	 * @param $issue Issue
	 * @param $journal Journal
	 */
	abstract function setHeaders($article, $issue, $journal);

	/**
	 * Whether this citation format is a downloadable file format (eg - EndNote)
	 *
	 * @return bool
	 */
	function isDownloadable() {
		return true;
	}
}

?>
