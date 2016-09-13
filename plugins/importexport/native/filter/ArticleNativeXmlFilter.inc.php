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
		return $submissionNode;
	}
}

?>
