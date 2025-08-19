<?php

/**
 * @file plugins/importexport/native/filter/IssueNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Base class that converts a set of issues to a Native XML document
 */

use function PHP81_BC\strftime;

import('lib.pkp.plugins.importexport.native.filter.NativeExportFilter');

class IssueNativeXmlFilter extends NativeExportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('Native XML issue export');
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.IssueNativeXmlFilter';
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $issues array Array of issues
	 * @return DOMDocument
	 */
	function &process(&$issues) {
		// Create the XML document
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		$deployment = $this->getDeployment();

		if (count($issues)==1) {
			// Only one issue specified; create root node
			$rootNode = $this->createIssueNode($doc, $issues[0]);
		} else {
			// Multiple issues; wrap in a <issues> element
			$rootNode = $doc->createElementNS($deployment->getNamespace(), 'issues');
			foreach ($issues as $issue) {
				$rootNode->appendChild($this->createIssueNode($doc, $issue));
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
	 * Create and return an issue node.
	 * @param $doc DOMDocument
	 * @param $issue Issue
	 * @return DOMElement
	 */
	function createIssueNode($doc, $issue) {
		// Create the root node and attributes
		$deployment = $this->getDeployment();
		$deployment->setIssue($issue);

		$issueNode = $doc->createElementNS($deployment->getNamespace(), 'issue');
		$this->addIdentifiers($doc, $issueNode, $issue);

		$issueNode->setAttribute('published', $issue->getPublished());
		$issueNode->setAttribute('current', $issue->getCurrent());
		$issueNode->setAttribute('access_status', $issue->getAccessStatus());
		$issueNode->setAttribute('url_path', $issue->getData('urlPath'));

		$this->createLocalizedNodes($doc, $issueNode, 'description', $issue->getDescription(null));
		import('plugins.importexport.native.filter.NativeFilterHelper');
		$nativeFilterHelper = new NativeFilterHelper();
		$issueNode->appendChild($nativeFilterHelper->createIssueIdentificationNode($this, $doc, $issue));

		$this->addDates($doc, $issueNode, $issue);
		$this->addSections($doc, $issueNode, $issue);
		// cover images
		import('plugins.importexport.native.filter.NativeFilterHelper');
		$nativeFilterHelper = new NativeFilterHelper();
		$coversNode = $nativeFilterHelper->createIssueCoversNode($this, $doc, $issue);
		if ($coversNode) $issueNode->appendChild($coversNode);

		$this->addIssueGalleys($doc, $issueNode, $issue);
		$this->addArticles($doc, $issueNode, $issue);

		return $issueNode;
	}

	/**
	 * Create and add identifier nodes to an issue node.
	 * @param $doc DOMDocument
	 * @param $issueNode DOMElement
	 * @param $issue Issue
	 */
	function addIdentifiers($doc, $issueNode, $issue) {
		$deployment = $this->getDeployment();

		// Add internal ID
		$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', $issue->getId()));
		$node->setAttribute('type', 'internal');
		$node->setAttribute('advice', 'ignore');

		// Add public ID
		if ($pubId = $issue->getStoredPubId('publisher-id')) {
			$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', htmlspecialchars($pubId, ENT_COMPAT, 'UTF-8')));
			$node->setAttribute('type', 'public');
			$node->setAttribute('advice', 'update');
		}

		// Add pub IDs by plugin
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $deployment->getContext()->getId());
		foreach ($pubIdPlugins as $pubIdPlugin) {
			$this->addPubIdentifier($doc, $issueNode, $issue, $pubIdPlugin);
		}
	}

	/**
	 * Add a single pub ID element for a given plugin to the document.
	 * @param $doc DOMDocument
	 * @param $issueNode DOMElement
	 * @param $issue Issue
	 * @param $pubIdPlugin PubIdPlugin
	 * @return DOMElement|null
	 */
	function addPubIdentifier($doc, $issueNode, $issue, $pubIdPlugin) {
		$pubId = $issue->getStoredPubId($pubIdPlugin->getPubIdType());
		if ($pubId) {
			$deployment = $this->getDeployment();
			$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', htmlspecialchars($pubId, ENT_COMPAT, 'UTF-8')));
			$node->setAttribute('type', $pubIdPlugin->getPubIdType());
			$node->setAttribute('advice', 'update');
			return $node;
		}
		return null;
	}

	/**
	 * Create and add various date nodes to an issue node.
	 * @param $doc DOMDocument
	 * @param $issueNode DOMElement
	 * @param $issue Issue
	 */
	function addDates($doc, $issueNode, $issue) {
		$deployment = $this->getDeployment();

		if ($issue->getDatePublished())
			$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'date_published', strftime('%Y-%m-%d', strtotime($issue->getDatePublished()))));

		if ($issue->getDateNotified())
			$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'date_notified', strftime('%Y-%m-%d', strtotime($issue->getDateNotified()))));

		if ($issue->getLastModified())
			$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'last_modified', strftime('%Y-%m-%d', strtotime($issue->getLastModified()))));

		if ($issue->getOpenAccessDate())
			$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'open_access_date', strftime('%Y-%m-%d', strtotime($issue->getOpenAccessDate()))));
	}

	/**
	 * Create and add articles to an issue node.
	 * @param $doc DOMDocument
	 * @param $issueNode DOMElement
	 * @param $issue Issue
	 */
	function addArticles($doc, $issueNode, $issue) {
		$filterDao = DAORegistry::getDAO('FilterDAO'); /* @var $filterDao FilterDAO */
		$nativeExportFilters = $filterDao->getObjectsByGroup('article=>native-xml');
		assert(count($nativeExportFilters)==1); // Assert only a single serialization filter
		$exportFilter = array_shift($nativeExportFilters);
		$exportFilter->setOpts($this->opts);
		$exportFilter->setDeployment($this->getDeployment());
		$exportFilter->setIncludeSubmissionsNode(true);

		$submissionsIterator = Services::get('submission')->getMany([
			'contextId' => $issue->getJournalId(),
			'issueIds' => $issue->getId(),
		]);
		$submissionsArray = iterator_to_array($submissionsIterator);
		$articlesDoc = $exportFilter->execute($submissionsArray);
		if ($articlesDoc->documentElement instanceof DOMElement) {
			$clone = $doc->importNode($articlesDoc->documentElement, true);
			$issueNode->appendChild($clone);
		}
	}

	/**
	 * Create and add issue galleys to an issue node.
	 * @param $doc DOMDocument
	 * @param $issueNode DOMElement
	 * @param $issue Issue
	 */
	function addIssueGalleys($doc, $issueNode, $issue) {
		$filterDao = DAORegistry::getDAO('FilterDAO'); /* @var $filterDao FilterDAO */
		$nativeExportFilters = $filterDao->getObjectsByGroup('issuegalley=>native-xml');
		assert(count($nativeExportFilters)==1); // Assert only a single serialization filter
		$exportFilter = array_shift($nativeExportFilters);
		$exportFilter->setOpts($this->opts);
		$exportFilter->setDeployment($this->getDeployment());

		$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /* @var $issueGalleyDao IssueGalleyDAO */
		$issue = $issueGalleyDao->getByIssueId($issue->getId());
		$issueGalleysDoc = $exportFilter->execute($issue);
		if ($issueGalleysDoc->documentElement instanceof DOMElement) {
			$clone = $doc->importNode($issueGalleysDoc->documentElement, true);
			$issueNode->appendChild($clone);
		}
	}

	/**
	 * Add the sections to the Issue DOM element.
	 * @param $doc DOMDocument
	 * @param $issueNode DOMElement
	 * @param $issue Issue
	 */
	function addSections($doc, $issueNode, $issue) {
		$sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
		$sections = $sectionDao->getByIssueId($issue->getId());
		$deployment = $this->getDeployment();
		$journal = $deployment->getContext();

		// Boundary condition: no sections in this issue.
		if (!count($sections)) return;

		$sectionsNode = $doc->createElementNS($deployment->getNamespace(), 'sections');
		foreach ($sections as $section) {
			$sectionNode = $doc->createElementNS($deployment->getNamespace(), 'section');

			$sectionNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', $section->getId()));
			$node->setAttribute('type', 'internal');
			$node->setAttribute('advice', 'ignore');

			if ($section->getReviewFormId()) $sectionNode->setAttribute('review_form_id', $section->getReviewFormId());
			$sectionNode->setAttribute('ref', $section->getAbbrev($journal->getPrimaryLocale()));
			$sectionNode->setAttribute('seq', (int) $section->getSequence());
			$sectionNode->setAttribute('editor_restricted', $section->getEditorRestricted());
			$sectionNode->setAttribute('meta_indexed', $section->getMetaIndexed());
			$sectionNode->setAttribute('meta_reviewed', $section->getMetaReviewed());
			$sectionNode->setAttribute('abstracts_not_required', $section->getAbstractsNotRequired());
			$sectionNode->setAttribute('hide_title', $section->getHideTitle());
			$sectionNode->setAttribute('hide_author', $section->getHideAuthor());
			$sectionNode->setAttribute('abstract_word_count', (int) $section->getAbstractWordCount());

			$this->createLocalizedNodes($doc, $sectionNode, 'abbrev', $section->getAbbrev(null));
			$this->createLocalizedNodes($doc, $sectionNode, 'policy', $section->getPolicy(null));
			$this->createLocalizedNodes($doc, $sectionNode, 'title', $section->getTitle(null));

			$sectionsNode->appendChild($sectionNode);
		}

		$issueNode->appendChild($sectionsNode);
	}

}
