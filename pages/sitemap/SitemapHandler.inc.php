<?php

/**
 * @file pages/sitemap/SitemapHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
		if ($journal->getData('publishingMode') != PUBLISHING_MODE_NONE) {
			$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'issue', 'current')));
			$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'issue', 'archive')));
			$publishedIssues = $issueDao->getPublishedIssues($journalId);
			while ($issue = $publishedIssues->next()) {
				$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'issue', 'view', $issue->getId())));
				// Articles for issue
				$submissionsIterator = Services::get('submission')->getMany([
					'issueIds' => [$issue->getId()],
					'contextId' => $journal->getId(),
					'status' => STATUS_PUBLISHED,
				]);
				foreach($submissionsIterator as $submission) {
					// Abstract
					$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'article', 'view', array($submission->getBestId()))));
					// Galley files
					$galleys = $galleyDao->getByPublicationId($submission->getCurrentPublication()->getId());
					while ($galley = $galleys->next()) {
						$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'article', 'view', array($submission->getBestId(), $galley->getBestGalleyId()))));
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


