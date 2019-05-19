<?php

/**
 * @file plugins/importexport/doaj/filter/DOAJJsonFilter.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOAJJsonFilter
 * @ingroup plugins_importexport_doaj
 *
 * @brief Class that converts an Article to a DOAJ JSON string.
 */

import('lib.pkp.plugins.importexport.native.filter.NativeImportExportFilter');


class DOAJJsonFilter extends NativeImportExportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('DOAJ JSON export');
		parent::__construct($filterGroup);
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.doaj.filter.DOAJJsonFilter';
	}

	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $pubObject PublishedArticle
	 * @return JSON string
	 */
	function &process(&$pubObject) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$plugin = $deployment->getPlugin();
		$cache = $plugin->getCache();

		// Create the JSON string Article JSON example bibJson https://github.com/DOAJ/harvester/blob/9b59fddf2d01f7c918429d33b63ca0f1a6d3d0d0/service/tests/fixtures/article.py

		$issueId = $pubObject->getIssueId();
		if ($cache->isCached('issues', $issueId)) {
			$issue = $cache->get('issues', $issueId);
		} else {
			$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issue = $issueDao->getById($issueId, $context->getId());
			if ($issue) $cache->add($issue, null);
		}

		$article = array();
		$article['bibjson']['journal'] = array();
		// Publisher name (i.e. institution name)
		$publisher = $context->getData('publisherInstitution');
		if (!empty($publisher)) $article['bibjson']['journal']['publisher'] = $publisher;
		// To-Do: license ???
		// Journal's title (M)
		$journalTitle =  $context->getName($context->getPrimaryLocale());
		$article['bibjson']['journal']['title'] = $journalTitle;
		// Identification Numbers
		$issns = array();
		$pissn = $context->getData('printIssn');
		if (!empty($pissn)) $issns[] = $pissn;
		$eissn = $context->getData('onlineIssn');
		if (!empty($eissn)) $issns[] = $eissn;
		if (!empty($issns)) $article['bibjson']['journal']['issns'] = $issns;
		// Volume, Number
		$volume = $issue->getVolume();
		if (!empty($volume)) $article['bibjson']['journal']['volume'] = $volume;
		$issueNumber = $issue->getNumber();
		if (!empty($issueNumber)) $article['bibjson']['journal']['number'] = $issueNumber;

		// Article title
		$article['bibjson']['title'] = $pubObject->getTitle($pubObject->getLocale());
		// Identifiers
		$article['bibjson']['identifier'] = array();
		// DOI
		$doi = $pubObject->getStoredPubId('doi');
		if (!empty($doi)) $article['bibjson']['identifier'][] = array('type' => 'doi', 'id' => $doi);
		// Print and online ISSN
		if (!empty($pissn)) $article['bibjson']['identifier'][] = array('type' => 'pissn', 'id' => $pissn);
		if (!empty($eissn)) $article['bibjson']['identifier'][] = array('type' => 'eissn', 'id' => $eissn);
		// Year and month from article's publication date
		$publicationDate = $this->formatDate($issue->getDatePublished());
		if ($pubObject->getDatePublished()) {
			$publicationDate = $this->formatDate($pubObject->getDatePublished());
		}
		$yearMonth = explode('-', $publicationDate);
		$article['bibjson']['year'] = $yearMonth[0];
		$article['bibjson']['month'] = $yearMonth[1];
		/** --- FirstPage / LastPage (from PubMed plugin)---
		 * there is some ambiguity for online journals as to what
		 * "page numbers" are; for example, some journals (eg. JMIR)
		 * use the "e-location ID" as the "page numbers" in PubMed
		 */
		$startPage = $pubObject->getStartingPage();
		$endPage = $pubObject->getEndingPage();
		if (isset($startPage) && $startPage !== "") {
			$article['bibjson']['start_page'] = $startPage;
			$article['bibjson']['end_page'] = $endPage;
		}
		// FullText URL
		$request = Application::get()->getRequest();
		$article['bibjson']['link'] = array();
		$article['bibjson']['link'][] = array(
			'url' => $request->url($context->getPath(), 'article', 'view', $pubObject->getId()),
			'type' => 'fulltext',
			'content_type' => 'html'
		);
		// Authors: name and affiliation
		$article['bibjson']['author'] = array();
		$articleAuthors = $pubObject->getAuthors();
		foreach ($articleAuthors as $articleAuthor) {
			$author = array('name' => $articleAuthor->getFullName(false));
			$affiliation = $articleAuthor->getAffiliation($pubObject->getLocale());
			if (!empty($affiliation)) $author['affiliation'] = $affiliation;
			$article['bibjson']['author'][] = $author;
		}
		// Abstract
		$abstract = $pubObject->getAbstract($pubObject->getLocale());
		if (!empty($abstract)) $article['bibjson']['abstract'] = PKPString::html2text($abstract);
		// Keywords
		$dao = DAORegistry::getDAO('SubmissionKeywordDAO');
		$keywords = $dao->getKeywords($pubObject->getId(), array($pubObject->getLocale()));
		$allowedNoOfKeywords = array_slice($keywords[$pubObject->getLocale()], 0, 6);
		if (!empty($keywords[$pubObject->getLocale()])) $article['bibjson']['keywords'] = $allowedNoOfKeywords;

		/* not needed here:
		// Language
		$language = AppLocale::get3LetterIsoFromLocale($pubObject->getLocale());
		// publisherRecordId
		$publisherRecordId = $pubObject->getId();
		// documentType
		$type = $pubObject->getType($pubObject->getLocale());
		*/

		$json = json_encode($article);
		return $json;
	}

	/**
	 * Format a date by Y-F format.
	 * @param $date string
	 * @return string
	 */
	function formatDate($date) {
		if ($date == '') return null;
		return date('Y-F', strtotime($date));
	}

}


