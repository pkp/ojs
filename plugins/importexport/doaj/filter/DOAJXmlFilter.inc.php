<?php

/**
 * @file plugins/importexport/doaj/filter/DOAJXmlFilter.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOAJXmlFilter
 * @ingroup plugins_importexport_doaj
 *
 * @brief Class that converts an Article to a DOAJ XML document.
 */

import('lib.pkp.plugins.importexport.native.filter.NativeExportFilter');


class DOAJXmlFilter extends NativeExportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('DOAJ XML export');
		parent::__construct($filterGroup);
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.doaj.filter.DOAJXmlFilter';
	}

	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $pubObjects array Array of PublishedArticles
	 * @return DOMDocument
	 */
	function &process(&$pubObjects) {
		// Create the XML document
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$plugin = $deployment->getPlugin();
		$cache = $plugin->getCache();

		// Create the root node
		$rootNode = $this->createRootNode($doc);
		$doc->appendChild($rootNode);

		foreach($pubObjects as $pubObject) {
			$issueId = $pubObject->getIssueId();
			if ($cache->isCached('issues', $issueId)) {
				$issue = $cache->get('issues', $issueId);
			} else {
				$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
				$issue = $issueDao->getById($issueId, $context->getId());
				if ($issue) $cache->add($issue, null);
			}

			// Record
			$recordNode = $doc->createElement('record');
			$rootNode->appendChild($recordNode);
			// Language
			$language = AppLocale::get3LetterIsoFromLocale($pubObject->getLocale());
			if (!empty($language)) $recordNode->appendChild($node = $doc->createElement('language', $language));
			// Publisher name (i.e. institution name)
			$publisher = $context->getSetting('publisherInstitution');
			if (!empty($publisher)) $recordNode->appendChild($node = $doc->createElement('publisher', htmlspecialchars($publisher, ENT_COMPAT, 'UTF-8')));
			// Journal's title (M)
			$journalTitle =  $context->getName($context->getPrimaryLocale());
			$recordNode->appendChild($node = $doc->createElement('journalTitle', htmlspecialchars($journalTitle, ENT_COMPAT, 'UTF-8')));
			// Identification Numbers
			$issn = $context->getSetting('printIssn');
			if (!empty($issn)) $recordNode->appendChild($node = $doc->createElement('issn', $issn));
			$eissn = $context->getSetting('onlineIssn');
			if (!empty($eissn)) $recordNode->appendChild($node = $doc->createElement('eissn', $eissn));
			// Article's publication date, volume, issue
			if ($pubObject->getDatePublished()) {
				$recordNode->appendChild($node = $doc->createElement('publicationDate', $this->formatDate($pubObject->getDatePublished())));
			}
			else {
				$recordNode->appendChild($node = $doc->createElement('publicationDate', $this->formatDate($issue->getDatePublished())));
			}
			$volume = $issue->getVolume();
			if (!empty($volume) && $issue->getShowVolume()) $recordNode->appendChild($node = $doc->createElement('volume', htmlspecialchars($volume, ENT_COMPAT, 'UTF-8')));
			$issueNumber = $issue->getNumber();
			if (!empty($issueNumber) && $issue->getShowNumber()) $recordNode->appendChild($node = $doc->createElement('issue', htmlspecialchars($issueNumber, ENT_COMPAT, 'UTF-8')));
			/** --- FirstPage / LastPage (from PubMed plugin)---
			 * there is some ambiguity for online journals as to what
			 * "page numbers" are; for example, some journals (eg. JMIR)
			 * use the "e-location ID" as the "page numbers" in PubMed
			 */
			$startPage = $pubObject->getStartingPage();
			$endPage = $pubObject->getEndingPage();
			if (isset($startPage) && $startPage !== "") {
				$recordNode->appendChild($node = $doc->createElement('startPage', htmlspecialchars($startPage, ENT_COMPAT, 'UTF-8')));
				$recordNode->appendChild($node = $doc->createElement('endPage', htmlspecialchars($endPage, ENT_COMPAT, 'UTF-8')));
			}
			// DOI
			$doi = $pubObject->getStoredPubId('doi');
			if (!empty($doi)) $recordNode->appendChild($node = $doc->createElement('doi', htmlspecialchars($doi, ENT_COMPAT, 'UTF-8')));
			// publisherRecordId
			$recordNode->appendChild($node = $doc->createElement('publisherRecordId', htmlspecialchars($pubObject->getId(), ENT_COMPAT, 'UTF-8')));
			// documentType
			$type = $pubObject->getType($pubObject->getLocale());
			if (!empty($type)) $recordNode->appendChild($node = $doc->createElement('documentType', htmlspecialchars($type, ENT_COMPAT, 'UTF-8')));
			// Article title
			$articleTitles = (array) $pubObject->getTitle(null);
			if (array_key_exists($pubObject->getLocale(), $articleTitles)) {
				$titleInArticleLocale = $articleTitles[$pubObject->getLocale()];
				unset($articleTitles[$pubObject->getLocale()]);
				$articleTitles = array_merge(array($pubObject->getLocale() => $titleInArticleLocale), $articleTitles);
			}
			foreach ($articleTitles as $locale => $title) {
				if (!empty($title)) {
					$recordNode->appendChild($node = $doc->createElement('title', htmlspecialchars($title, ENT_COMPAT, 'UTF-8')));
					$node->setAttribute('language', AppLocale::get3LetterIsoFromLocale($locale));
				}
			}
			// Authors and affiliations
			$authorsNode = $doc->createElement('authors');
			$recordNode->appendChild($authorsNode);
			$affilList = $this->createAffiliationsList($pubObject->getAuthors(), $pubObject);
			foreach ($pubObject->getAuthors() as $author) {
				$authorsNode->appendChild($this->createAuthorNode($doc, $pubObject, $author, $affilList));
			}
			if (!empty($affilList[0])) {
				$affilsNode = $doc->createElement('affiliationsList');
				$recordNode->appendChild($affilsNode);
				for ($i = 0; $i < count($affilList); $i++) {
					$affilsNode->appendChild($node = $doc->createElement('affiliationName', htmlspecialchars($affilList[$i], ENT_COMPAT, 'UTF-8')));
					$node->setAttribute('affiliationId', $i);
				}
			}
			// Abstract
			$articleAbstracts = (array) $pubObject->getAbstract(null);
			if (array_key_exists($pubObject->getLocale(), $articleAbstracts)) {
				$abstractInArticleLocale = $articleAbstracts[$pubObject->getLocale()];
				unset($articleAbstracts[$pubObject->getLocale()]);
				$articleAbstracts = array_merge(array($pubObject->getLocale() => $abstractInArticleLocale), $articleAbstracts);
			}
			foreach ($articleAbstracts as $locale => $abstract) {
				if (!empty($abstract)) {
					$recordNode->appendChild($node = $doc->createElement('abstract', htmlspecialchars(PKPString::html2text($abstract), ENT_COMPAT, 'UTF-8')));
					$node->setAttribute('language', AppLocale::get3LetterIsoFromLocale($locale));
				}
			}
			// FullText URL
			$recordNode->appendChild($node = $doc->createElement('fullTextUrl', htmlspecialchars(Request::url(null, 'article', 'view', $pubObject->getId()), ENT_COMPAT, 'UTF-8')));
			$node->setAttribute('format', 'html');
			// Keywords
			$supportedLocales = array_keys(AppLocale::getSupportedFormLocales());
			$dao = DAORegistry::getDAO('SubmissionKeywordDAO');
			$articleKeywords = $dao->getKeywords($pubObject->getId(), $supportedLocales);
			if (array_key_exists($pubObject->getLocale(), $articleKeywords)) {
				$keywordsInArticleLocale = $articleKeywords[$pubObject->getLocale()];
				unset($articleKeywords[$pubObject->getLocale()]);
				$articleKeywords = array_merge(array($pubObject->getLocale() => $keywordsInArticleLocale), $articleKeywords);
			}
			foreach ($articleKeywords as $locale => $keywords) {
				$keywordsNode = $doc->createElement('keywords');
				$keywordsNode->setAttribute('language', AppLocale::get3LetterIsoFromLocale($locale));
				$recordNode->appendChild($keywordsNode);
				foreach ($keywords as $keyword) {
					if (!empty($keyword)) $keywordsNode->appendChild($node = $doc->createElement('keyword', htmlspecialchars($keyword, ENT_COMPAT, 'UTF-8')));
				}
			}
		}
		return $doc;
	}

	/**
	 * Create and return the root node.
	 * @param $doc DOMDocument
	 * @return DOMElement
	 */
	function createRootNode($doc) {
		$deployment = $this->getDeployment();
		$rootNode = $doc->createElement($deployment->getRootElementName());
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$rootNode->setAttribute('xsi:noNamespaceSchemaLocation', $deployment->getXmlSchemaLocation());
		return $rootNode;
	}

	/**
	 * Generate the author node.
	 * @param $doc DOMDocument
	 * @param $article object Article
	 * @param $author object Author
	 * @param $affilList array List of author affiliations
	 * @return DOMElement
	 */
	function createAuthorNode($doc, $article, $author, $affilList) {
		$deployment = $this->getDeployment();
		$authorNode = $doc->createElement('author');
		$authorNode->appendChild($node = $doc->createElement('name', htmlspecialchars($author->getFullName(false), ENT_COMPAT, 'UTF-8')));
		if(in_array($author->getAffiliation($article->getLocale()), $affilList)  && !empty($affilList[0])) {
			$authorNode->appendChild($node = $doc->createElement('affiliationId', htmlspecialchars(current(array_keys($affilList, $author->getAffiliation($article->getLocale()))), ENT_COMPAT, 'UTF-8')));
		}
		return $authorNode;
	}

	/**
	 * Generate a list of affiliations among all authors of an article.
	 * @param $authors object Array of article authors
	 * @param $article Article
	 * @return array
	 */
	function createAffiliationsList($authors, $article) {
		$affilList = array();
		foreach ($authors as $author) {
			if(!in_array($author->getAffiliation($article->getLocale()), $affilList)) {
				$affilList[] = $author->getAffiliation($article->getLocale()) ;
			}
		}
		return $affilList;
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


