<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlSupplementaryFileFilter.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlSupplementaryFileFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Native XML document to an supplementary file.
 */

import('plugins.importexport.native.filter.NativeXmlArticleFileFilter');

class NativeXmlSupplementaryFileFilter extends NativeXmlArticleFileFilter {
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
		return 'plugins.importexport.native.filter.NativeXmlSupplementaryFileFilter';
	}

	//
	// Override methods in NativeImportFilter
	//
	/**
	 * Return the plural element name
	 * @return string
	 */
	function getPluralElementName() {
		return 'supplementary_files';
	}

	/**
	 * Get the singular element name
	 * @return string
	 */
	function getSingularElementName() {
		return 'supplementary_file';
	}


	//
	// Extend functions in the parent class
	//
	/**
	 * Handle a child node of the submission file element; add new files, if
	 * any, to $submissionFiles
	 * @param $node DOMElement
	 * @param $stageId int SUBMISSION_FILE_...
	 * @param $fileId int File id
	 * @param $submissionFiles array
	 */
	function handleChildElement($node, $stageId, $fileId, &$submissionFiles) {
		$deployment = $this->getDeployment();
		$submission = $deployment->getSubmission();
		$localizedSetterMappings = $this->_getLocalizedSupplementaryFileSetterMappings();
		if (isset($localizedSetterMappings[$node->tagName])) {
			// If applicable, call a setter for localized content.
			$setterFunction = $localizedSetterMappings[$node->tagName];
			list($locale, $value) = $this->parseLocalizedContent($node);
			if (empty($locale)) $locale = $submission->getLocale();
			if (count($submissionFiles) > 0) $submissionFiles[count($submissionFiles)-1]->$setterFunction($value, $locale);
		} else switch ($node->tagName) {
			case 'date_created':
				if (count($submissionFiles) > 0) $submissionFiles[count($submissionFiles)-1]->setDateCreated(strtotime($node->textContent));
				break;
			case 'language':
				if (count($submissionFiles) > 0) $submissionFiles[count($submissionFiles)-1]->setLanguage($node->textContent);
				break;
			default:
				parent::handleChildElement($node, $stageId, $fileId, $submissionFiles);
		}
	}

	//
	// Helper functions
	//
	/**
	 * Get node name to setter function mapping for localized data.
	 * @return array
	 */
	function _getLocalizedSupplementaryFileSetterMappings() {
		return array(
			'creator' => 'setCreator',
			'subject' => 'setSubject',
			'description' => 'setDescription',
			'publisher' => 'setPublisher',
			'sponsor' => 'setSponsor',
			'source' => 'setSource',
		);
	}
}


