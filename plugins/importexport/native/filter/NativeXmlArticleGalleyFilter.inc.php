<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlArticleGalleyFilter.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlArticleGalleyFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Native XML document to a set of publication formats.
 */

import('lib.pkp.plugins.importexport.native.filter.NativeXmlRepresentationFilter');

class NativeXmlArticleGalleyFilter extends NativeXmlRepresentationFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		parent::__construct($filterGroup);
	}

	//
	// Implement template methods from NativeImportFilter
	//
	/**
	 * Return the plural element name
	 * @return string
	 */
	function getPluralElementName() {
		return 'article_galleys'; // defined if needed in the future.
	}

	/**
	 * Get the singular element name
	 * @return string
	 */
	function getSingularElementName() {
		return 'article_galley';
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.NativeXmlArticleGalleyFilter';
	}


	/**
	 * Handle a submission element
	 * @param $node DOMElement
	 * @return array Array of ArticleGalley objects
	 */
	function handleElement($node) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$submission = $deployment->getSubmission();
		assert(is_a($submission, 'Submission'));

		$submissionFileRefNodes = $node->getElementsByTagName('submission_file_ref');
		assert($submissionFileRefNodes->length <= 1);
		$addSubmissionFile = false;
		if ($submissionFileRefNodes->length == 1) {
			$fileNode = $submissionFileRefNodes->item(0);
			$fileId = $fileNode->getAttribute('id');
			$revisionId = $fileNode->getAttribute('revision');
			$dbFileId = $deployment->getFileDBId($fileId, $revisionId);
			if ($dbFileId) $addSubmissionFile = true;
		}
		$representation = parent::handleElement($node);

		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) if (is_a($n, 'DOMElement')) switch($n->tagName) {
			case 'name':
				// Labels are not localized in OJS ArticleGalleys, but we use the <name locale="....">...</name> structure.
				$locale = $n->getAttribute('locale');
				if (empty($locale)) $locale = $submission->getLocale();
				$representation->setLabel($n->textContent);
				$representation->setLocale($locale);
				break;
		}

		$representationDao = Application::getRepresentationDAO();
		if ($addSubmissionFile) $representation->setFileId($dbFileId);
		$representationDao->insertObject($representation);

		if ($addSubmissionFile) {
			// Update the submission file.
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
			$submissionFile = $submissionFileDao->getRevision($dbFileId, $revisionId);
			$submissionFile->setAssocType(ASSOC_TYPE_REPRESENTATION);
			$submissionFile->setAssocId($representation->getId());
			$submissionFileDao->updateObject($submissionFile);
		}

		// representation proof files
		return $representation;
	}

	/**
	 * Process the self_file_ref node found inside the article_galley node.
	 * @param $node DOMElement
	 * @param $deployment NativeImportExportDeployment
	 * @param $representation ArticleGalley
	 */
	function _processFileRef($node, $deployment, $representation) {
	}
}

?>
