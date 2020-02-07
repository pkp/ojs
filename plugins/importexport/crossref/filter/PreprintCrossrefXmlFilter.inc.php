<?php

/**
 * @file plugins/importexport/crossref/filter/PreprintCrossrefXmlFilter.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PreprintCrossrefXmlFilter
 * @ingroup plugins_importexport_crossref
 *
 * @brief Class that converts a Preprint to a Crossref XML document.
 */

import('lib.pkp.plugins.importexport.native.filter.NativeExportFilter');

class PreprintCrossrefXmlFilter extends NativeExportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('Crossref XML preprint export');
		parent::__construct($filterGroup);
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.crossref.filter.PreprintCrossrefXmlFilter';
	}

	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $pubObjects array Array of Issues or Submissions
	 * @return DOMDocument
	 */
	function &process(&$pubObjects) {
		// Create the XML document
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();

		// Create the root node
		$rootNode = $this->createRootNode($doc);
		$doc->appendChild($rootNode);

		// Create and appet the 'head' node and all parts inside it
		$rootNode->appendChild($this->createHeadNode($doc));

		// Create and append the 'body' node, that contains everything
		$bodyNode = $doc->createElementNS($deployment->getNamespace(), 'body');
		$rootNode->appendChild($bodyNode);

		foreach($pubObjects as $pubObject) {
			$publications = $pubObject->getData('publications');
			// Use array reverse so that the latest version of the submission is first in the xml output and the DOI relations do not cause an error with Crossref
			$publications = array_reverse($publications, true);
			foreach ($publications as $publication) {
				if ($publication->getStoredPubId('doi')) {					
					$postedContentNode = $this->createPostedContentNode($doc, $publication, $pubObject);
					$bodyNode->appendChild($postedContentNode);
				}
			}
		}
		return $doc;
	}

	//
	// Submission conversion functions
	//

	/**
	 * Create and return the root node 'doi_batch'.
	 * @param $doc DOMDocument
	 * @return DOMElement
	 */
	function createRootNode($doc) {
		$deployment = $this->getDeployment();
		$rootNode = $doc->createElementNS($deployment->getNamespace(), $deployment->getRootElementName());
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', $deployment->getXmlSchemaInstance());
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:jats', $deployment->getJATSNamespace());
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ai', $deployment->getAINamespace());
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:rel', $deployment->getRELNamespace());
		$rootNode->setAttribute('version', $deployment->getXmlSchemaVersion());
		$rootNode->setAttribute('xsi:schemaLocation', $deployment->getNamespace() . ' ' . $deployment->getSchemaFilename());
		return $rootNode;
	}

	/**
	 * Create and return the head node 'head'.
	 * @param $doc DOMDocument
	 * @return DOMElement
	 */
	function createHeadNode($doc) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$plugin = $deployment->getPlugin();
		$headNode = $doc->createElementNS($deployment->getNamespace(), 'head');
		$headNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'doi_batch_id', htmlspecialchars($context->getData('initials', $context->getPrimaryLocale()) . '_' . time(), ENT_COMPAT, 'UTF-8')));
		$headNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'timestamp', time()));
		$depositorNode = $doc->createElementNS($deployment->getNamespace(), 'depositor');
		$depositorName = $plugin->getSetting($context->getId(), 'depositorName');
		if (empty($depositorName)) {
			$depositorName = $context->getData('supportName');
		}
		$depositorEmail = $plugin->getSetting($context->getId(), 'depositorEmail');
		if (empty($depositorEmail)) {
			$depositorEmail = $context->getData('supportEmail');
		}
		$depositorNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'depositor_name', htmlspecialchars($depositorName, ENT_COMPAT, 'UTF-8')));
		$depositorNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'email_address', htmlspecialchars($depositorEmail, ENT_COMPAT, 'UTF-8')));
		$headNode->appendChild($depositorNode);
		$publisherServer = $context->getLocalizedName();
		$headNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'registrant', htmlspecialchars($publisherServer, ENT_COMPAT, 'UTF-8')));
		return $headNode;
	}

	/**
	 * Create and return the posted content node 'posted_content'.
	 * @param $doc DOMDocument
	 * @return DOMElement
	 */
	function createPostedContentNode($doc, $publication, $submission) {
		assert(is_a($publication, 'Publication'));
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$request = Application::get()->getRequest();
		$postedContentNode = $doc->createElementNS($deployment->getNamespace(), 'posted_content');
		$postedContentNode->setAttribute('type', 'preprint');

		// contributors
		$contributorsNode = $doc->createElementNS($deployment->getNamespace(), 'contributors');
		$authors = $publication->getData('authors');
		$isFirst = true;
		foreach ($authors as $author) {
			$personNameNode = $doc->createElementNS($deployment->getNamespace(), 'person_name');
			$personNameNode->setAttribute('contributor_role', 'author');
			if ($isFirst) {
				$personNameNode->setAttribute('sequence', 'first');
			} else {
				$personNameNode->setAttribute('sequence', 'additional');
			}
			if (empty($author->getLocalizedFamilyName())) {
				$personNameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'surname', htmlspecialchars(ucfirst($author->getFullName(false)), ENT_COMPAT, 'UTF-8')));
			} else {
				$personNameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'given_name', htmlspecialchars(ucfirst($author->getLocalizedGivenName()), ENT_COMPAT, 'UTF-8')));
				$personNameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'surname', htmlspecialchars(ucfirst($author->getLocalizedFamilyName()), ENT_COMPAT, 'UTF-8')));
			}
			if ($author->getData('orcid')) {
				$personNameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'ORCID', $author->getData('orcid')));
			}
			$contributorsNode->appendChild($personNameNode);
			$isFirst = false;
		}
		$postedContentNode->appendChild($contributorsNode);

		// Titles
		$titlesNode = $doc->createElementNS($deployment->getNamespace(), 'titles');
		$titlesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'title', htmlspecialchars($publication->getData('title', $submission->getLocale()), ENT_COMPAT, 'UTF-8')));
		if ($subtitle = $publication->getData('subtitle', $submission->getLocale())) $titlesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'subtitle', htmlspecialchars($subtitle, ENT_COMPAT, 'UTF-8')));
		$postedContentNode->appendChild($titlesNode);

		// Posted date
		$postedContentNode->appendChild($this->createPostedDateNode($doc, $publication->getData('datePublished')));

		// license
		if ($publication->getData('licenseUrl')) {
			$licenseNode = $doc->createElementNS($deployment->getAINamespace(), 'ai:program');
			$licenseNode->setAttribute('name', 'AccessIndicators');
			$licenseNode->appendChild($node = $doc->createElementNS($deployment->getAINamespace(), 'ai:license_ref', htmlspecialchars($publication->getData('licenseUrl'), ENT_COMPAT, 'UTF-8')));
			$postedContentNode->appendChild($licenseNode);
		}

		// DOI relations
		if ($submission->getLatestPublication()->getStoredPubId('doi') && $submission->getLatestPublication()->getStoredPubId('doi') != $publication->getStoredPubId('doi')){
			$postedContentNode->appendChild($this->createRelationsDataNode($doc, $submission->getLatestPublication()->getStoredPubId('doi')));
		}

		// DOI data
		$postedContentNode->appendChild($this->createDOIDataNode($doc, $publication->getStoredPubId('doi'), $request->url($context->getPath(), 'preprint', 'view', [$submission->getBestId(), 'version', $publication->getId()], null, null, true)));

		return $postedContentNode;
	}

	/**
	 * Create and return the posted date node 'posted_date'.
	 * @param $doc DOMDocument
	 * @param $objectPostedDate string
	 * @return DOMElement
	 */
	function createPostedDateNode($doc, $objectPostedDate) {
		$deployment = $this->getDeployment();
		$postedDate = strtotime($objectPostedDate);
		$postedDateNode = $doc->createElementNS($deployment->getNamespace(), 'posted_date');
		if (date('m', $postedDate)) {
			$postedDateNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'month', date('m', $postedDate)));
		}
		if (date('d', $postedDate)) {
			$postedDateNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'day', date('d', $postedDate)));
		}
		$postedDateNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'year', date('Y', $postedDate)));
		return $postedDateNode;
	}

	/**
	 * Create and return the DOI data node 'doi_data'.
	 * @param $doc DOMDocument
	 * @param $doi string
	 * @param $url string
	 * @return DOMElement
	 */
	function createDOIDataNode($doc, $doi, $url) {
		$deployment = $this->getDeployment();
		$doiDataNode = $doc->createElementNS($deployment->getNamespace(), 'doi_data');
		$doiDataNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'doi', htmlspecialchars($doi, ENT_COMPAT, 'UTF-8')));
		$doiDataNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'resource', $url));
		return $doiDataNode;
	}

	/**
	 * Create and return the 'program' node for DOI relations.
	 * @param $doc DOMDocument
	 * @param $parentDoi string
	 * @return DOMElement
	 */
	function createRelationsDataNode($doc, $parentDoi) {
		$deployment = $this->getDeployment();
		$relationsDataNode = $doc->createElementNS($deployment->getRELNamespace(), 'rel:program');
		$relationsDataNode->setAttribute('name', 'relations');

		$relatedItemNode = $doc->createElementNS($deployment->getRELNamespace(), 'rel:related_item');
		$intraWorkRelationNode = $doc->createElementNS($deployment->getRELNamespace(), 'rel:intra_work_relation',  htmlspecialchars($parentDoi, ENT_COMPAT, 'UTF-8'));
		$intraWorkRelationNode->setAttribute('relationship-type', 'isVersionOf');
		$intraWorkRelationNode->setAttribute('identifier-type', 'doi');
		$relatedItemNode->appendChild($intraWorkRelationNode);
		$relationsDataNode->appendChild($relatedItemNode);

		return $relationsDataNode;
	}

}


