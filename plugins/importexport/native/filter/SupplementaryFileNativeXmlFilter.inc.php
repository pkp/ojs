<?php

/**
 * @file plugins/importexport/native/filter/SupplementaryFileNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SupplementaryFileNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Filter to convert a supplementary file to a Native XML document
 */

import('lib.pkp.plugins.importexport.native.filter.SubmissionFileNativeXmlFilter');

class SupplementaryFileNativeXmlFilter extends SubmissionFileNativeXmlFilter {
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
		return 'plugins.importexport.native.filter.SupplementaryFileNativeXmlFilter';
	}


	//
	// Implement/override functions from SubmissionFileNativeXmlFilter
	//
	/**
	 * Create and return a submissionFile node.
	 * @param $doc DOMDocument
	 * @param $submissionFile SubmissionFile
	 * @return DOMElement
	 */
	function createSubmissionFileNode($doc, $submissionFile) {
		$deployment = $this->getDeployment();
		$submissionFileNode = parent::createSubmissionFileNode($doc, $submissionFile);
		$this->createLocalizedNodes($doc, $submissionFileNode, 'creator', $submissionFile->getCreator(null));
		$this->createLocalizedNodes($doc, $submissionFileNode, 'subject', $submissionFile->getSubject(null));
		$this->createLocalizedNodes($doc, $submissionFileNode, 'description', $submissionFile->getDescription(null));
		$this->createLocalizedNodes($doc, $submissionFileNode, 'publisher', $submissionFile->getPublisher(null));
		$this->createLocalizedNodes($doc, $submissionFileNode, 'sponsor', $submissionFile->getSponsor(null));
		if ($dateCreated = $submissionFile->getDateCreated()) {
			$submissionFileNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'date_created',$dateCreated));
		}
		$this->createLocalizedNodes($doc, $submissionFileNode, 'source', $submissionFile->getSource(null));
		if ($language = $submissionFile->getLanguage()) {
			$submissionFileNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'language', htmlspecialchars($language, ENT_COMPAT, 'UTF-8')));
		}
		return $submissionFileNode;
	}

	/**
	 * Get the submission file element name
	 */
	function getSubmissionFileElementName() {
		return 'supplementary_file';
	}
}

?>
