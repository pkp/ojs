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

import('lib.pkp.classes.xml.XMLCustomWriter');
import('classes.handler.Handler');

define('SITEMAP_XSD_URL', 'http://www.sitemaps.org/schemas/sitemap/0.9');

class SitemapHandler extends Handler {
	/**
	 * Generate an XML sitemap for webcrawlers
	 * Creates a sitemap index if in site context, else creates a sitemap
	 */
	function index($args, $request) {
		if ($request->getRequestedJournalPath() == 'index') {
			$doc = $this->_createSitemapIndex();
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: inline; filename=\"sitemap_index.xml\"");
			echo $doc->saveXml();
		} else {
			$doc = $this->_createJournalSitemap($request);
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: inline; filename=\"sitemap.xml\"");
			echo $doc->saveXml();
		}
	}

	/**
	 * Construct a sitemap index listing each journal's individual sitemap
	 * @return XMLNode
	 */
	function _createSitemapIndex() {
		$journalDao = DAORegistry::getDAO('JournalDAO');

		$doc = new DOMDocument('1.0', 'utf-8');
		$root = $doc->createElement('sitemapindex');
		$root->setAttribute('xmlns', SITEMAP_XSD_URL);

		$journals = $journalDao->getAll(true);
		while ($journal = $journals->next()) {
			$sitemapUrl = $request->url($journal->getPath(), 'sitemap');
			$sitemap = $doc->createElement('sitemap');
			$sitemap->appendChild($doc->createElement('loc', $sitemapUrl));
			$root->appendChild($sitemap);
		}

		$doc->appendChild($root);
		return $doc;
	}

	 /**
	 * Construct the sitemap
	 * @return XMLNode
	 */
	function _createJournalSitemap($request) {
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');

		$journal = $request->getJournal();
		$journalId = $journal->getId();

		$doc = new DOMDocument('1.0', 'utf-8');

		$root = $doc->createElement('urlset');
		$root->setAttribute('xmlns', SITEMAP_XSD_URL);

		// Journal home
		$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(),'index','index')));
		// User register
		if ($journal->getSetting('disableUserReg') != 1) {
			$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'user', 'register')));
		}
		// User login
		$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'login')));
		// Announcements
		if ($journal->getSetting('enableAnnouncements') == 1) {
			$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'announcement')));
			$announcementDao = DAORegistry::getDAO('AnnouncementDAO');
			$announcementsResult = $announcementDao->getByAssocId(ASSOC_TYPE_JOURNAL, $journalId);
			while ($announcement = $announcementsResult->next()) {
				$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'announcement', 'view', $announcement->getId())));
			}
		}
		// About: journal
		if (!empty($journal->getSetting('about'))) {
			$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'about')));
		}
		// About: submissions
		$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'about', 'submissions')));
		// About: editorial team
		if (!empty($journal->getSetting('editorialTeam'))) {
			$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'about', 'editorialTeam')));
		}
		// About: contact
		if (!empty($journal->getSetting('mailingAddress')) || !empty($journal->getSetting('contactName'))) {
			$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'about', 'contact')));
		}
		// Search
		$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'search')));
		$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), 'search', 'authors')));
		// Issues
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
		// Custom pages (navigation menu items)
		$navigationMenuItemDao = DAORegistry::getDAO('NavigationMenuItemDAO');
		$menuItemsResult = $navigationMenuItemDao->getByType(NMI_TYPE_CUSTOM, $journalId);
		while ($menuItem = $menuItemsResult->next()) {
			$root->appendChild($this->_createUrlTree($doc, $request->url($journal->getPath(), $menuItem->getPath())));
		}

		$doc->appendChild($root);

		// Enable plugins to change the sitemap
		HookRegistry::call('SitemapHandler::createJournalSitemap', array(&$doc));

		return $doc;
	}

	/**
	 * Create a url entry with children
	 * @param $doc DOMDocument Reference to the XML document object
	 * @param $loc string URL of page (required)
	 * @param $lastmod string Last modification date of page (optional)
	 * @param $changefreq Frequency of page modifications (optional)
	 * @param $priority string Subjective priority assessment of page (optional)
	 * @return DOMNode
	 */
	function _createUrlTree($doc, $loc, $lastmod = null, $changefreq = null, $priority = null) {
		$url = $doc->createElement('url');
		$url->appendChild($doc->createElement('loc', $loc));
		if ($lastmod) {
			$url->appendChild($doc->createElement('lastmod', $lastmod));
		}
		if ($changefreq) {
			$url->appendChild($doc->createElement('changefreq', $changefreq));
		}
		if ($priority) {
			$url->appendChild($doc->createElement('priority', $priority));
		}
		return $url;
	}

}

?>
