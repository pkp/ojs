<?php

/**
 * @file plugins/importexport/native/filter/ArtworkFileNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArtworkFileNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Filter to convert an artwork file to a Native XML document
 */

import('lib.pkp.plugins.importexport.native.filter.SubmissionFileNativeXmlFilter');

class ArtworkFileNativeXmlFilter extends SubmissionFileNativeXmlFilter {
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
		return 'plugins.importexport.native.filter.ArtworkFileNativeXmlFilter';
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
		if ($caption = $submissionFile->getCaption()) {
			$submissionFileNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'caption', htmlspecialchars($caption, ENT_COMPAT, 'UTF-8')));
		}
		if ($credit = $submissionFile->getCredit()) {
			$submissionFileNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'credit', htmlspecialchars($credit, ENT_COMPAT, 'UTF-8')));
		}
		if ($copyrightOwner = $submissionFile->getCopyrightOwner()) {
			$submissionFileNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'copyright_owner', htmlspecialchars($copyrightOwner, ENT_COMPAT, 'UTF-8')));
		}
		if ($copyrightOwnerContact = $submissionFile->getCopyrightOwnerContactDetails()) {
			$submissionFileNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'copyright_owner_contact', htmlspecialchars($copyrightOwnerContact, ENT_COMPAT, 'UTF-8')));
		}
		if ($permissionTerms = $submissionFile->getPermissionTerms()) {
			$submissionFileNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'permission_terms', htmlspecialchars($permissionTerms, ENT_COMPAT, 'UTF-8')));
		}

		// FIXME: is permission file ID implemented?
		// FIXME: is chapter ID implemented?
		// FIXME: is contact author ID implemented?

		return $submissionFileNode;
	}

	/**
	 * Get the submission file element name
	 */
	function getSubmissionFileElementName() {
		return 'artwork_file';
	}
}


