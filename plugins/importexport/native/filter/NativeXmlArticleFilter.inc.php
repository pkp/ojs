<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlArticleFilter.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlArticleFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Native XML document to a set of articles.
 */

import('lib.pkp.plugins.importexport.native.filter.NativeXmlSubmissionFilter');

class NativeXmlArticleFilter extends NativeXmlSubmissionFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function NativeXmlArticleFilter($filterGroup) {
		parent::NativeXmlSubmissionFilter($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.NativeXmlArticleFilter';
	}

	/**
	 * Get the published submission DAO for this application.
	 * @return DAO
	 */
	function getPublishedSubmissionDAO() {
		return DAORegistry::getDAO('PublishedArticleDAO');
	}

	/**
	 * Get the method name for inserting a published submission.
	 * @return string
	 */
	function getPublishedSubmissionInsertMethod() {
		return 'insertObject';
	}

	/**
	 * Populate the submission object from the node
	 * @param $submission Submission
	 * @param $node DOMElement
	 * @return Submission
	 */
	function populateObject($submission, $node) {
		$sectionAbbrev = $node->getAttribute('section_ref');
		if ($sectionAbbrev !== '') {
			$sectionDao = DAORegistry::getDAO('SectionDAO');
			$section = $sectionDao->getByAbbrev($sectionAbbrev, $submission->getContextId());
			if (!$section) {
				fatalError('Could not find a section with the path "' . $sectionAbbrev . '"!');
			}
			$submission->setSectionId($section->getId());
		}

		return parent::populateObject($submission, $node);
	}

	/**
	 * Handle an element whose parent is the submission element.
	 * @param $n DOMElement
	 * @param $submission Submission
	 */
	function handleChildElement($n, $submission) {
		switch ($n->tagName) {
			case 'artwork_file':
			case 'supplementary_file':
				$this->parseSubmissionFile($n, $submission);
				break;
			case 'article_galley':
				$this->parseArticleGalley($n, $submission);
				break;
			case 'issue_identification':
				// do nothing, because this is done in populatePublishedSubmission
				break;
			case 'pages':
				$submission->setPages($n->textContent);
				break;
			default:
				parent::handleChildElement($n, $submission);
		}
	}

	/**
	 * Get the import filter for a given element.
	 * @param $elementName string Name of XML element
	 * @return Filter
	 */
	function getImportFilter($elementName) {
		switch ($elementName) {
			case 'submission_file':
				$importClass='SubmissionFile';
				break;
			case 'artwork_file':
				$importClass='SubmissionArtworkFile';
				break;
			case 'supplementary_file':
				$importClass='SupplementaryFile';
				break;
			case 'article_galley':
				$importClass='ArticleGalley';
				break;
			default:
				$importClass=null; // Suppress scrutinizer warn
				fatalError('Unknown node ' . $elementName);
		}
		// Caps on class name for consistency with imports, whose filter
		// group names are generated implicitly.
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$importFilters = $filterDao->getObjectsByGroup('native-xml=>' . $importClass);
		$importFilter = array_shift($importFilters);
		return $importFilter;
	}

	/**
	 * Parse an article galley and add it to the submission.
	 * @param $n DOMElement
	 * @param $submission Submission
	 */
	function parseArticleGalley($n, $submission) {
		$importFilter = $this->getImportFilter($n->tagName);
		assert($importFilter); // There should be a filter

		$importFilter->setDeployment($this->getDeployment());
		$articleGalleyDoc = new DOMDocument();
		$articleGalleyDoc->appendChild($articleGalleyDoc->importNode($n, true));
		return $importFilter->execute($articleGalleyDoc);
	}

	/**
	 * Class-specific methods for published submissions.
	 * @param PublishedArticle $submission
	 * @param DOMElement $node
	 * @return PublishedArticle
	 */
	function populatePublishedSubmission($submission, $node) {
		$deployment = $this->getDeployment();
		$issue = $deployment->getIssue();
		if (empty($issue)) {
			$issueIdentificationNodes = $node->getElementsByTagName('issue_identification');

			if ($issueIdentificationNodes->length != 1) {
				$titleNodes = $node->getElementsByTagName('title');
				fatalError(__('plugins.importexport.native.import.error.issueIdentificationMissing', array('articleTitle' => $titleNodes->item(0)->textContent)));
			}
			$issueIdentificationNode = $issueIdentificationNodes->item(0);
			$issue = $this->parseIssueIdentification($issueIdentificationNode);
		}
		assert($issue);
		$submission->setSequence($node->getAttribute('seq'));
		$submission->setAccessStatus($node->getAttribute('access_status'));
		$submission->setIssueId($issue->getId());
		return $submission;
	}

	/**
	 * Get the issue from the given identification.
	 * @param $node DOMElement
	 * @return Issue
	 */
	function parseIssueIdentification($node) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$vol = $num = $year = null;
		$titles = $givenIssueIdentification = array();
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'volume':
						$vol = $n->textContent;
						$givenIssueIdentification[] = 'volue = ' .$vol .' ';
						break;
					case 'number':
						$num = $n->textContent;
						$givenIssueIdentification[] = 'number = ' .$num .' ';
						break;
					case 'year':
						$year = $n->textContent;
						$givenIssueIdentification[] = 'year = ' .$year .' ';
						break;
					case 'title':
						list($locale, $value) = $this->parseLocalizedContent($n);
						if (empty($locale)) $locale = $context->getPrimaryLocale();
						$titles[$locale] = $value;
						$givenIssueIdentification[] = 'title (' .$locale .') = ' .$value .' ';
						break;
				}
			}
		}
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$issuesByIdentification = $issueDao->getIssuesByIdentification($context->getId(), $vol, $num, $year, $titles);
		if ($issuesByIdentification->getCount() != 1) {
			fatalError(__('plugins.importexport.native.import.error.issueIdentificationMatch', array('issueIdentification' => implode(',', $givenIssueIdentification))));
		}
		$issue = $issuesByIdentification->next();
		return $issue;
	}
}

?>
