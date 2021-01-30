<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlArticleFilter.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
		return 'plugins.importexport.native.filter.NativeXmlArticleFilter';
	}

	/**
	 * Get the method name for inserting a published submission.
	 * @return string
	 */
	function getPublishedSubmissionInsertMethod() {
		return 'insertObject';
	}

	/**
	 * @see Filter::process()
	 * @param $document DOMDocument|string
	 * @return array Array of imported documents
	 */
	function &process(&$document) {
		$importedObjects =& parent::process($document);

		$deployment = $this->getDeployment();
		$submission = $deployment->getSubmission();

		// Index imported content
		$articleSearchIndex = Application::getSubmissionSearchIndex();
		foreach ($importedObjects as $submission) {
			assert(is_a($submission, 'Submission'));
			$articleSearchIndex->submissionMetadataChanged($submission);
			$articleSearchIndex->submissionFilesChanged($submission);
		}

		$articleSearchIndex->submissionChangesFinished();

		return $importedObjects;
	}

	/**
	 * Populate the submission object from the node, checking first for a valid section and published_date/issue relationship
	 * @param $submission Submission
	 * @param $node DOMElement
	 * @return Submission
	 */
	function populateObject($submission, $node) {
		return parent::populateObject($submission, $node);
	}

	/**
	 * Get the import filter for a given element.
	 * @param $elementName string Name of XML element
	 * @return Filter
	 */
	function getImportFilter($elementName) {
		$deployment = $this->getDeployment();
		$submission = $deployment->getSubmission();
		switch ($elementName) {
			case 'submission_file':
				$importClass='SubmissionFile';
				break;
			case 'publication':
				$importClass='Publication';
				break;
			default:
				$importClass=null; // Suppress scrutinizer warn
				$deployment->addWarning(ASSOC_TYPE_SUBMISSION, $submission->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $elementName)));
		}
		// Caps on class name for consistency with imports, whose filter
		// group names are generated implicitly.
		$filterDao = DAORegistry::getDAO('FilterDAO'); /* @var $filterDao FilterDAO */
		$importFilters = $filterDao->getObjectsByGroup('native-xml=>' . $importClass);
		$importFilter = array_shift($importFilters);
		return $importFilter;
	}
}
