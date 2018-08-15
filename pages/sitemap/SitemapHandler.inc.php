<?php

/**
 * @file pages/sitemap/SitemapHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SitemapHandler
 * @ingroup pages_sitemap
 *
 * @brief Produce a sitemap in XML format for submitting to search engines.
 */

import('lib.pkp.pages.sitemap.PKPSitemapHandler');

class SitemapHandler extends PKPSitemapHandler {

	/**
	 * @copydoc PKPSitemapHandler_createContextSitemap()
	 */
	function _createContextSitemap($request) {
		$doc = parent::_createContextSitemap($request);
		$root = $doc->documentElement;

		$journal = $request->getJournal();
		$journalId = $journal->getId();

		// Search
		$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'search')));
		// Issues
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		if ($journal->getSetting('publishingMode') != PUBLISHING_MODE_NONE) {
			$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'issue', 'current')));
			$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'issue', 'archive')));
			$publishedIssues = $issueDao->getPublishedIssues($journalId);
			while ($issue = $publishedIssues->next()) {
				$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'issue', 'view', $issue->getId())));
				// Articles for issue
				$articles = $publishedArticleDao->getPublishedArticles($issue->getId());
				foreach($articles as $article) {
					// Abstract
					$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'article', 'view', array($article->getBestArticleId()))));
					// Galley files
					$galleys = $galleyDao->getBySubmissionId($article->getId());
					while ($galley = $galleys->next()) {
						$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'article', 'view', array($article->getBestArticleId(), $galley->getBestGalleyId()))));
					}
				}
			}
		}

		$doc->appendChild($root);

		// Enable plugins to change the sitemap
		HookRegistry::call('SitemapHandler::createJournalSitemap', array(&$doc));

		return $doc;
	}

}


