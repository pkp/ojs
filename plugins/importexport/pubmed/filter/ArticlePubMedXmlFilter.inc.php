<?php

/**
 * @file plugins/importexport/pubmed/filter/ArticlePubMedXmlFilter.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticlePubMedXmlFilter
 * @ingroup plugins_importexport_pubmed
 *
 * @brief Class that converts a Article to a PubMed XML document.
 */

import('lib.pkp.classes.filter.PersistableFilter');

class ArticlePubMedXmlFilter extends PersistableFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.pubmed.filter.ArticlePubMedXmlFilter';
	}


	//
	// Implement abstract methods from SubmissionPubMedXmlFilter
	//
	/**
	 * Get the representation export filter group name
	 * @return string
	 */
	function getRepresentationExportFilterGroupName() {
		return 'article-galley=>pubmed-xml';
	}

	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $submissions array Array of submissions
	 * @return DOMDocument
	 */
	function &process(&$submissions) {
		// Create the XML document
		$implementation = new DOMImplementation();
		$dtd = $implementation->createDocumentType('ArticleSet', '-//NLM//DTD PubMed 2.0//EN', 'http://www.ncbi.nlm.nih.gov/entrez/query/static/PubMed.dtd');
		$doc = $implementation->createDocument('', '', $dtd);
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journal = null;

		$rootNode = $doc->createElement('ArticleSet');
		foreach ($submissions as $submission) {
			// Fetch associated objects
			$publishedArticle = $publishedArticleDao->getBySubmissionId($submission->getId());
			if (!$journal || $journal->getId() != $submission->getContextId()) {
				$journal = $journalDao->getById($submission->getContextId());
			}
			$issue = $issueDao->getByArticleId($submission->getId(), $journal->getId());

			$articleNode = $doc->createElement('Article');
			$articleNode->appendChild($this->createJournalNode($doc, $journal, $issue, $submission, $publishedArticle));


			$submissionLocale = $submission->getLocale();
			if ($submissionLocale == 'en_US') {
				$articleNode->appendChild($doc->createElement('ArticleTitle', $submission->getTitle($submissionLocale)));
			} else {
				$articleNode->appendChild($doc->createElement('VernacularTitle', $submission->getTitle($submissionLocale)));
			}

			$startPage = $submission->getStartingPage();
			$endPage = $submission->getEndingPage();
			if (isset($startPage) && $startPage !== '') {
				// We have a page range or e-location id
				$articleNode->appendChild($doc->createElement('FirstPage', $startPage));
				$articleNode->appendChild($doc->createElement('LastPage', $endPage));
			}

			if ($doi = $submission->getStoredPubId('doi')) {
				$doiNode = $doc->createElement('ELocationID', $doi);
				$doiNode->setAttribute('EIdType', 'doi');
				$articleNode->appendChild($doiNode);
			}

			$articleNode->appendChild($doc->createElement('Language', AppLocale::get3LetterFrom2LetterIsoLanguage(substr($submission->getLocale(), 0, 2))));

			$authorListNode = $doc->createElement('AuthorList');
			foreach ($submission->getAuthors() as $authorIndex => $author) {
				$authorListNode->appendChild($this->generateAuthorNode($doc, $journal, $issue, $submission, $author, $authorIndex));
			}
			$articleNode->appendChild($authorListNode);

			if ($submission->getStoredPubId('publisher-id')) {
				$articleIdListNode = $doc->createElement('ArticleIdList');
				$articleIdNode = $doc->createElement('ArticleId', $publisherId);
				$articleIdNode->setAttribute('IdType', 'pii');
				$articleIdListNode->appendChild($articleIdNode);
				$articleNode->appendChild($articleIdListNode);
			}

			// History
			$historyNode = $doc->createElement('History');
			$historyNode->appendChild($this->generatePubDateDom($doc, $submission->getDateSubmitted(), 'received'));

			$editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
			$editDecisions = (array) $editDecisionDao->getEditorDecisions($submission->getId());
			do {
				$editorDecision = array_pop($editDecisions);
			} while ($editorDecision && $editorDecision['decision'] != SUBMISSION_EDITOR_DECISION_ACCEPT);

			if ($editorDecision) {
				$historyNode->appendChild($this->generatePubDateDom($doc, $editorDecision['dateDecided'], 'accepted'));
			}
			$articleNode->appendChild($historyNode);

			// FIXME: Revision dates

			if ($abstract = PKPString::html2text($submission->getAbstract($submissionLocale))) {
				$articleNode->appendChild($doc->createElement('Abstract', $abstract));
			}

			$rootNode->appendChild($articleNode);
		}
		$doc->appendChild($rootNode);
		return $doc;
	}

	/**
	 * Construct and return a Journal element.
	 * @param $doc DOMDocument
	 * @param $journal Journal
	 * @param $issue Issue
	 * @param $submission Submission
	 * @param $publishedArticle PublishedArticle
	 */
	function createJournalNode($doc, $journal, $issue, $submission, $publishedArticle) {
		$journalNode = $doc->createElement('Journal');

		$publisherNameNode = $doc->createElement('PublisherName', $journal->getData('publisherInstitution'));
		$journalNode->appendChild($publisherNameNode);

		$journalTitleNode = $doc->createElement('JournalTitle', $journal->getName($journal->getPrimaryLocale()));
		$journalNode->appendChild($journalTitleNode);

		// check various ISSN fields to create the ISSN tag
		if ($journal->getData('printIssn') != '') $issn = $journal->getData('printIssn');
		elseif ($journal->getData('issn') != '') $issn = $journal->getData('issn');
		elseif ($journal->getData('onlineIssn') != '') $issn = $journal->getData('onlineIssn');
		else $issn = '';
		if ($issn != '') $journalNode->appendChild($doc->createElement('Issn', $issn));

		if ($issue && $issue->getShowVolume()) $journalNode->appendChild($doc->createElement('Volume', $issue->getVolume()));
		if ($issue && $issue->getShowNumber()) $journalNode->appendChild($doc->createElement('Issue', $issue->getNumber()));

		$datePublished = null;
		if ($publishedArticle) $datePublished = $publishedArticle->getDatePublished();
		if (!$datePublished && $issue) $datePublished = $issue->getDatePublished();
		if ($datePublished) {
			$journalNode->appendChild($this->generatePubDateDom($doc, $datePublished, 'epublish'));
		}

		return $journalNode;
	}

	/**
	 * Generate and return an author node representing the supplied author.
	 * @param $doc DOMDocument
	 * @param $journal Journal
	 * @param $issue Issue
	 * @param $submission Submission
	 * @param $author Author
	 * @return DOMElement
	 */
	function generateAuthorNode($doc, $journal, $issue, $submission, $author, $authorIndex) {
		$authorElement = $doc->createElement('Author');

		if (empty($author->getLocalizedFamilyName())) {
			$authorElement->appendChild($node = $doc->createElement('FirstName'));
			$node->setAttribute('EmptyYN', 'Y');
			$authorElement->appendChild($doc->createElement('LastName', ucfirst($author->getLocalizedGivenName())));
		} else {
			$authorElement->appendChild($doc->createElement('FirstName', ucfirst($author->getLocalizedGivenName())));
			$authorElement->appendChild($doc->createElement('LastName', ucfirst($author->getLocalizedFamilyName())));
		}
		if ($authorIndex == 0) {
			// See http://pkp.sfu.ca/bugzilla/show_bug.cgi?id=7774
			$authorElement->appendChild($doc->createElement('Affiliation', $author->getLocalizedAffiliation() . '. ' . $author->getEmail()));
		}

		return $authorElement;
	}

	/**
	 * Generate and return a date element per the PubMed standard.
	 * @param $doc DOMDocument
	 * @param $pubDate string
	 * @param $pubStatus string
	 * @return DOMElement
	 */
	function generatePubDateDom($doc, $pubDate, $pubStatus) {
		$pubDateNode = $doc->createElement('PubDate');
		$pubDateNode->setAttribute('PubStatus', $pubStatus);

		$pubDateNode->appendChild($doc->createElement('Year', date('Y', strtotime($pubDate))));
		$pubDateNode->appendChild($doc->createElement('Month', date('m', strtotime($pubDate))));
		$pubDateNode->appendChild($doc->createElement('Day', date('d', strtotime($pubDate))));

		return $pubDateNode;
	}
}


