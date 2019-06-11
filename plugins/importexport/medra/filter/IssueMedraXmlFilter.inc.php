<?php

/**
 * @file plugins/importexport/medra/filter/IssueMedraXmlFilter.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueMedraXmlFilter
 * @ingroup plugins_importexport_medra
 *
 * @brief Class that converts an Issue as work or manifestation to a O4DOI XML document.
 */

import('plugins.importexport.medra.filter.O4DOIXmlFilter');


class IssueMedraXmlFilter extends O4DOIXmlFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('mEDRA XML issue export');
		parent::__construct($filterGroup);
	}

	/**
	 * @copydoc O4DOIXmlFilter::isWork()
	 */
	function isWork($context, $plugin) {
		return $plugin->getSetting($context->getId(), 'exportIssuesAs') == O4DOI_ISSUE_AS_WORK;
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.medra.filter.IssueMedraXmlFilter';
	}

	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $pubObjects array Array of Issues
	 * @return DOMDocument
	 */
	function &process(&$pubObjects) {
		// Create the XML document
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$plugin = $deployment->getPlugin();

		// Create the root node
		$rootNodename = $this->isWork($context, $plugin) ? 'ONIXDOISerialIssueWorkRegistrationMessage' : 'ONIXDOISerialIssueVersionRegistrationMessage';
		$rootNode = $this->createRootNode($doc, $rootNodename);
		$doc->appendChild($rootNode);

		// Create and appet the header node and all parts inside it
		$rootNode->appendChild($this->createHeadNode($doc));

		// Create and append the issue nodes
		foreach($pubObjects as $pubObject) {
			$rootNode->appendChild($this->createIssueNode($doc, $pubObject));
		}
		return $doc;
	}

	/**
	 * Create and return an issue node, either as work or as manifestation.
	 * @param $doc DOMDocument
	 * @param $pubObject Issue
	 * @return DOMElement
	 */
	function createIssueNode($doc, $pubObject) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$cache = $deployment->getCache();
		$plugin = $deployment->getPlugin();
		$request = Application::get()->getRequest();
		$router = $request->getRouter();

		$issueNodeName = $this->isWork($context, $plugin) ? 'DOISerialIssueWork' : 'DOISerialIssueVersion';
		$issueNode = $doc->createElementNS($deployment->getNamespace(), $issueNodeName);
		// Notification type (mandatory)
		$doi = $pubObject->getStoredPubId('doi');
		$registeredDoi = $pubObject->getData('medra::registeredDoi');
		assert(empty($registeredDoi) || $registeredDoi == $doi);
		$notificationType = (empty($registeredDoi) ? O4DOI_NOTIFICATION_TYPE_NEW : O4DOI_NOTIFICATION_TYPE_UPDATE);
		$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'NotificationType', $notificationType));
		// DOI (mandatory)
		$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'DOI', htmlspecialchars($doi, ENT_COMPAT, 'UTF-8')));
		// DOI URL (mandatory)
		$url = $router->url($request, $context->getPath(), 'article', 'view', $pubObject->getBestIssueId(), null, null, true);
		if ($plugin->isTestMode($context)) {
			// Change server domain for testing.
			$url = PKPString::regexp_replace('#://[^\s]+/index.php#', '://example.com/index.php', $url);
		}
		$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'DOIWebsiteLink', $url));
		// DOI strucural type
		$structuralType = $this->isWork($context, $plugin) ? 'Abstraction' : 'DigitalFixation';
		$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'DOIStructuralType', $structuralType));
		// Registrant (mandatory)
		$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'RegistrantName', htmlspecialchars($plugin->getSetting($context->getId(), 'registrantName'), ENT_COMPAT, 'UTF-8')));
		// Registration authority (mandatory)
		$issueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'RegistrationAuthority', 'mEDRA'));
		// Work/ProductIdentifier - proprietary ID
		$pubObjectProprietaryId = $context->getId() . '-' . $pubObject->getId();
		$workOrProduct = $this->isWork($context, $plugin) ? 'Work' : 'Product';
		$issueNode->appendChild($this->createIdentifierNode($doc, $workOrProduct, O4DOI_ID_TYPE_PROPRIETARY, $pubObjectProprietaryId));
		// Issue/journal and object locale precedence.
		$journalLocalePrecedence = $objectLocalePrecedence = $this->getObjectLocalePrecedence($context, null, null);
		// Serial Publication (mandatory)
		$issueNode->appendChild($this->createSerialPublicationNode($doc, $journalLocalePrecedence, O4DOI_EPUB_FORMAT_HTML));
		// Journal Issue (mandatory)
		$issueId = $pubObject->getId();
		if (!$cache->isCached('issues', $issueId)) {
			$cache->add($pubObject, null);
		}
		$issueNode->appendChild($this->createJournalIssueNode($doc, $pubObject, $journalLocalePrecedence));
		// Object Description 'OtherText'
		$descriptions = $this->getTranslationsByPrecedence($pubObject->getDescription(null), $objectLocalePrecedence);
		foreach ($descriptions as $locale => $description) {
			$issueNode->appendChild($this->createOtherTextNode($doc, $locale, $description));
		}

		// 4) issue (as-work and as-manifestation):
		// related works:
		// - includes articles-as-work
		$articleDao = DAORegistry::getDAO('PublishedSubmissionDAO'); /* @var $articleDao PublishedSubmissionDAO */
		$articlesByIssue = $articleDao->getPublishedSubmissions($issueId);
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
		$galleysByIssue = array();
		foreach ($articlesByIssue as $relatedArticle) {
			$articleProprietaryId = $context->getId() . '-' . $pubObject->getId() . '-' . $relatedArticle->getId();
			$relatedArticleIds = array(O4DOI_ID_TYPE_PROPRIETARY => $articleProprietaryId);
			$doi = $relatedArticle->getStoredPubId('doi');
			if (!empty($doi)) $relatedArticleIds[O4DOI_ID_TYPE_DOI] = $doi;
			$issueNode->appendChild($this->createRelatedNode($doc, 'Work', O4DOI_RELATION_INCLUDES, $relatedArticleIds));
			// Collect galleys by issue
			$galleysByArticle = $galleyDao->getBySubmissionId($relatedArticle->getId())->toArray();
			$galleysByIssue = array_merge($galleysByIssue, $galleysByArticle);
			unset($relatedArticle, $relatedArticleIds);
		}
		// related products:
		// - includes articles-as-manifestation
		foreach($galleysByIssue as $relatedGalley) {
			$galleyProprietaryId = $context->getId() . '-' . $pubObject->getId() . '-' . $relatedGalley->getSubmissionId() . '-g' . $relatedGalley->getId();
			$relatedGalleyIds = array(O4DOI_ID_TYPE_PROPRIETARY => $galleyProprietaryId);
			$doi = $relatedGalley->getStoredPubId('doi');
			if (!empty($doi)) $relatedGalleyIds[O4DOI_ID_TYPE_DOI] = $doi;
			$issueNode->appendChild($this->createRelatedNode($doc, 'Product', O4DOI_RELATION_INCLUDES, $relatedGalleyIds));
			unset($relatedGalley, $relatedGalleyIds);
		}

		return $issueNode;
	}

	/**
	 * @copydoc O4DOIXmlFilter::createJournalIssueNode()
	 */
	function createJournalIssueNode($doc, $issue, $journalLocalePrecedence) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$plugin = $deployment->getPlugin();

		$journalIssueNode = parent::createJournalIssueNode($doc, $issue, $journalLocalePrecedence);

		// Publication Date
		$datePublished = $issue->getDatePublished();
		if (!empty($datePublished)) {
			$journalIssueNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'PublicationDate', date('Ymd', strtotime($datePublished))));
		}
		// Issue Title (mandatory)
		$localizedTitles = $this->getTranslationsByPrecedence($issue->getTitle(null), $journalLocalePrecedence);
		// Retrieve the first key/value pair...
		foreach($localizedTitles as $locale => $localizedTitle) break;
		if (empty($localizedTitle)) {
			$localizedTitles = $this->getTranslationsByPrecedence($context->getName(null), $journalLocalePrecedence);
			// Retrieve the first key/value pair...
			foreach($localizedTitles as $locale => $localizedTitle) break;
			assert(!empty($localizedTitle));

			// Hack to make sure that no untranslated title appears:
			$showTitle = $issue->getShowTitle();
			$issue->setShowTitle(0);
			$localizedTitle = $localizedTitle . ', ' . $issue->getIssueIdentification();
			$issue->setShowTitle($showTitle);
		}
		$journalIssueNode->appendChild($this->createTitleNode($doc, $locale, $localizedTitle, O4DOI_TITLE_TYPE_ISSUE));

		// Extent (for issues-as-manifestation only)
		if (!$this->isWork($context, $plugin)) {
			$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO'); /* @var $issueGalleyDao IssueGalleyDAO */
			$issueGalleys = $issueGalleyDao->getByIssueId($issue->getId());
			if (!empty($issueGalleys)) {
				foreach($issueGalleys as $issueGalley) {
					$journalIssueNode->appendChild($this->createExtentNode($doc, $issueGalley));
				}
			}
		}

		return $journalIssueNode;
	}

}


