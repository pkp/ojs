<?php

/**
 * @file plugins/importexport/native/filter/ArticleGalleyNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts an ArticleGalley to a Native XML document.
 */

import('lib.pkp.plugins.importexport.native.filter.RepresentationNativeXmlFilter');

class ArticleGalleyNativeXmlFilter extends RepresentationNativeXmlFilter {
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
		return 'plugins.importexport.native.filter.ArticleGalleyNativeXmlFilter';
	}

	//
	// Extend functions in RepresentationNativeXmlFilter
	//
	/**
	 * Create and return a representation node. Extend the parent class
	 * with publication format specific data.
	 * @param $doc DOMDocument
	 * @param $representation Representation
	 * @return DOMElement
	 */
	function createRepresentationNode($doc, $representation) {
		$representationNode = parent::createRepresentationNode($doc, $representation);
		$representationNode->setAttribute('approved', $representation->getIsApproved()?'true':'false');

		$submission = $this->getDeployment()->getSubmission();

		return $representationNode;
	}

	/**
	 * Get the available submission files for a representation
	 * @param $representation Representation
	 * @return array
	 */
	function getFiles($representation) {
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$galleyFiles = array();
		if ($representation->getFileId()) $galleyFiles = array($submissionFileDao->getLatestRevision($representation->getFileId()));
		return $galleyFiles;
	}
}


