<?php

/**
 * @file plugins/importexport/doaj/DOAJExportDom.inc.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOAJExportDom
 * @ingroup plugins_importexport_DOAJ
 *
 * @brief DOAJ import/export plugin DOM functions for export
 */

import('lib.pkp.classes.xml.XMLCustomWriter');

class DOAJExportDom {
	/**
	 * Generate the export DOM tree for a given journal.
	 * @param $doc object DOM object
	 * @param $journal object Journal to export
	 * @param $selectedObjects array
	 */
	function generateJournalDom($doc, $journal, $selectedObjects) {
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$pubArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$journalId = $journal->getId();

		// Records node contains all articles, each called a record
		$records = XMLCustomWriter::createElement($doc, 'records');

		// retrieve selected issues
		$selectedIssues = array();
		if (isset($selectedObjects[DOAJ_EXPORT_ISSUES])) {
			$selectedIssues = $selectedObjects[DOAJ_EXPORT_ISSUES];

			// make sure the selected issues belong to the current journal
			foreach($selectedIssues as $key => $selectedIssueId) {
				$selectedIssue = $issueDao->getIssueById($selectedIssueId, $journalId);
				if (!$selectedIssue) unset($selectedIssues[$key]);
			}
		}

		// retrieve selected articles
		$selectedArticles = array();
		if (isset($selectedObjects[DOAJ_EXPORT_ARTICLES])) {
			$selectedArticles = $selectedObjects[DOAJ_EXPORT_ARTICLES];

			// make sure the selected articles belong to the current journal
			foreach($selectedArticles as $key => $selectedArticleId) {
				$selectedArticle = $articleDao->getArticle($selectedArticleId, $journalId);
				if (!$selectedArticle) unset($selectedArticles[$key]);
			}
		}

		$pubArticles = $pubArticleDao->getPublishedArticlesByJournalId($journalId);
		while ($pubArticle = $pubArticles->next()) {

			// check for selected issues:
			$issueId = $pubArticle->getIssueId();
			if (!empty($selectedIssues) && !in_array($issueId, $selectedIssues)) continue;

			$issue = $issueDao->getIssueById($issueId);
			if(!$issue) continue;

			// check for selected articles:
			$articleId = $pubArticle->getArticleId();
			if (!empty($selectedArticles) && !in_array($articleId, $selectedArticles)) continue;


			$section = $sectionDao->getSection($pubArticle->getSectionId());
			$articleNode = DOAJExportDom::generateArticleDom($doc, $journal, $issue, $section, $pubArticle);

			XMLCustomWriter::appendChild($records, $articleNode);

			unset($issue, $section, $articleNode);
		}

		return $records;
	}

