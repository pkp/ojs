<?php

/**
 * @file plugins/importexport/medra/classes/O4DOIExportDom.inc.php
 *
 * Copyright (c) 2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class O4DOIExportDom
 * @ingroup plugins_importexport_medra_classes
 *
 * @brief Onix for DOI (O4DOI) XML export format implementation.
 */


import('lib.pkp.classes.xml.XMLCustomWriter');

define('O4DOI_XMLNS_XSI' , 'http://www.w3.org/2001/XMLSchema-instance');
define('O4DOI_XMLNS' , 'http://www.editeur.org/onix/DOIMetadata/2.0');
define('O4DOI_XSI_SCHEMALOCATION' , 'http://www.medra.org/schema/onix/DOIMetadata/2.0/ONIX_DOIMetadata_2.0.xsd');

// Notification types
define('O4DOI_NOTIFICATION_TYPE_NEW', '06');
define('O4DOI_NOTIFICATION_TYPE_UPDATE', '07');

// ID types
define('O4DOI_ID_TYPE_PROPRIETARY', '01');
define('O4DOI_ID_TYPE_DOI', '06');
define('O4DOI_ID_TYPE_ISSN', '07');

// Text formats
define('O4DOI_TEXTFORMAT_DEFAULT', '06');

// Title types
define('O4DOI_TITLE_TYPE_FULL', '01');

// Publishing roles
define('O4DOI_PUBLISHING_ROLE_PUBLISHER', '01');

// Product forms
define('O4DOI_PRODUCT_FORM_PRINT', 'JB');
define('O4DOI_PRODUCT_FORM_ELECTRONIC', 'JD');

// ePublication formats
define('O4DOI_EPUB_FORMAT_HTML', '01');

// Date formats
define('O4DOI_DATE_FORMAT_YYYY', '06');

// Extent types
define('O4DOI_EXTENT_TYPE_FILESIZE', '22');

// Extent units
define('O4DOI_EXTENT_UNIT_BYTES', '17');

// Contributor roles
define('O4DOI_CONTRIBUTOR_ROLE_ACTUAL_AUTHOR', 'A01');

// Language roles
define('O4DOI_LANGUAGE_ROLE_LANGUAGE_OF_TEXT', '01');

// Subject schemes
define('O4DOI_SUBJECT_SCHEME_PUBLISHER', '23');
define('O4DOI_SUBJECT_SCHEME_PROPRIETARY', '24');

// Text type codes
define('O4DOI_TEXT_TYPE_MAIN_DESCRIPTION', '01');

// Relation codes
define('O4DOI_RELATION_INCLUDES', '80');
define('O4DOI_RELATION_IS_PART_OF', '81');
define('O4DOI_RELATION_IS_A_NEW_VERSION_OF', '82');
define('O4DOI_RELATION_HAS_A_NEW_VERSION', '83');
define('O4DOI_RELATION_IS_A_DIFFERENT_FORM_OF', '84');
define('O4DOI_RELATION_IS_A_LANGUAGE_VERSION_OF', '85');
define('O4DOI_RELATION_IS_MANIFESTED_IN', '89');
define('O4DOI_RELATION_IS_A_MANIFESTATION_OF', '90');


class O4DOIExportDom {

	//
	// Public properties
	//
	/** @var array */
	var $_errors = array();

	/**
	 * Retrieve export error details.
     * @return array
	 */
	function getErrors() {
		return $this->_errors;
	}

	/**
	 * Add an error to the errors list.
	 * @param $errorTranslationKey string An i18n key.
	 * @param $param string|null An additional translation parameter.
	 */
	function _addError($errorTranslationKey, $param = null) {
		$this->_errors[] = array($errorTranslationKey, $param);
	}


	//
	// Private properties
	//
	/** @var XMLNode|DOMImplementation */
	var $_doc;

	/**
	 * Get the XML document.
	 * @return XMLNode|DOMImplementation
	 */
	function &_getDoc() {
		return $this->_doc;
	}

	/** @var integer */
	var $_schema;

	/**
	 * Get the schema that this DOM will generate.
	 * @return string One of the O4DOI_* schema types.
	 */
	function _getSchema() {
		return $this->_schema;
	}

	/** @var array */
	var $_schemaInfo;

	/**
	 *  Internal schema-specific configuration.
	 *  @param $infoType string
	 *  @return array
	 */
	function _getSchemaInfo($infoType) {
		return $this->_schemaInfo[$infoType];
	}

	/**
	 * The OJS object type represented by this DOM
	 * @return string
	 */
	function _getObjectType() {
		return $this->_getSchemaInfo('objectType');
	}

	/**
	 * The DOM's root element.
	 * @return string
	 */
	function _getRootElementName() {
		return $this->_getSchemaInfo('rootElementName');
	}

	/**
	 * The DOM's payload element.
	 * @return string
	 */
	function _getObjectElementName() {
		return $this->_getSchemaInfo('objectElementName');
	}

	/**
	 * Whether the DOM represents an object-as-work.
	 * @return boolean
	 */
	function _isWork() {
		return $this->_getSchemaInfo('isWork');
	}

	/**
	 * Whether the DOM represents a serial article.
	 * @return boolean
	 */
	function _isArticle() {
		return $this->_getSchemaInfo('isArticle');
	}

	/** @var Request */
	var $_request;

	/**
	 * Get the current request.
	 * @return Request
	 */
	function &_getRequest() {
		return $this->_request;
	}

	/** @var Journal */
	var $_journal;

	/**
	 * Get the journal (a.k.a. serial title) of this
	 * O4DOI message.
	 * @return Journal
	 */
	function &_getJournal() {
		return $this->_journal;
	}

	/** @var O4DOIObjectCache A cache for publication objects */
	var $_cache;

	/**
	 * Get the object cache.
	 * @return O4DOIObjectCache
	 */
	function &_getCache() {
		return $this->_cache;
	}

	/** @var string One of the O4DOI_* schema constants */
	var $_exportIssuesAs;

