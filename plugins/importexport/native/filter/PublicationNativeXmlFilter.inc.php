<?php

/**
 * @file plugins/importexport/native/filter/PublicationNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Publication to a Native XML document.
 */

import('lib.pkp.plugins.importexport.native.filter.PKPPublicationNativeXmlFilter');

class PublicationNativeXmlFilter extends PKPPublicationNativeXmlFilter {
	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.PublicationNativeXmlFilter';
	}

	


	//
	// Implement abstract methods from SubmissionNativeXmlFilter
	//
	/**
	 * Get the representation export filter group name
	 * @return string
	 */
	function getRepresentationExportFilterGroupName() {
		return 'article-galley=>native-xml';
	}

	//
	// Publication conversion functions
	//
	/**
	 * Create and return a publication node.
	 * @param $doc DOMDocument
	 * @param $entity Publication
	 * @return DOMElement
	 */
	function createEntityNode($doc, $entity) {
		$deployment = $this->getDeployment();
		$entityNode = parent::createEntityNode($doc, $entity);

		// Add the series, if one is designated.
		if ($sectionId = $entity->getData('sectionId')) {
			$sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var $sectionDao SectionDAO */
			$section = $sectionDao->getById($sectionId);
			assert(isset($section));
			$entityNode->setAttribute('section_ref', $section->getLocalizedAbbrev());
		}

		$isPublished = $entity->getData('status') === STATUS_PUBLISHED;
		$isPublished ? $entityNode->setAttribute('seq', (int) $entity->getData('seq')) : $entityNode->setAttribute('seq', '0');
		$isPublished ? $entityNode->setAttribute('access_status', $entity->getData('accessStatus')) : $entityNode->setAttribute('access_status', '0');

		// if this is a published submission and not part/subelement of an issue element
		// add issue identification element
		if ($entity->getData('issueId') && !$deployment->getIssue()) {
			$issueDao = DAORegistry::getDAO('IssueDAO'); /** @var $issueDao IssueDAO */
			$issue = $issueDao->getById($entity->getData('issueId'));
			import('plugins.importexport.native.filter.NativeFilterHelper');
			$nativeFilterHelper = new NativeFilterHelper();
			$entityNode->appendChild($nativeFilterHelper->createIssueIdentificationNode($this, $doc, $issue));
		}

		$pages = $entity->getData('pages');
		if (!empty($pages)) $entityNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'pages', htmlspecialchars($pages, ENT_COMPAT, 'UTF-8')));

		// cover images
		import('plugins.importexport.native.filter.NativeFilterHelper');
		$nativeFilterHelper = new NativeFilterHelper();
		$coversNode = $nativeFilterHelper->createPublicationCoversNode($this, $doc, $entity);
		if ($coversNode) $entityNode->appendChild($coversNode);

		$citationsListNode = $this->createCitationsNode($doc, $deployment, $entity);
		if ($citationsListNode) {
			$entityNode->appendChild($citationsListNode);
		}

		return $entityNode;
	}

	/**
	 * Create and return a Citations node.
	 * @param $doc DOMDocument
	 * @param $deployment
	 * @param $publication Publication
	 * @return DOMElement
	 */
	private function createCitationsNode($doc, $deployment, $publication) {
		$citationDao = DAORegistry::getDAO('CitationDAO');

		$nodeCitations = $doc->createElementNS($deployment->getNamespace(), 'citations');
		$submissionCitations = $citationDao->getByPublicationId($publication->getId());
		if ($submissionCitations->getCount() != 0) {
			while ($elementCitation = $submissionCitations->next()) {
				$rawCitation = $elementCitation->getRawCitation();
				$nodeCitations->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'citation', htmlspecialchars($rawCitation, ENT_COMPAT, 'UTF-8')));
			}

			return $nodeCitations;
		}

		return null;
	}
}
