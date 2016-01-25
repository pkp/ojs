<?php

/**
 * @file plugins/importexport/native/filter/ArtworkFileNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
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
	function ArtworkFileNativeXmlFilter($filterGroup) {
		parent::SubmissionFileNativeXmlFilter($filterGroup);
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
			$submissionFileNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'caption', $caption));
		}
		if ($credit = $submissionFile->getCredit()) {
			$submissionFileNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'credit', $credit));
		}
		if ($copyrightOwner = $submissionFile->getCopyrightOwner()) {
			$submissionFileNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'copyright_owner', $copyrightOwner));
		}
		if ($copyrightOwnerContact = $submissionFile->getCopyrightOwnerContactDetails()) {
			$submissionFileNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'copyright_owner_contact', $copyrightOwnerContact));
		}
		if ($permissionTerms = $submissionFile->getPermissionTerms()) {
			$submissionFileNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'permission_terms', $permissionTerms));
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

?>