	/**
	 * Whether issues are exported as work.
	 * @return boolean
	 */
	function _exportIssuesAsWork() {
		return $this->_exportIssuesAs == O4DOI_ISSUE_AS_WORK;
	}


	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $request Request
	 * @param $schema string One of the O4DOI_* schema constants.
	 * @param $journal Journal
	 * @param $objectCache O4DOIObjectCache
	 * @param $exportIssuesAs Whether issues are exported as work
	 *  or as manifestation. One of the O4DOI_* schema constants.
	 */
	function O4DOIExportDom(&$request, $schema, &$journal, &$objectCache, $exportIssuesAs) {
		// Configure the DOM.
		$this->_doc =& XMLCustomWriter::createDocument();
		$this->_schema = $schema;
		$this->_schemaInfo = $this->_setSchemaInfo($this->_getSchema());
		$this->_request =& $request;
		$this->_journal =& $journal;
		$this->_cache =& $objectCache;
		$this->_exportIssuesAs = $exportIssuesAs;
	}


	//
	// Public methods
	//
	/**
	 * Generate the O4DOI XML document.
	 *
	 * This method either returns a fully validated O4DOI document
	 * containing all given objects for export or it returns a boolean
	 * 'false' to indicate an error.
	 *
	 * If one or more errors occur then O4DOIExportDom::getErrors()
	 * will return localized error details for display by the client.
	 *
	 * @param $objects array An array of issues, articles or galleys. The
	 *  array must not contain more than one object type.
	 * @param $publicationCountry string This would normally have to be
	 *  a journal property but it isn't. We therefore pass it into this
	 *  method as an external parameter.
	 *
	 * @return XMLNode|DOMImplementation|boolean An XML document or 'false'
	 *  if an error occurred.
	 */
	function &generate(&$objects, $publicationCountry) {
		$falseVar = false;

		// Create the root element.
		$doc =& $this->_getDoc();
		$rootElement =& $this->_rootElement();
		XMLCustomWriter::appendChild($doc, $rootElement);

		// The publisher is needed, both in the header and the payload.
		$journal =& $this->_getJournal();
		$publisher = $journal->getSetting('publisherInstitution');
		if (empty($publisher)) {
			$this->_addError('plugins.importexport.medra.export.error.publisherNotSet');
			return $falseVar;
		}

		// Generate and add the O4DOI header.
		if (!($headerElement =& $this->_headerElement($publisher))) return $falseVar;
		XMLCustomWriter::appendChild($rootElement, $headerElement);

		// Generate and add the O4DOI payload.
		foreach ($objects as $object) {
			if (!($objectElement =& $this->_objectElement($object, $publisher, $publicationCountry))) return $falseVar;
			XMLCustomWriter::appendChild($rootElement, $objectElement);
			unset($object, $objectElement);
		}

		return $doc;
	}


	//
	// Private helper methods
	//
	/**
	 * Return information about the given schema.
	 *
	 * @param $schema string One of the O4DOI_* schema constants.
	 *
	 * @return array An array with schema information.
	 */
	function _setSchemaInfo($schema) {
		static $schemaInfos = array(
			O4DOI_ISSUE_AS_WORK => array(
				'rootElementName' => 'ONIXDOISerialIssueWorkRegistrationMessage',
				'objectElementName' => 'DOISerialIssueWork',
				'objectType' => 'Issue',
				'isWork' => true,
				'isArticle' => false
			),
			O4DOI_ISSUE_AS_MANIFESTATION => array(
				'rootElementName' => 'ONIXDOISerialIssueVersionRegistrationMessage',
				'objectElementName' => 'DOISerialIssueVersion',
				'objectType' => 'Issue',
				'isWork' => false,
				'isArticle' => false
			),
			O4DOI_ARTICLE_AS_WORK => array(
				'rootElementName' => 'ONIXDOISerialArticleWorkRegistrationMessage',
				'objectElementName' => 'DOISerialArticleWork',
				'objectType' => 'PublishedArticle',
				'isWork' => true,
				'isArticle' => true
			),
			O4DOI_ARTICLE_AS_MANIFESTATION => array(
				'rootElementName' => 'ONIXDOISerialArticleVersionRegistrationMessage',
				'objectElementName' => 'DOISerialArticleVersion',
				'objectType' => 'ArticleGalley',
				'isWork' => false,
				'isArticle' => true
			)
		);

		return $schemaInfos[$schema];
	}

	/**
	 * Generate the O4DOI root element depending on the
	 * given schema type.
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_rootElement() {
		// Create the root element and make it the document element of the document.
		$rootElement =& XMLCustomWriter::createElement($this->_getDoc(), $this->_getRootElementName());

		// Add root-level attributes.
		XMLCustomWriter::setAttribute($rootElement, 'xmlns', O4DOI_XMLNS);
		XMLCustomWriter::setAttribute($rootElement, 'xmlns:xsi', O4DOI_XMLNS_XSI);
		XMLCustomWriter::setAttribute($rootElement, 'xsi:schemaLocation', O4DOI_XSI_SCHEMALOCATION);

		return $rootElement;
	}

	/**
	 * Generate the O4DOI header element.
	 *
	 * @param $fromCompany string
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_headerElement($fromCompany) {
		$falseVar = false;
		$journal =& $this->_getJournal();
		$headerElement =& XMLCustomWriter::createElement($this->_getDoc(), 'Header');

		// Publisher (mandatory)
		XMLCustomWriter::createChildWithText($this->_getDoc(), $headerElement, 'FromCompany', $fromCompany);

		// Technical Contact
		$technicalContact = $journal->getSetting('supportName');
		if (!empty($technicalContact)) {
			$technicalContactPhone = $journal->getSetting('supportPhone');
			if (!empty($technicalContactPhone)) {
				$technicalContact .= ', ' . $technicalContactPhone;
			}
			XMLCustomWriter::createChildWithText($this->_getDoc(), $headerElement, 'FromPerson', $technicalContact);
		}

		// Technical Contact Email (mandatory)
		$technicalContactEmail = $journal->getSetting('supportEmail');
		if (empty($technicalContactEmail)) {
			$this->_addError('plugins.importexport.medra.export.error.supportEmailNotSet');
			return $falseVar;
		}
		XMLCustomWriter::createChildWithText($this->_getDoc(), $headerElement, 'FromEmail', $technicalContactEmail);

		// Addressee
		XMLCustomWriter::createChildWithText($this->_getDoc(), $headerElement, 'ToCompany', 'mEDRA');

		// Timestamp
		XMLCustomWriter::createChildWithText($this->_getDoc(), $headerElement, 'SentDate', date('YmdHi'));

		// Message note
		$app =& PKPApplication::getApplication();
		$name = $app->getName();
		$version = $app->getCurrentVersion();
		$versionString = $version->getVersionString();
		XMLCustomWriter::createChildWithText($this->_getDoc(), $headerElement, 'MessageNote', "This dataset was exported with $name, version $versionString.");

		return $headerElement;
	}

	/**
	 * Generate O4DOI object payload.
	 *
	 * @param $object Issue|PublishedArticle|ArticleGalley
	 * @param $publisher string
	 * @param $publicationCountry string
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_objectElement(&$object, $publisher, $publicationCountry) {
		$falseVar = false;
		$journal =& $this->_getJournal();

		// Declare variables that will contain publication objects.
		$issue = null; /* @var $issue Issue */
		$article = null; /* @var $article PublishedArticle */
		$galley = null; /* @var $galley ArticleGalley */
		$articlesByIssue = null;
		$galleysByArticle = null;
		$galleysByIssue = null;