	/**
	 * Generate the DOM tree for a given article.
	 * @param $doc object DOM object
	 * @param $journal object Journal
	 * @param $issue object Issue
	 * @param $section object Section
	 * @param $article object Article
	 */
	function generateArticleDom($doc, $journal, $issue, $section, $article) {
		$root = XMLCustomWriter::createElement($doc, 'record');

		/* --- Article Language --- */
		XMLCustomWriter::createChildWithText($doc, $root, 'language', AppLocale::get3LetterIsoFromLocale($article->getLocale()), false);

		/* --- Publisher name (i.e. institution name) --- */
		XMLCustomWriter::createChildWithText($doc, $root, 'publisher', $journal->getSetting('publisherInstitution'), false);

		/* --- Journal's title --- */
		XMLCustomWriter::createChildWithText($doc, $root, 'journalTitle', $journal->getTitle($journal->getPrimaryLocale()), false);

		/* --- Identification Numbers --- */
		XMLCustomWriter::createChildWithText($doc, $root, 'issn', $journal->getSetting('printIssn'), false);
		XMLCustomWriter::createChildWithText($doc, $root, 'eissn', $journal->getSetting('onlineIssn'), false);

		/* --- Article's publication date, volume, issue, DOI --- */
		if ($article->getDatePublished()) {
			XMLCustomWriter::createChildWithText($doc, $root, 'publicationDate', DOAJExportDom::formatDate($article->getDatePublished()), false);
		}
		else {
			XMLCustomWriter::createChildWithText($doc, $root, 'publicationDate', DOAJExportDom::formatDate($issue->getDatePublished()), false);
		}

		XMLCustomWriter::createChildWithText($doc, $root, 'volume',  $issue->getVolume(), false);

		XMLCustomWriter::createChildWithText($doc, $root, 'issue',  $issue->getNumber(), false);

		/** --- FirstPage / LastPage (from PubMed plugin)---
		 * there is some ambiguity for online journals as to what
		 * "page numbers" are; for example, some journals (eg. JMIR)
		 * use the "e-location ID" as the "page numbers" in PubMed
		 */
		$pages = $article->getPages();
		if (preg_match("/([0-9]+)\s*-\s*([0-9]+)/i", $pages, $matches)) {
			// simple pagination (eg. "pp. 3-8")
			XMLCustomWriter::createChildWithText($doc, $root, 'startPage', $matches[1]);
			XMLCustomWriter::createChildWithText($doc, $root, 'endPage', $matches[2]);
		} elseif (preg_match("/(e[0-9]+)/i", $pages, $matches)) {
			// elocation-id (eg. "e12")
			XMLCustomWriter::createChildWithText($doc, $root, 'startPage', $matches[1]);
			XMLCustomWriter::createChildWithText($doc, $root, 'endPage', $matches[1]);
		}

		XMLCustomWriter::createChildWithText($doc, $root, 'doi',  $article->getPubId('doi'), false);

		/* --- Article's publication date, volume, issue, DOI --- */
		XMLCustomWriter::createChildWithText($doc, $root, 'publisherRecordId',  $article->getPublishedArticleId(), false);

		XMLCustomWriter::createChildWithText($doc, $root, 'documentType',  $article->getType($article->getLocale()), false);

		/* --- Article title --- */
		$articleTitles = (array) $article->getTitle(null);
		if (array_key_exists($article->getLocale(), $articleTitles)) {
			$titleInArticleLocale = $articleTitles[$article->getLocale()];
			unset($articleTitles[$article->getLocale()]);
			$articleTitles = array_merge(array($article->getLocale() => $titleInArticleLocale), $articleTitles);
		}
		foreach ($articleTitles as $locale => $title) {
			if (empty($title)) continue;

			$titleNode = XMLCustomWriter::createChildWithText($doc, $root, 'title', $title);
			XMLCustomWriter::setAttribute($titleNode, 'language', AppLocale::get3LetterIsoFromLocale($locale));
		}

		/* --- Authors and affiliations --- */
		$authors = XMLCustomWriter::createElement($doc, 'authors');
		XMLCustomWriter::appendChild($root, $authors);

		$affilList = DOAJExportDom::generateAffiliationsList($article->getAuthors(), $article);

		foreach ($article->getAuthors() as $author) {
			$authorNode = DOAJExportDom::generateAuthorDom($doc, $root, $issue, $article, $author, $affilList);
			XMLCustomWriter::appendChild($authors, $authorNode);
			unset($authorNode);
		}

		if (!empty($affilList[0])) {
			$affils = XMLCustomWriter::createElement($doc, 'affiliationsList');
			XMLCustomWriter::appendChild($root, $affils);

			for ($i = 0; $i < count($affilList); $i++) {
				$affilNode = XMLCustomWriter::createChildWithText($doc, $affils, 'affiliationName', $affilList[$i]);
				XMLCustomWriter::setAttribute($affilNode, 'affiliationId', $i);
				unset($affilNode);
			}
		}

		/* --- Abstract --- */
		$articleAbstracts = (array) $article->getAbstract(null);
		if (array_key_exists($article->getLocale(), $articleAbstracts)) {
			$abstractInArticleLocale = $articleAbstracts[$article->getLocale()];
			unset($articleAbstracts[$article->getLocale()]);
			$articleAbstracts = array_merge(array($article->getLocale() => $abstractInArticleLocale), $articleAbstracts);
		}
		foreach ($articleAbstracts as $locale => $abstract) {
			if (empty($abstract)) continue;

			$abstractNode = XMLCustomWriter::createChildWithText($doc, $root, 'abstract', PKPString::html2text($abstract));
			XMLCustomWriter::setAttribute($abstractNode, 'language', AppLocale::get3LetterIsoFromLocale($locale));
		}

		/* --- FullText URL --- */
		$fullTextUrl = XMLCustomWriter::createChildWithText($doc, $root, 'fullTextUrl', Request::url(null, 'article', 'view', $article->getId()));
		XMLCustomWriter::setAttribute($fullTextUrl, 'format', 'html');

		/* --- Keywords --- */
		$articleKeywords = (array) $article->getSubject(null);
		if (array_key_exists($article->getLocale(), $articleKeywords)) {
			$keywordsInArticleLocale = $articleKeywords[$article->getLocale()];
			unset($articleKeywords[$article->getLocale()]);
			$articleKeywords = array_merge(array($article->getLocale() => $keywordsInArticleLocale), $articleKeywords);
		}
		foreach ($articleKeywords as $locale => $keywords) {
			$keywordsElement = XMLCustomWriter::createElement($doc, 'keywords');
			XMLCustomWriter::setAttribute($keywordsElement, 'language', AppLocale::get3LetterIsoFromLocale($locale));
			XMLCustomWriter::appendChild($root, $keywordsElement);

			$subjects = array_map('trim', explode(';', $keywords));

			foreach ($subjects as $keyword) {
				XMLCustomWriter::createChildWithText($doc, $keywordsElement, 'keyword', $keyword, false);
			}
		}

		return $root;
	}

	/**
	 * Generate the author export DOM tree.
	 * @param $doc object DOM object
	 * @param $journal object Journal
	 * @param $issue object Issue
	 * @param $article object Article
	 * @param $author object Author
	 * @param $affilList array List of author affiliations
	 */
	function generateAuthorDom($doc, $journal, $issue, $article, $author, $affilList) {
		$root = XMLCustomWriter::createElement($doc, 'author');

		XMLCustomWriter::createChildWithText($doc, $root, 'name', $author->getFullName());

		if(in_array($author->getAffiliation($article->getLocale()), $affilList)  && !empty($affilList[0])) {
			XMLCustomWriter::createChildWithText($doc, $root, 'affiliationId', current(array_keys($affilList, $author->getAffiliation($article->getLocale()))));
		}

		return $root;
	}

	/**
	 * Generate a list of affiliations among all authors of an article.
	 * @param $authors object Array of article authors
	 * @param $article Article
	 * @return array
	 */
	function generateAffiliationsList($authors, $article) {
		$affilList = array();

		foreach ($authors as $author) {
			if(!in_array($author->getAffiliation($article->getLocale()), $affilList)) {
				$affilList[] = $author->getAffiliation($article->getLocale()) ;
			}
		}

		return $affilList;
	}

	/* --- Utility functions: --- */

	/**
	 * Get the file extension of a filename.
	 * @param $filename
	 * @return string
	 */
	function file_ext($filename) {
		return strtolower_codesafe(str_replace('.', '', strrchr($filename, '.')));
	}

	/**
	 * Format a date by Y-m-d format.
	 * @param $date string
	 * @return string
	 */
	function formatDate($date) {
		if ($date == '') return null;
		return date('Y-m-d', strtotime($date));
	}


}

?>
