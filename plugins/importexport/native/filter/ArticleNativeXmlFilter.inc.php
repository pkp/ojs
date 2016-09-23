<?php

/**
 * @file plugins/importexport/native/filter/ArticleNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Article to a Native XML document.
 */

import('lib.pkp.plugins.importexport.native.filter.SubmissionNativeXmlFilter');

class ArticleNativeXmlFilter extends SubmissionNativeXmlFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function ArticleNativeXmlFilter($filterGroup) {
		parent::SubmissionNativeXmlFilter($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.ArticleNativeXmlFilter';
	}


	//
	// Implement abstract methods from SubmissionNativeXmlFilter
	//
	/**
	 * Get the representation export filter group name
	 * @return string
	 */
	function getRepresentationExportFilterGroupName() {
		return 'article-galley=>native-xml';
	}

	//
	// Submission conversion functions
	//
	/**
	 * Create and return a submission node.
	 * @param $doc DOMDocument
	 * @param $submission Submission
	 * @return DOMElement
	 */
	function createSubmissionNode($doc, $submission) {
		$deployment = $this->getDeployment();
		$submissionNode = parent::createSubmissionNode($doc, $submission);

		// Add the series, if one is designated.
		if ($sectionId = $submission->getSectionId()) {
			$sectionDao = DAORegistry::getDAO('SectionDAO');
			$section = $sectionDao->getById($sectionId, $submission->getContextId());
			assert($section);
			$submissionNode->setAttribute('section_ref', $section->getLocalizedAbbrev());
		}

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = $publishedArticleDao->getPublishedArticleByArticleId($submission->getId());
		$publishedArticle ? $submissionNode->setAttribute('seq', $publishedArticle->getSequence()) : $submissionNode->setAttribute('seq', '0');
		$publishedArticle ? $submissionNode->setAttribute('access_status', $publishedArticle->getAccessStatus()) : $submissionNode->setAttribute('access_status', '0');
		// if this is a published article and not part/subelement of an issue element
		// add issue identification element
		if ($publishedArticle && !$deployment->getIssue()) {
			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getById($publishedArticle->getIssueId());
			$submissionNode->appendChild($this->createIssueIdentificationNode($doc, $issue));
		}
		return $submissionNode;
	}

	/**
	 * Create and return an issue identification node.
	 * @param $doc DOMDocument
	 * @param $issue Issue
	 * @return DOMElement
	 */
	function createIssueIdentificationNode($doc, $issue) {
		$deployment = $this->getDeployment();
		$vol = $issue->getVolume();
		$num = $issue->getNumber();
		$year = $issue->getYear();
		$title = $issue->getTitle(null);
		assert($issue->getShowVolume() || $issue->getShowNumber() || $issue->getShowYear() || $issue->getShowTitle());
		$issueIdentificationNode = $doc->createElementNS($deployment->getNamespace(), 'issue_identification');
		if ($issue->getShowVolume()) {
			assert(!empty($vol));
			$issueIdentificationNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'volume', $vol));
		}
		if ($issue->getShowNumber()) {
			assert(!empty($num));
			$issueIdentificationNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'number', $num));
		}
		if ($issue->getShowYear()) {
			assert(!empty($year));
			$issueIdentificationNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'year', $year));
		}
		if ($issue->getShowTitle()) {
			assert(!empty(array_values($title)));
			$this->createLocalizedNodes($doc, $issueIdentificationNode, 'title', $title);
		}
		return $issueIdentificationNode;
	}
}

?>