		// Retrieve required publication objects (depends on the schema of this DOM).
		$pubObjects =& $this->_retrievePublicationObjects($object);
		extract($pubObjects);

		// Main object element.
		$objectElement =& XMLCustomWriter::createElement($this->_getDoc(), $this->_getObjectElementName());

		// Notification type (mandatory, 06 - new record)
		XMLCustomWriter::createChildWithText($this->_getDoc(), $objectElement, 'NotificationType', O4DOI_NOTIFICATION_TYPE_NEW);

		// DOI (mandatory)
		$doi = $object->getPubId('doi');
		if (empty($doi)) {
			$this->_addError('plugins.importexport.medra.export.error.noDoiAssigned', $object->getId());
			return $falseVar;
		}
		XMLCustomWriter::createChildWithText($this->_getDoc(), $objectElement, 'DOI', $doi);

		// DOI URL (mandatory)
		$request =& $this->_getRequest();
		$router =& $request->getRouter();
		switch ($this->_getSchema()) {
			case O4DOI_ISSUE_AS_WORK:
			case O4DOI_ISSUE_AS_MANIFESTATION:
				$url = $router->url($request, null, 'issue', 'view', $issue->getBestIssueId($journal));
				break;

			case O4DOI_ARTICLE_AS_WORK:
				$url = $router->url($request, null, 'article', 'view', $article->getBestArticleId($journal));
				break;

			case O4DOI_ARTICLE_AS_MANIFESTATION:
				$url = $router->url($request, null, 'article', 'view', array($article->getBestArticleId($journal), $galley->getBestGalleyId($journal)));
				break;
		}
		assert(!empty($url));
		XMLCustomWriter::createChildWithText($this->_getDoc(), $objectElement, 'DOIWebsiteLink', $url);

		// DOI strucural type
		if ($this->_isWork()) {
			XMLCustomWriter::createChildWithText($this->_getDoc(), $objectElement, 'DOIStructuralType', 'Abstraction');
		} else {
			XMLCustomWriter::createChildWithText($this->_getDoc(), $objectElement, 'DOIStructuralType', 'Digital Fixation');
		}

		// Registrant (mandatory)
		XMLCustomWriter::createChildWithText($this->_getDoc(), $objectElement, 'RegistrantName', $publisher);

		// Registration authority (mandatory)
		XMLCustomWriter::createChildWithText($this->_getDoc(), $objectElement, 'RegistrationAuthority', 'mEDRA');

		// Internal ID
		$internalId = '';
		switch ($this->_getSchema()) {
			case O4DOI_ARTICLE_AS_MANIFESTATION:
				$internalId = '-' . $galley->getId();

			case O4DOI_ARTICLE_AS_WORK:
				$internalId = '-' . $article->getId() . $internalId;

			case O4DOI_ISSUE_AS_WORK:
			case O4DOI_ISSUE_AS_MANIFESTATION:
				$internalId = '-' . $issue->getId() . $internalId;
		}
		$internalId = $journal->getId() . $internalId;
		XMLCustomWriter::appendChild($objectElement, $this->_idElement($this->_isWork()?'Work':'Product', O4DOI_ID_TYPE_PROPRIETARY, $internalId));

		// Serial Publication (mandatory)
		XMLCustomWriter::appendChild($objectElement, $this->_serialPublicationElement($issue, $publisher, $publicationCountry));

		// Journal Issue (mandatory)
		XMLCustomWriter::appendChild($objectElement, $this->_journalIssueElement($issue));

		if ($this->_isArticle()) {
			assert(!empty($article));

			// Content Item (mandatory for articles)
			$contentItemElement =& $this->_contentItemElement($article, $galley);
			XMLCustomWriter::appendChild($objectElement, $contentItemElement);

			// For articles, final elements go into the ContentItem element.
			$finalElemementsContainer =& $contentItemElement;
		} else {
			// For issues, final elements go directly into the message payload element.
			$finalElemementsContainer =& $objectElement;
		}

		// Object Description
		foreach ($this->_getExportLanguages($journal) as $locale => $localeName) {
			if ($this->_isArticle()) {
				$description = $article->getAbstract($locale);
			} else {
				$description = $issue->getDescription($locale);
			}
			if (!empty($description)) {
				XMLCustomWriter::appendChild($finalElemementsContainer, $this->_otherTextElement($locale, $description));
			}
		}

