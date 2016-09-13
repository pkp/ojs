<?php

/**
 * @file plugins/importexport/native/filter/ArticleGalleyNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
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
	function ArticleGalleyNativeXmlFilter($filterGroup) {
		parent::RepresentationNativeXmlFilter($filterGroup);
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
		$deployment = $this->getDeployment();
		$submission = $deployment->getSubmission();
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		return array_filter(
			$submissionFileDao->getLatestRevisions($submission->getId()),
			create_function('$a', 'return $a->getAssocType() == ASSOC_TYPE_GALLEY && $a->getAssocId() == ' . ((int) $representation->getId()) . ';')
		);
	}
}

?>
