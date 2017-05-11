<?php

/**
 * @file plugins/importexport/native/filter/SubmissionNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Base class that converts a set of submissions to a Native XML document
 */

import('lib.pkp.plugins.importexport.native.filter.NativeExportFilter');

class SubmissionNativeXmlFilter extends NativeExportFilter {

	var $_includeSubmissionsNode;

	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('Native XML submission export');
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.importexport.native.filter.SubmissionNativeXmlFilter';
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $submissions array Array of submissions
	 * @return DOMDocument
	 */
	function &process(&$submissions) {
		// Create the XML document
		$doc = new DOMDocument('1.0');
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		$deployment = $this->getDeployment();

		if (count($submissions)==1 && !$this->getIncludeSubmissionsNode()) {
			// Only one submission specified; create root node
			$rootNode = $this->createSubmissionNode($doc, $submissions[0]);
		} else {
			// Multiple submissions; wrap in a <submissions> element
			$rootNode = $doc->createElementNS($deployment->getNamespace(), $deployment->getSubmissionsNodeName());
			foreach ($submissions as $submission) {
				$rootNode->appendChild($this->createSubmissionNode($doc, $submission));
			}
		}
		$doc->appendChild($rootNode);
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$rootNode->setAttribute('xsi:schemaLocation', $deployment->getNamespace() . ' ' . $deployment->getSchemaFilename());

		return $doc;
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
		// Create the root node and attributes
		$deployment = $this->getDeployment();
		$deployment->setSubmission($submission);
		$submissionNode = $doc->createElementNS($deployment->getNamespace(), $deployment->getSubmissionNodeName());
		$submissionNode->setAttribute('locale', $submission->getLocale());
		$submissionLanguage = $submission->getLanguage();
		if ($submissionLanguage) {
			$submissionNode->setAttribute('language', $submissionLanguage);
		}
		$submissionNode->setAttribute('date_submitted', strftime('%Y-%m-%d', strtotime($submission->getDateSubmitted())));

		$workflowStageDao = DAORegistry::getDAO('WorkflowStageDAO');
		$submissionNode->setAttribute('stage', WorkflowStageDAO::getPathFromId($submission->getStageId()));

		if ($datePublished = $submission->getDatePublished()) {
			$submissionNode->setAttribute('date_published', strftime('%Y-%m-%d', strtotime($datePublished)));
		}
		// FIXME: language attribute (from old DTD). Necessary? Data migration needed?

		$this->addIdentifiers($doc, $submissionNode, $submission);
		$this->addMetadata($doc, $submissionNode, $submission);
		$this->addAuthors($doc, $submissionNode, $submission);
		$this->addFiles($doc, $submissionNode, $submission);
		$this->addRepresentations($doc, $submissionNode, $submission);

		return $submissionNode;
	}