		// Relations
		if ($this->_isArticle()) {
			// 1) article (as-work and as-manifestation):
			if ($this->_exportIssuesAsWork()) {
				// related work:
				// - is part of issue-as-work
				$issueWorkOrProduct = 'Work';
			} else {
				// related product:
				// - is part of issue-as-manifestation
				$issueWorkOrProduct = 'Product';
			}
			$relatedIssueIds = array(O4DOI_ID_TYPE_PROPRIETARY => $journal->getId() . '-' . $issue->getId());
			$doi = $issue->getPubId('doi');
			if (!empty($doi)) $relatedIssueIds[O4DOI_ID_TYPE_DOI] = $doi;
			$relatedIssueElement =& $this->_relationElement($issueWorkOrProduct, O4DOI_RELATION_IS_PART_OF, $relatedIssueIds);

			// 2) article-as-work:
			if ($this->_isWork()) {
				XMLCustomWriter::appendChild($finalElemementsContainer, $relatedIssueElement);

				// related products:
				// - is manifested in articles-as-manifestation
				foreach($galleysByArticle as $relatedGalley) {
					$relatedGalleyIds = array(
						O4DOI_ID_TYPE_PROPRIETARY => $journal->getId() . '-' . $issue->getId() . '-' . $article->getId() . '-' . $relatedGalley->getId()
					);
					$doi = $relatedGalley->getPubId('doi');
					if (!empty($doi)) $relatedGalleyIds[O4DOI_ID_TYPE_DOI] = $doi;
					$relatedArticleElement =& $this->_relationElement('Product', O4DOI_RELATION_IS_MANIFESTED_IN, $relatedGalleyIds);
					XMLCustomWriter::appendChild($finalElemementsContainer, $relatedArticleElement);
					unset($relatedGalley, $relatedGalleyIds, $relatedArticleElement);
				}

			// 3) article-as-manifestation:
			} else {
				// Include issue-as-work before article-as-work.
				if ($issueWorkOrProduct == 'Work') XMLCustomWriter::appendChild($finalElemementsContainer, $relatedIssueElement);

				// related work:
				// - is a manifestation of article-as-work
				$relatedArticleIds = array(O4DOI_ID_TYPE_PROPRIETARY => $journal->getId() . '-' . $issue->getId() . '-' . $article->getId());
				$doi = $article->getPubId('doi');
				if (!empty($doi)) $relatedArticleIds[O4DOI_ID_TYPE_DOI] = $doi;
				$relatedArticleElement =& $this->_relationElement('Work', O4DOI_RELATION_IS_A_MANIFESTATION_OF, $relatedArticleIds);
				XMLCustomWriter::appendChild($finalElemementsContainer, $relatedArticleElement);
				unset($relatedArticleIds, $relatedArticleElement);

				// Include issue-as-manifestation after article-as-work.
				if ($issueWorkOrProduct == 'Product') XMLCustomWriter::appendChild($finalElemementsContainer, $relatedIssueElement);

				// related products:
				foreach($galleysByArticle as $relatedGalley) {
					$relatedGalleyIds = array(
						O4DOI_ID_TYPE_PROPRIETARY => $journal->getId() . '-' . $issue->getId() . '-' . $article->getId() . '-' . $relatedGalley->getId()
					);
					$doi = $relatedGalley->getPubId('doi');
					if (!empty($doi)) $relatedGalleyIds[O4DOI_ID_TYPE_DOI] = $doi;

					// - is a different form of all other articles-as-manifestation
					//   with the same article id and language but different form
					if ($galley->getLocale() == $relatedGalley->getLocale() &&
							$galley->getLabel() != $relatedGalley->getLabel()) {

						$relatedArticleElement =& $this->_relationElement('Product', O4DOI_RELATION_IS_A_DIFFERENT_FORM_OF, $relatedGalleyIds);
						XMLCustomWriter::appendChild($finalElemementsContainer, $relatedArticleElement);
						unset($relatedArticleElement);
					}

					// - is a different language version of all other articles-as-manifestation
					//   with the same article id and form but different language
					if ($galley->getLabel() == $relatedGalley->getLabel() &&
							$galley->getLocale() != $relatedGalley->getLocale()) {

						$relatedArticleElement =& $this->_relationElement('Product', O4DOI_RELATION_IS_A_LANGUAGE_VERSION_OF, $relatedGalleyIds);
						XMLCustomWriter::appendChild($finalElemementsContainer, $relatedArticleElement);
						unset($relatedArticleElement);
					}

					unset($relatedGalley, $relatedGalleyIds);
				}
			}
			unset($relatedIssueIds, $relatedIssueElement);
		} else {
			// 4) issue (as-work and as-manifestation):
			// related works:
			// - includes articles-as-work
			foreach ($articlesByIssue as $relatedArticle) {
				$relatedArticleIds = array(O4DOI_ID_TYPE_PROPRIETARY => $journal->getId() . '-' . $issue->getId() . '-' . $relatedArticle->getId());
				$doi = $relatedArticle->getPubId('doi');
				if (!empty($doi)) $relatedArticleIds[O4DOI_ID_TYPE_DOI] = $doi;
				$relatedArticleElement =& $this->_relationElement('Work', O4DOI_RELATION_INCLUDES, $relatedArticleIds);
				XMLCustomWriter::appendChild($finalElemementsContainer, $relatedArticleElement);
				unset($relatedArticle, $relatedArticleIds, $relatedArticleElement);
			}

			// related products:
			// - includes articles-as-manifestation
			foreach($galleysByIssue as $relatedGalley) {
				$relatedGalleyIds = array(
					O4DOI_ID_TYPE_PROPRIETARY => $journal->getId() . '-' . $issue->getId() . '-' . $relatedGalley->getArticleId() . '-' . $relatedGalley->getId()
				);
				$doi = $relatedGalley->getPubId('doi');
				if (!empty($doi)) $relatedGalleyIds[O4DOI_ID_TYPE_DOI] = $doi;
				$relatedArticleElement =& $this->_relationElement('Product', O4DOI_RELATION_INCLUDES, $relatedGalleyIds);
				XMLCustomWriter::appendChild($finalElemementsContainer, $relatedArticleElement);
				unset($relatedGalley, $relatedGalleyIds, $relatedArticleElement);
			}
		}

