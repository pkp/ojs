<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlArticleFilter.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2000-2014 John Willinsky
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
	function getPublishedSubmissionInsertMethod(){
		return 'insertPublishedArticle';
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
				$this->parseSubmissionFile($n, $submission);
				break;
			case 'article_galley':
				$this->parseArticleGalley($n, $submission);
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
				$importClass='ArtworkFile';
				break;
			case 'article_galley':
				$importClass='ArticleGalley';
				break;
			default:
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
		$submission->setSeq($node->getAttribute('seq'));
		$submission->setAccessStatus($node->getAttribute('access_status'));
		$submission->setIssueId($issue->getId());
		return $submission;
	}
}

?>