	/**
	 * Create and add identifier nodes to a submission node.
	 * @param $doc DOMDocument
	 * @param $submissionNode DOMElement
	 * @param $submission Submission
	 */
	function addIdentifiers($doc, $submissionNode, $submission) {
		$deployment = $this->getDeployment();

		// Add internal ID
		$submissionNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', $submission->getId()));
		$node->setAttribute('type', 'internal');
		$node->setAttribute('advice', 'ignore');

		// Add public ID
		if ($pubId = $submission->getStoredPubId('publisher-id')) {
			$submissionNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', htmlspecialchars($pubId, ENT_COMPAT, 'UTF-8')));
			$node->setAttribute('type', 'public');
			$node->setAttribute('advice', 'update');
		}

		// Add pub IDs by plugin
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $deployment->getContext()->getId());
		foreach ((array) $pubIdPlugins as $pubIdPlugin) {
			$this->addPubIdentifier($doc, $submissionNode, $submission, $pubIdPlugin);
		}
	}

	/**
	 * Add a single pub ID element for a given plugin to the document.
	 * @param $doc DOMDocument
	 * @param $submissionNode DOMElement
	 * @param $submission Submission
	 * @param $pubIdPlugin PubIdPlugin
	 * @return DOMElement|null
	 */
	function addPubIdentifier($doc, $submissionNode, $submission, $pubIdPlugin) {
		$pubId = $submission->getStoredPubId($pubIdPlugin->getPubIdType());
		if ($pubId) {
			$deployment = $this->getDeployment();
			$submissionNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', htmlspecialchars($pubId, ENT_COMPAT, 'UTF-8')));
			$node->setAttribute('type', $pubIdPlugin->getPubIdType());
			$node->setAttribute('advice', 'update');
			return $node;
		}
		return null;
	}

	/**
	 * Add the submission metadata for a submission to its DOM element.
	 * @param $doc DOMDocument
	 * @param $submissionNode DOMElement
	 * @param $submission Submission
	 */
	function addMetadata($doc, $submissionNode, $submission) {
		$this->createLocalizedNodes($doc, $submissionNode, 'title', $submission->getTitle(null, false));
		$this->createLocalizedNodes($doc, $submissionNode, 'prefix', $submission->getPrefix(null));
		$this->createLocalizedNodes($doc, $submissionNode, 'subtitle', $submission->getSubtitle(null));
		$this->createLocalizedNodes($doc, $submissionNode, 'abstract', $submission->getAbstract(null));
		$this->createLocalizedNodes($doc, $submissionNode, 'coverage', $submission->getCoverage(null));
		$this->createLocalizedNodes($doc, $submissionNode, 'type', $submission->getType(null));
		$this->createLocalizedNodes($doc, $submissionNode, 'source', $submission->getSource(null));
		$this->createLocalizedNodes($doc, $submissionNode, 'rights', $submission->getRights(null));

		// add controlled vocabularies
		// get the supported locale keys
		$supportedLocales = array_keys(AppLocale::getSupportedFormLocales());
		$controlledVocabulariesMapping = $this->_getControlledVocabulariesMappings();
		foreach ($controlledVocabulariesMapping as $controlledVocabulariesNodeName => $mappings) {
			$dao = DAORegistry::getDAO($mappings[0]);
			$getFunction = $mappings[1];
			$controlledVocabularyNodeName = $mappings[2];
			$controlledVocabulary = $dao->$getFunction($submission->getId(), $supportedLocales);
			$this->addControlledVocabulary($doc, $submissionNode, $controlledVocabulariesNodeName, $controlledVocabularyNodeName, $controlledVocabulary);
		}
	}

	/**
	 * Add submission controlled vocabulary to its DOM element.
	 * @param $doc DOMDocument
	 * @param $submissionNode DOMElement
	 * @param $controlledVocabulariesNodeName string Parent node name
	 * @param $controlledVocabularyNodeName string Item node name
	 * @param $controlledVocabulary array Associative array (locale => array of items)
	 */
	function addControlledVocabulary($doc, $submissionNode, $controlledVocabulariesNodeName, $controlledVocabularyNodeName, $controlledVocabulary) {
		$deployment = $this->getDeployment();
		$locales = array_keys($controlledVocabulary);
		foreach ($locales as $locale) {
			if (!empty($controlledVocabulary[$locale])) {
				$controlledVocabulariesNode = $doc->createElementNS($deployment->getNamespace(), $controlledVocabulariesNodeName);
				$controlledVocabulariesNode->setAttribute('locale', $locale);
				foreach ($controlledVocabulary[$locale] as $controlledVocabularyItem) {
					$controlledVocabulariesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), $controlledVocabularyNodeName, htmlspecialchars($controlledVocabularyItem, ENT_COMPAT, 'UTF-8')));
				}
				$submissionNode->appendChild($controlledVocabulariesNode);
			}
		}
	}

	/**
	 * Add the author metadata for a submission to its DOM element.
	 * @param $doc DOMDocument
	 * @param $submissionNode DOMElement
	 * @param $submission Submission
	 */
	function addAuthors($doc, $submissionNode, $submission) {
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$nativeExportFilters = $filterDao->getObjectsByGroup('author=>native-xml');
		assert(count($nativeExportFilters)==1); // Assert only a single serialization filter
		$exportFilter = array_shift($nativeExportFilters);
		$exportFilter->setDeployment($this->getDeployment());

		$authorsDoc = $exportFilter->execute($submission->getAuthors());
		if ($authorsDoc->documentElement instanceof DOMElement) {
			$clone = $doc->importNode($authorsDoc->documentElement, true);
			$submissionNode->appendChild($clone);
		}
	}

	/**
	 * Add the representations of a submission to its DOM element.
	 * @param $doc DOMDocument
	 * @param $submissionNode DOMElement
	 * @param $submission Submission
	 */
	function addRepresentations($doc, $submissionNode, $submission) {
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$nativeExportFilters = $filterDao->getObjectsByGroup($this->getRepresentationExportFilterGroupName());
		assert(count($nativeExportFilters)==1); // Assert only a single serialization filter
		$exportFilter = array_shift($nativeExportFilters);
		$exportFilter->setDeployment($this->getDeployment());

		$representationDao = Application::getRepresentationDAO();
		$representations = $representationDao->getBySubmissionId($submission->getId());
		while ($representation = $representations->next()) {
			$representationDoc = $exportFilter->execute($representation);
			$clone = $doc->importNode($representationDoc->documentElement, true);
			$submissionNode->appendChild($clone);
		}
	}

	/**
	 * Add the submission files to its DOM element.
	 * @param $doc DOMDocument
	 * @param $submissionNode DOMElement
	 * @param $submission Submission
	 */
	function addFiles($doc, $submissionNode, $submission) {
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		$submissionFiles = $submissionFileDao->getBySubmissionId($submission->getId());

		// Submission files will come back from the file export filter
		// with one revision each, wrapped in a submission_file node:
		// <submission_file ...>
		//  <revision ...>...</revision>
		// </submission_file>
		// Reformat them into groups by submission_file, i.e.:
		// <submission_file ...>
		//  <revision ...>...</revision>
		//  <revision ...>...</revision>
		// </submission_file>
		$submissionFileNodesByFileId = array();
		foreach ($submissionFiles as $submissionFile) {
			$nativeExportFilters = $filterDao->getObjectsByGroup(get_class($submissionFile) . '=>native-xml');
			assert(count($nativeExportFilters)==1); // Assert only a single serialization filter
			$exportFilter = array_shift($nativeExportFilters);
			$exportFilter->setDeployment($this->getDeployment());

			$submissionFileDoc = $exportFilter->execute($submissionFile);
			$fileId = $submissionFileDoc->documentElement->getAttribute('id');
			if (!isset($submissionFileNodesByFileId[$fileId])) {
				$clone = $doc->importNode($submissionFileDoc->documentElement, true);
				$submissionNode->appendChild($clone);
				$submissionFileNodesByFileId[$fileId] = $clone;
			} else {
				$submissionFileNode = $submissionFileNodesByFileId[$fileId];
				// Look for a <revision> element
				$revisionNode = null;
				foreach ($submissionFileDoc->documentElement->childNodes as $childNode) {
					if (!is_a($childNode, 'DOMElement')) continue;
					if ($childNode->tagName == 'revision') $revisionNode = $childNode;
				}
				assert(is_a($revisionNode, 'DOMElement'));
				$clone = $doc->importNode($revisionNode, true);
				$submissionFileNode->appendChild($clone);
			}
		}
	}


	//
	// Abstract methods for subclasses to implement
	//
	/**
	 * Get the representation export filter group name
	 * @return string
	 */
	function getRepresentationExportFilterGroupName() {
		assert(false); // Must be overridden by subclasses
	}

	/**
	 * Sets a flag to always include the <submissions> node, even if there
	 * may only be one submission.
	 * @param boolean $includeSubmissionsNode
	 */
	function setIncludeSubmissionsNode($includeSubmissionsNode) {
		$this->_includeSubmissionsNode = $includeSubmissionsNode;
	}

	/**
	 * Returnes whether to always include the <submissions> node, even if there
	 * may only be one submission.
	 * @return boolean $includeSubmissionsNode
	 */
	function getIncludeSubmissionsNode() {
		return $this->_includeSubmissionsNode;
	}

	/**
	 * Get controlled vocabularies parent node name to DAO, get function and item node name mapping.
	 * @return array
	 */
	function _getControlledVocabulariesMappings() {
		return array(
				'keywords' => array('SubmissionKeywordDAO', 'getKeywords', 'keyword'),
				'agencies' => array('SubmissionAgencyDAO', 'getAgencies', 'agency'),
				'disciplines' => array('SubmissionDisciplineDAO', 'getDisciplines', 'disciplin'),
				'subjects' => array('SubmissionSubjectDAO', 'getSubjects', 'subject'),
		);
	}
}

?>