		return $objectElement;
	}

	/**
	 * Retrieve all the OJS publication objects containing the
	 * data required to generate the given O4DOI schema.
	 *
	 * @param $object Issue|PublishedArticle|ArticleGalley The object to export.
	 *
	 * @return array An array with the required OJS objects.
	 */
	function &_retrievePublicationObjects(&$object) {
		$nullVar = null;
 		$journal =& $this->_getJournal();
 		$cache =& $this->_getCache();

		// Make sure that the schema and the object combine.
		assert(is_a($object, $this->_getObjectType()));

		// Retrieve additional OJS objects required for the given schema.
		$publicationObjects = array();
		switch ($this->_getSchema()) {
			case O4DOI_ISSUE_AS_WORK:
			case O4DOI_ISSUE_AS_MANIFESTATION:
				$cache->add($object, $nullVar);
				$publicationObjects['issue'] =& $object;
				break;

			case O4DOI_ARTICLE_AS_WORK:
				$cache->add($object, $nullVar);
				$publicationObjects['article'] =& $object;
				break;

			case O4DOI_ARTICLE_AS_MANIFESTATION:
				$publicationObjects['galley'] =& $object;
				$articleId = $object->getArticleId();
				if ($cache->isCached('articles', $articleId)) {
					$article =& $cache->get('articles', $articleId);
				} else {
					$articleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
					$article =& $articleDao->getPublishedArticleByArticleId($articleId, $journal->getId());
					if ($article) $cache->add($article, $nullVar);
				}
				assert(is_a($article, 'PublishedArticle'));
				$cache->add($object, $article);
				$publicationObjects['article'] =& $article;
				break;
		}

		// Retrieve the issue if it's not yet there.
		if (!isset($publicationObjects['issue'])) {
			assert(isset($publicationObjects['article']));
			$issueId = $publicationObjects['article']->getIssueId();
			if ($cache->isCached('issues', $issueId)) {
				$issue =& $cache->get('issues', $issueId);
			} else {
				$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
				$issue =& $issueDao->getIssueById($issueId, $journal->getId());
				if ($issue) $cache->add($issue, $nullVar);
			}
			assert(is_a($issue, 'Issue'));
			$publicationObjects['issue'] =& $issue;
		}

		// For articles: Retrieve all galleys of the article:
		if ($this->_isArticle()) {
			assert(isset($publicationObjects['article']));
			$articleId = $publicationObjects['article']->getId();
			if (!$cache->isCached('galleysByArticle', $articleId)) {
				$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
				$galleys =& $galleyDao->getGalleysByArticle($articleId);
				foreach($galleys as $galley) {
					$cache->add($galley, $publicationObjects['article']);
					unset($galley);
				}
				$cache->markComplete('galleysByArticle', $articleId);
			}
			$publicationObjects['galleysByArticle'] =& $cache->get('galleysByArticle', $articleId);

		// For issues: Retrieve all articles and galleys of the issue:
		} else {
			// Articles by issue
			$issueId = $publicationObjects['issue']->getId();
			if (!$cache->isCached('articlesByIssue', $issueId)) {
				$articleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
				$articles =& $articleDao->getPublishedArticles($issueId);
				foreach ($articles as $article) {
					$cache->add($article, $nullVar);
					unset($article);
				}
				$cache->markComplete('articlesByIssue', $issueId);
			}
			$publicationObjects['articlesByIssue'] =& $cache->get('articlesByIssue', $issueId);

			// Galleys by issue
			if (!$cache->isCached('galleysByIssue', $issueId)) {
				$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
				foreach($publicationObjects['articlesByIssue'] as $article) {
					$articleId = $article->getId();
					if (!$cache->isCached('galleysByArticle', $articleId)) {
						$galleys =& $galleyDao->getGalleysByArticle($articleId);
						foreach($galleys as $galley) {
							$cache->add($galley, $article);
							unset($galley);
						}
						$cache->markComplete('galleysByArticle', $articleId);
					}
					unset($article);
				}
				$cache->markComplete('galleysByIssue', $issueId);
			}
			$publicationObjects['galleysByIssue'] =& $cache->get('galleysByIssue', $issueId);
		}

		return $publicationObjects;
	}

	/**
	 * Create a work or product id element.
	 *
	 * @param $workOrProduct string "Work" or "Product"
	 * @param $idType string One of the O4DOI_ID_TYPE_* constants
	 * @param $id string The ID.
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_idElement($workOrProduct, $idType, $id) {
		$idElement =& XMLCustomWriter::createElement($this->_getDoc(), "${workOrProduct}Identifier");

		// ID type (mandatory)
		XMLCustomWriter::createChildWithText($this->_getDoc(), $idElement, "${workOrProduct}IDType", $idType);

		// ID (mandatory)
		XMLCustomWriter::createChildWithText($this->_getDoc(), $idElement, 'IDValue', $id);

		return $idElement;
	}

	/**
	 * Generate O4DOI serial publication.
	 *
	 * @param $issue Issue
	 * @param $publisher string
	 * @param $publicationCountry string
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_serialPublicationElement(&$issue, $publisher, $publicationCountry) {
		$journal =& $this->_getJournal();
		$serialElement =& XMLCustomWriter::createElement($this->_getDoc(), 'SerialPublication');

		// Serial Work (mandatory)
		XMLCustomWriter::appendChild($serialElement, $this->_serialWorkElement($publisher, $publicationCountry));

		// Electronic Serial Version
		$onlineIssn = $journal->getSetting('onlineIssn');
		XMLCustomWriter::appendChild($serialElement, $this->_serialVersionElement($onlineIssn, O4DOI_PRODUCT_FORM_ELECTRONIC));

		// Print Serial Version
		if ($printIssn = $journal->getSetting('printIssn')) {
			XMLCustomWriter::appendChild($serialElement, $this->_serialVersionElement($printIssn, O4DOI_PRODUCT_FORM_PRINT));
		}

		return $serialElement;
	}

	/**
	 * Generate O4DOI serial work.
	 *
	 * @param $publisher string
	 * @param $publicationCountry string
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_serialWorkElement($publisher, $publicationCountry) {
		$journal =& $this->_getJournal();
		$serialWorkElement =& XMLCustomWriter::createElement($this->_getDoc(), 'SerialWork');

		// Title (mandatory)
		$foundATitle = false;
		foreach ($this->_getExportLanguages($journal) as $locale => $localeName) {
			$localizedTitle = $journal->getTitle($locale);
			if (!empty($localizedTitle)) {
				XMLCustomWriter::appendChild($serialWorkElement, $this->_titleElement($locale, $localizedTitle));
				$foundATitle = true;
			}
		}
		assert($foundATitle);

		// Publisher
		XMLCustomWriter::appendChild($serialWorkElement, $this->_publisherElement($publisher));

		// Country of Publication (mandatory)
		XMLCustomWriter::createChildWithText($this->_getDoc(), $serialWorkElement, 'CountryOfPublication', $publicationCountry);

		return $serialWorkElement;
	}

	/**
	 * Create a work or product id element.
	 *
	 * @param $locale string e.g. 'en_US'
	 * @param $localizedTitle string
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_titleElement($locale, $localizedTitle) {
		$titleElement =& XMLCustomWriter::createElement($this->_getDoc(), 'Title');

		// Text format
		XMLCustomWriter::setAttribute($titleElement, 'textformat', O4DOI_TEXTFORMAT_DEFAULT);

		// Language
		$language = AppLocale::get3LetterIsoFromLocale($locale);
		assert(!empty($language));
		XMLCustomWriter::setAttribute($titleElement, 'language', $language);

		// Title type (mandatory)
		XMLCustomWriter::createChildWithText($this->_getDoc(), $titleElement, 'TitleType', O4DOI_TITLE_TYPE_FULL);

		// Title text (mandatory)
		XMLCustomWriter::createChildWithText($this->_getDoc(), $titleElement, 'TitleText', $localizedTitle);

		return $titleElement;
	}

	/**
	 * Create a publisher element.
	 *
	 * @param $publisher string
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_publisherElement($publisher) {
		$publisherElement =& XMLCustomWriter::createElement($this->_getDoc(), 'Publisher');

		// Publishing role (mandatory)
		XMLCustomWriter::createChildWithText($this->_getDoc(), $publisherElement, 'PublishingRole', O4DOI_PUBLISHING_ROLE_PUBLISHER);

		// Publisher name (mandatory)
		XMLCustomWriter::createChildWithText($this->_getDoc(), $publisherElement, 'PublisherName', $publisher);

		return $publisherElement;
	}

	/**
	 * Create a serial version element.
	 *
	 * @param $issn string
	 * @param $productForm One of the O4DOI_PRODUCT_FORM_* constants
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_serialVersionElement($issn, $productForm) {
		$journal =& $this->_getJournal();
		$serialVersionElement =& XMLCustomWriter::createElement($this->_getDoc(), 'SerialVersion');

		// Proprietary Journal Identifier
		if ($productForm == O4DOI_PRODUCT_FORM_ELECTRONIC) {
			XMLCustomWriter::appendChild($serialVersionElement, $this->_idElement('Product', O4DOI_ID_TYPE_PROPRIETARY, $journal->getId()));
		}

		// ISSN
		if (!empty($issn)) {
			$issn = String::regexp_replace('/[^0-9]/', '', $issn);
			XMLCustomWriter::appendChild($serialVersionElement, $this->_idElement('Product', O4DOI_ID_TYPE_ISSN, $issn));
		}

		// Product Form
		XMLCustomWriter::createChildWithText($this->_getDoc(), $serialVersionElement, 'ProductForm', $productForm);

		if ($productForm == O4DOI_PRODUCT_FORM_ELECTRONIC) {
			// ePublication Format
			XMLCustomWriter::createChildWithText($this->_getDoc(), $serialVersionElement, 'EpubFormat', O4DOI_EPUB_FORMAT_HTML);

			// ePublication Format Description
			XMLCustomWriter::createChildWithText($this->_getDoc(), $serialVersionElement, 'EpubFormatDescription', 'Open Journal Systems (OJS)');
		}

		return $serialVersionElement;
	}

	/**
	 * Create the journal issue element.
	 *
	 * @param $issue Issue
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_journalIssueElement(&$issue) {
		$journalIssueElement =& XMLCustomWriter::createElement($this->_getDoc(), 'JournalIssue');

		// Volume
		$volume = $issue->getVolume();
		if (!empty($volume)) {
			XMLCustomWriter::createChildWithText($this->_getDoc(), $journalIssueElement, 'JournalVolumeNumber', $volume);
		}

		// Number
		$number = $issue->getNumber();
		if (!empty($number)) {
			XMLCustomWriter::createChildWithText($this->_getDoc(), $journalIssueElement, 'JournalIssueNumber', $number);
		}

		// Identification
		$identification = $issue->getIssueIdentification();
		if (!empty($identification)) {
			XMLCustomWriter::createChildWithText($this->_getDoc(), $journalIssueElement, 'JournalIssueDesignation', $identification);
		}

		assert(!(empty($number) && empty($identification)));

		// Nominal Year
		$year = (string)$issue->getYear();
		$yearlen = strlen($year);
		if (!empty($year) && ($yearlen == 2 || $yearlen == 4)) {
			$issueDate =& XMLCustomWriter::createElement($this->_getDoc(), 'JournalIssueDate');
			XMLCustomWriter::createChildWithText($this->_getDoc(), $issueDate, 'DateFormat', O4DOI_DATE_FORMAT_YYYY);

			// Try to extend the year if necessary.
			if ($yearlen == 2) {
				// Assume that the issue date will never be
				// more than one year in the future.
				if ((int)$year <= (int)date('y')+1) {
					$year = '20' . $year;
				} else {
					$year = '19' . $year;
				}
			}
			XMLCustomWriter::createChildWithText($this->_getDoc(), $issueDate, 'Date', $year);
			XMLCustomWriter::appendChild($journalIssueElement, $issueDate);
		}

		// Extent (for issues-as-manifestation only)
		// FIXME: Uncomment this in dev.
		// if (!$this->_isWork()) {
		//	$issueGalleyDao =& DAORegistry::getDAO('IssueGalleyDAO'); /* @var $issueGalleyDao IssueGalleyDAO */
		//	$issueGalleys =& $issueGalleyDao->getGalleysByIssue($issue->getId());
		//	if (!empty($issueGalleys)) {
		//		foreach($issueGalleys as $issueGalley) {
		//			XMLCustomWriter::appendChild($journalIssueElement, $this->_extentElement($issueGalley));
		//		}
		//	}
		// }

		if ($this->_getObjectType() == 'Issue') {
			// Publication Date
			$datePublished = $issue->getDatePublished();
			if (!empty($datePublished)) {
				XMLCustomWriter::appendChild($journalIssueElement, $this->_publicationDateElement($datePublished));
			}

			// Issue Title (mandatory)
			$journal =& $this->_getJournal();
			$foundATitle = false;
			foreach ($this->_getExportLanguages($journal) as $locale => $localeName) {
				$localizedTitle = $issue->getTitle($locale);
				if (empty($localizedTitle)) {
					$localizedTitle = $journal->getTitle($locale);
					if (empty($localizedTitle)) {
						continue;
					}
					// Hack to make sure that no untranslated title appears:
					$showTitle = $issue->getShowTitle();
					$issue->setShowTitle(0);
					$localizedTitle = $localizedTitle . ', ' . $issue->getIssueIdentification();
					$issue->setShowTitle($showTitle);
				}
				$foundATitle = true;
				XMLCustomWriter::appendChild($journalIssueElement, $this->_titleElement($locale, $localizedTitle));
			}
			assert($foundATitle);
		}

		return $journalIssueElement;
	}

	/**
	 * Create an extent element.
	 *
	 * @param $file ArticleFile
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_extentElement(&$file) {
		$extentElement =& XMLCustomWriter::createElement($this->_getDoc(), 'Extent');

		// Extent type
		XMLCustomWriter::createChildWithText($this->_getDoc(), $extentElement, 'ExtentType', O4DOI_EXTENT_TYPE_FILESIZE);

		// Extent value
		XMLCustomWriter::createChildWithText($this->_getDoc(), $extentElement, 'ExtentValue', $file->getFileSize());

		// Extent unit
		XMLCustomWriter::createChildWithText($this->_getDoc(), $extentElement, 'ExtentUnit', O4DOI_EXTENT_UNIT_BYTES);

		return $extentElement;
	}

	/**
	 * Create a publication date element.
	 *
	 * @param $datePublished string The publication timestamp.
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_publicationDateElement($datePublished) {
		$publicationDateElement =& XMLCustomWriter::createElement($this->_getDoc(), 'PublicationDate');
		$textNode =& XMLCustomWriter::createTextNode($this->_getDoc(), date('Ymd', strtotime($datePublished)));
		XMLCustomWriter::appendChild($publicationDateElement, $textNode);
		return $publicationDateElement;
	}

	/**
	 * Create a content item element.
	 *
	 * @param $article PublishedArticle
	 * @param $galley ArticleGalley|null This will only be set in case we're
	 *  transmitting an article-as-manifestation.
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_contentItemElement(&$article, &$galley) {
		$contentItemElement =& XMLCustomWriter::createElement($this->_getDoc(), 'ContentItem');

		// Sequence number
		$seq = $article->getSeq();
		assert(!empty($seq));
		XMLCustomWriter::createChildWithText($this->_getDoc(), $contentItemElement, 'SequenceNumber', $seq);

		// Number of pages
		$pages = $article->getPages();
		if (!empty($pages)) {
			$textItemElement =& XMLCustomWriter::createElement($this->_getDoc(), 'TextItem');
			XMLCustomWriter::createChildWithText($this->_getDoc(), $textItemElement, 'NumberOfPages', $pages);
			XMLCustomWriter::appendChild($contentItemElement, $textItemElement);
		}

		// Extent (for article-as-manifestation only)
		if (is_a($galley, 'ArticleGalley')) {
			XMLCustomWriter::appendChild($contentItemElement, $this->_extentElement($galley));
		}

		// Publication Date
		$datePublished = $article->getDatePublished();
		if (!empty($datePublished)) {
			XMLCustomWriter::appendChild($contentItemElement, $this->_publicationDateElement($datePublished));
		}

		// Primary locale of the object.
		$primaryObjectLocale = null;
		if (is_a($galley, 'ArticleGalley')) {
			$primaryObjectLocale = $galley->getLocale();
		}
		if (empty($primaryObjectLocale) && is_a($article, 'PublishedArticle')) {
			$primaryObjectLocale = $article->getLocale();
		}
		if (empty($primaryObjectLocale)) {
			$primaryObjectLocale = $journal->getPrimaryLocale();
		}
		assert(AppLocale::isLocaleValid($primaryObjectLocale));
		$languageDao =& DAORegistry::getDAO('LanguageDAO'); /* @var $languageDao LanguageDAO */

		// Article Title (mandatory)
		$foundATitle = false;
		$journal =& $this->_getJournal();
		foreach ($this->_getExportLanguages($journal) as $locale => $localeName) {
			$localizedTitle = $article->getTitle($locale);
			if (!empty($localizedTitle)) {
				if (!$this->_isWork()) {
					$language =& $languageDao->getLanguageByCode(substr($primaryObjectLocale, 0, 2), $locale);
					$languageName = $language->getName();
					$galleyForm = $galley->getLabel();
					if (!empty($galleyForm)) {
						$localizedTitle .= " ($galleyForm, $languageName)";
					} else {
						$localizedTitle .= " ($languageName)";
					}
				}
				$foundATitle = true;
				XMLCustomWriter::appendChild($contentItemElement, $this->_titleElement($locale, $localizedTitle));
			}
		}
		assert($foundATitle);

		// Contributors
		$authors =& $article->getAuthors();
		assert(!empty($authors));
		foreach ($authors as $author) {
			XMLCustomWriter::appendChild($contentItemElement, $this->_contributorElement($author, $primaryObjectLocale));
		}

		// Language
		$languageCode = AppLocale::get3LetterIsoFromLocale($primaryObjectLocale);
		assert(!empty($languageCode));
		$languageElement = XMLCustomWriter::createElement($this->_getDoc(), 'Language');
		XMLCustomWriter::createChildWithText($this->_getDoc(), $languageElement, 'LanguageRole', O4DOI_LANGUAGE_ROLE_LANGUAGE_OF_TEXT);
		XMLCustomWriter::createChildWithText($this->_getDoc(), $languageElement, 'LanguageCode', $languageCode);
		XMLCustomWriter::appendChild($contentItemElement, $languageElement);

		// Article keywords
		$keywords = $article->getSubject($primaryObjectLocale);
		if (empty($keywords)) {
			$keywords = $article->getLocalizedSubject();
		}
		if (!empty($keywords)) {
			XMLCustomWriter::appendChild($contentItemElement, $this->_subjectElement(O4DOI_SUBJECT_SCHEME_PUBLISHER, $keywords));
		}

		// Subject class
		$subjectSchemeTitle = $journal->getSetting('metaSubjectClassTitle', $primaryObjectLocale);
		if (empty($subjectSchemeTitle)) {
			$subjectSchemeTitle = $journal->getLocalizedSetting('metaSubjectClassTitle');
		}
		$subjectSchemeUrl = $journal->getSetting('metaSubjectClassUrl', $primaryObjectLocale);
		if ($subjectSchemeUrl == 'http://') $subjectSchemeUrl = '';
		if (empty($subjectSchemeUrl)) {
			$subjectSchemeUrl = $journal->getLocalizedSetting('metaSubjectClassUrl');
		}
		if (empty($subjectSchemeTitle)) {
			$subjectSchemeName = $subjectSchemeUrl;
		} else {
			if (empty($subjectSchemeUrl)) {
				$subjectSchemeName = $subjectSchemeTitle;
			} else {
				$subjectSchemeName = "$subjectSchemeTitle ($subjectSchemeUrl)";
			}
		}
		$subjectCode = $article->getSubjectClass($primaryObjectLocale);
		if (empty($subjectCode)) {
			$subjectCode = $article->getLocalizedSubjectClass();
		}
		if (!(empty($subjectSchemeName) || empty($subjectCode))) {
			XMLCustomWriter::appendChild($contentItemElement, $this->_subjectElement(O4DOI_SUBJECT_SCHEME_PROPRIETARY, $subjectCode, $subjectSchemeName));
		}

		return $contentItemElement;
	}

	/**
	 * Create a content item element.
	 *
	 * @param $author Author
	 * @param $primaryObjectLocale string
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_contributorElement(&$author, $primaryObjectLocale) {
		$contributorElement =& XMLCustomWriter::createElement($this->_getDoc(), 'Contributor');

		// Sequence number
		$seq = $author->getSequence();
		assert(!empty($seq));
		XMLCustomWriter::createChildWithText($this->_getDoc(), $contributorElement, 'SequenceNumber', $seq);

		// Contributor role (mandatory)
		XMLCustomWriter::createChildWithText($this->_getDoc(), $contributorElement, 'ContributorRole', O4DOI_CONTRIBUTOR_ROLE_ACTUAL_AUTHOR);

		// Person name (mandatory)
		$personName = $author->getFullName();
		assert(!empty($personName));
		XMLCustomWriter::createChildWithText($this->_getDoc(), $contributorElement, 'PersonName', $personName);

		// Inverted person name
		$invertedPersonName = $author->getFullName(true);
		assert(!empty($invertedPersonName));
		XMLCustomWriter::createChildWithText($this->_getDoc(), $contributorElement, 'PersonNameInverted', $invertedPersonName);

		// Affiliation
		$affiliation = $author->getAffiliation($primaryObjectLocale);
		if (empty($affiliation)) {
			$affiliation = $author->getLocalizedAffiliation();
		}
		if (!empty($affiliation)) {
			$affiliationElement = XMLCustomWriter::createElement($this->_getDoc(), 'ProfessionalAffiliation');
			XMLCustomWriter::createChildWithText($this->_getDoc(), $affiliationElement, 'Affiliation', $affiliation);
			XMLCustomWriter::appendChild($contributorElement, $affiliationElement);
		}

		// Biographical note
		$bioNote = $author->getBiography($primaryObjectLocale);
		if (empty($bioNote)) {
			$bioNote = $author->getLocalizedBiography();
		}
		if (!empty($bioNote)) {
			XMLCustomWriter::createChildWithText($this->_getDoc(), $contributorElement, 'BiographicalNote', $bioNote);
		}

		return $contributorElement;
	}

	/**
	 * Create a subject element.
	 *
	 * @param $subjectSchemeId string One of the O4DOI_SUBJECT_SCHEME_* constants.
	 * @param $subjectHeadingOrCode string The subject.
	 * @param $subjectSchemeName string|null A subject scheme name.
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_subjectElement($subjectSchemeId, $subjectHeadingOrCode, $subjectSchemeName = null) {
		$subjectElement =& XMLCustomWriter::createElement($this->_getDoc(), 'Subject');

		// Subject Scheme Identifier
		XMLCustomWriter::createChildWithText($this->_getDoc(), $subjectElement, 'SubjectSchemeIdentifier', $subjectSchemeId);

		if (is_null($subjectSchemeName)) {
			// Subject Heading
			XMLCustomWriter::createChildWithText($this->_getDoc(), $subjectElement, 'SubjectHeadingText', $subjectHeadingOrCode);
		} else {
			// Subject Scheme Name
			XMLCustomWriter::createChildWithText($this->_getDoc(), $subjectElement, 'SubjectSchemeName', $subjectSchemeName);

			// Subject Code
			XMLCustomWriter::createChildWithText($this->_getDoc(), $subjectElement, 'SubjectCode', $subjectHeadingOrCode);
		}

		return $subjectElement;
	}

	/**
	 * Create a description text element.
	 *
	 * @param $locale string
	 * @param $description string
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_otherTextElement($locale, $description) {
		$otherTextElement =& XMLCustomWriter::createElement($this->_getDoc(), 'OtherText');

		// Text type
		XMLCustomWriter::createChildWithText($this->_getDoc(), $otherTextElement, 'TextTypeCode', O4DOI_TEXT_TYPE_MAIN_DESCRIPTION);

		// Text
		$textElement =& XMLCustomWriter::createElement($this->_getDoc(), 'Text');
		$textElementContent =& XMLCustomWriter::createTextNode($this->_getDoc(), $description);
		XMLCustomWriter::appendChild($textElement, $textElementContent);

		// Text format
		XMLCustomWriter::setAttribute($textElement, 'textformat', O4DOI_TEXTFORMAT_DEFAULT);

		// Language
		$language = AppLocale::get3LetterIsoFromLocale($locale);
		assert(!empty($language));
		XMLCustomWriter::setAttribute($textElement, 'language', $language);

		XMLCustomWriter::appendChild($otherTextElement, $textElement);

		return $otherTextElement;
	}

	/**
	 * Create a description text element.
	 *
	 * @param $workOrProduct string
	 * @param $relationCode string One of the O4DOI_RELATION_* constants.
	 * @param $ids array
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &_relationElement($workOrProduct, $relationCode, $ids) {
		$relationElement =& XMLCustomWriter::createElement($this->_getDoc(), "Related$workOrProduct");

		// Relation code (mandatory)
		XMLCustomWriter::createChildWithText($this->_getDoc(), $relationElement, 'RelationCode', $relationCode);

		// Work/Product ID (mandatory)
		foreach($ids as $idType => $id) {
			XMLCustomWriter::appendChild($relationElement, $this->_idElement($workOrProduct, $idType, $id));
		}

		return $relationElement;
	}

	/**
	 * Get an ordered list of languages to export.
	 *
	 * @param $journal Journal
	 * @return array A list of locales.
	 */
	function _getExportLanguages(&$journal) {
		static $languages = array();
		if (empty($languages)) {
			$languages = $journal->getSupportedFormLocaleNames();
			ksort($languages);
		}
		return $languages;
	}
}

?>
