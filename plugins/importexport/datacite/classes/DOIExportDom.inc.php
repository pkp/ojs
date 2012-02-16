<?php

/**
 * @file plugins/importexport/.../classes/DOIExportDom.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOIExportDom
 * @ingroup plugins_importexport_..._classes
 *
 * @brief Onix for DOI (O4DOI) XML export format implementation.
 */


import('lib.pkp.classes.xml.XMLCustomWriter');

define('DOI_EXPORT_FILETYPE_PDF', 'PDF');
define('DOI_EXPORT_FILETYPE_HTML', 'HTML');
define('DOI_EXPORT_FILETYPE_XML', 'XML');
define('DOI_EXPORT_FILETYPE_PS', 'PostScript');

class DOIExportDom {

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
	// Protected properties
	//
	/** @var XMLNode|DOMImplementation */
	var $_doc;

	/**
	 * Get the XML document.
	 * @return XMLNode|DOMImplementation
	 */
	function &getDoc() {
		return $this->_doc;
	}

	/** @var Request */
	var $_request;

	/**
	 * Get the current request.
	 * @return Request
	 */
	function &getRequest() {
		return $this->_request;
	}

	/**
	 * Are we in test mode?
	 * @return boolean
	 */
	function getTestMode() {
		$request =& $this->getRequest();
		return ($request->getUserVar('testMode') == '1');
	}

	/** @var DOIExportPlugin */
	var $_plugin;

	/**
	 * Get a plug-in setting.
	 * @return mixed
	 */
	function getPluginSetting($settingName) {
		$plugin =& $this->_plugin;
		$journal =& $this->getJournal();
		$settingValue = $plugin->getSetting($journal->getId(), $settingName);
		assert(!empty($settingValue));
		return $settingValue;
	}

	/** @var Journal */
	var $_journal;

	/**
	 * Get the journal (a.k.a. serial title) of this
	 * O4DOI message.
	 * @return Journal
	 */
	function &getJournal() {
		return $this->_journal;
	}

	/** @var PubObjectCache A cache for publication objects */
	var $_cache;

	/**
	 * Get the object cache.
	 * @return PubObjectCache
	 */
	function &getCache() {
		return $this->_cache;
	}


	//
	// Constructor
	//
	/**
	 * Constructor
	 * @param $request Request
	 * @param $plugin DOIExportPlugin
	 * @param $journal Journal
	 * @param $objectCache PubObjectCache
	 */
	function DOIExportDom(&$request, &$plugin, &$journal, &$objectCache) {
		// Configure the DOM.
		$this->_doc =& XMLCustomWriter::createDocument();
		$this->_request =& $request;
		$this->_plugin =& $plugin;
		$this->_journal =& $journal;
		$this->_cache =& $objectCache;
	}


	//
	// Public methods
	//
	/**
	 * Generate the XML document.
	 *
	 * This method either returns a fully validated XML document
	 * containing all given objects for export or it returns a boolean
	 * 'false' to indicate an error.
	 *
	 * If one or more errors occur then DOIExportDom::getErrors()
	 * will return localized error details for display by the client.
	 *
	 * @param $objects array An array of issues, articles or galleys. The
	 *  array must not contain more than one object type.
	 *
	 * @return XMLNode|DOMImplementation|boolean An XML document or 'false'
	 *  if an error occurred.
	 */
	function &generate(&$objects) {
		assert(false);
	}


	//
	// Protected template methods
	//
	/**
	 * The DOM's root element.
	 * @return string
	 */
	function getRootElementName() {
		assert(false);
	}

	/**
	 * Return the XML namespace.
	 * @return string
	 */
	function getNamespace() {
		assert(false);
	}

	/**
	 * Return the XML schema location.
	 * @return string
	 */
	function getXmlSchemaLocation() {
		assert(false);
	}


	//
	// Protected helper methods
	//
	/**
	 * Generate the XML root element.
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &rootElement() {
		// Create the root element and make it the document element of the document.
		$rootElement =& XMLCustomWriter::createElement($this->getDoc(), $this->getRootElementName());

		// Add root-level attributes.
		XMLCustomWriter::setAttribute($rootElement, 'xmlns', $this->getNamespace());
		XMLCustomWriter::setAttribute($rootElement, 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		XMLCustomWriter::setAttribute($rootElement, 'xsi:schemaLocation', $this->getXmlSchemaLocation());

		return $rootElement;
	}

	/**
	 * Create an XML element with a text node.
	 *
	 * FIXME: Move this to XMLCustomWriter? I leave the decision up to PKP...
	 *
	 * @param $name string
	 * @param $value string
	 * @param $attributes array An array with the attribute names as array
	 *  keys and attribute values as array values.
	 *
	 * @return XMLNode|DOMImplementation
	 */
	function &createElementWithText($name, $value, $attributes = array()) {
		$element =& XMLCustomWriter::createElement($this->getDoc(), $name);
		$elementContent =& XMLCustomWriter::createTextNode($this->getDoc(), $value);
		XMLCustomWriter::appendChild($element, $elementContent);
		foreach($attributes as $attributeName => $attributeValue) {
			XMLCustomWriter::setAttribute($element, $attributeName, $attributeValue);
		}
		return $element;
	}

	/**
	 * Retrieve all the OJS publication objects containing the
	 * data required to generate the given O4DOI schema.
	 *
	 * @param $object Issue|PublishedArticle|ArticleGalley The object to export.
	 *
	 * @return array An array with the required OJS objects.
	 */
	function &retrievePublicationObjects(&$object) {
		// Initialize local variables.
		$nullVar = null;
 		$journal =& $this->getJournal();
 		$cache =& $this->getCache();

		// Assign the object itself.
		$publicationObjects = array();
		switch (true) {
			case is_a($object, 'Issue'):
				$cache->add($object, $nullVar);
				$publicationObjects['issue'] =& $object;
				break;

			case is_a($object, 'PublishedArticle'):
				$cache->add($object, $nullVar);
				$publicationObjects['article'] =& $object;
				break;

			case is_a($object, 'ArticleGalley'):
				$publicationObjects['galley'] =& $object;
				break;
		}

		// Retrieve the article related to article files.
		if (is_a($object, 'ArticleFile')) {
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

		return $publicationObjects;
	}

	/**
	 * Retrieve all articles for the given issue
	 * and commit them to the cache.
	 * @param $issue Issue
	 * @return array
	 */
	function &retrieveArticlesByIssue(&$issue) {
		$cache =& $this->getCache();
		$issueId = $issue->getId();
		if (!$cache->isCached('articlesByIssue', $issueId)) {
			$articleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
			$articles =& $articleDao->getPublishedArticles($issueId);
			foreach ($articles as $article) {
				$cache->add($article, $nullVar);
				unset($article);
			}
			$cache->markComplete('articlesByIssue', $issueId);
		}
		return $cache->get('articlesByIssue', $issueId);
	}

	/**
	 * Retrieve all galleys for the given article
	 * and commit them to the cache.
	 * @param $article PublishedArticle
	 * @return array
	 */
	function &retrieveGalleysByArticle(&$article) {
		$cache =& $this->getCache();
		$articleId = $article->getId();
		if (!$cache->isCached('galleysByArticle', $articleId)) {
			$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
			$galleys =& $galleyDao->getGalleysByArticle($articleId);
			foreach($galleys as $galley) {
				$cache->add($galley, $article);
				unset($galley);
			}
			$cache->markComplete('galleysByArticle', $articleId);
		}
		return $cache->get('galleysByArticle', $articleId);
	}

	/**
	 * Identify the primary locale for this export.
	 * @param $article PublishedArticle
	 * @param $galley ArticleGalley
	 * @return string A valid PKP locale.
	 */
	function getPrimaryObjectLocale(&$article, &$galley) {
		if (is_a($galley, 'ArticleGalley')) {
			$primaryObjectLocale = $galley->getLocale();
		}
		if (empty($primaryObjectLocale) && is_a($article, 'PublishedArticle')) {
			$primaryObjectLocale = $article->getLocale();
		}
		if (empty($primaryObjectLocale)) {
			$journal =& $this->getJournal();
			$primaryObjectLocale = $journal->getPrimaryLocale();
		}
		assert(AppLocale::isLocaleValid($primaryObjectLocale));
		return $primaryObjectLocale;
	}

	/**
	 * Generate a proprietary ID for the given objects.
	 *
	 * The idea is to produce an idea that is globally unique within
	 * an OJS installation so that we can uniquely identify the exported
	 * object just by knowing the proprietary ID.
	 *
	 * We're using the internal ID rather than the "best ID" as the
	 * "best ID" can be changed by the end user while the internal ID
	 * is an automatically assigned database ID that cannot be changed
	 * without DBA access.
	 *
	 * @param $journal Journal
	 * @param $issue Issue
	 * @param $articleOrArticleFile PublishedArticle|ArticleFile An object representing an article.
	 * @param $articleFile ArticleGalley|SuppFile
	 *
	 * @return string The proprietary ID for the given objects.
	 */
	function getProprietaryId(&$journal, $issue = null, $articleOrArticleFile = null, $articleFile = null) {
		$proprietaryId = $journal->getId();
		if ($issue) $proprietaryId .= '-' . $issue->getId();
		if ($articleOrArticleFile) {
			assert($issue);
			$proprietaryId .= '-';
			if (is_a($articleOrArticleFile, 'PublishedArticle')) {
				$proprietaryId .= $articleOrArticleFile->getId();
			} else {
				assert(is_a($articleOrArticleFile, 'ArticleFile'));
				$proprietaryId .= $articleOrArticleFile->getArticleId();
			}
		}
		if ($articleFile) {
			assert($articleOrArticleFile);
			$proprietaryId .= '-';
			if (is_a($articleFile, 'ArticleGalley')) {
				$proprietaryId .= 'g';
			} else {
				assert(is_a($articleFile, 'SuppFile'));
				$proprietaryId .= 's';
			}
			$proprietaryId .= $articleFile->getId();
		}
		return $proprietaryId;
	}

	/**
	 * Identify the publisher of the journal.
	 * @return string
	 */
	function getPublisher() {
		$journal =& $this->getJournal();
		$publisher = $journal->getSetting('publisherInstitution');
		if (empty($publisher)) {
			// Use the journal title if no publisher is set.
			// This corresponds to the logic implemented for OAI interfaces, too.
			$publisher = $journal->getLocalizedTitle();
		}
		assert(!empty($publisher));
		return $publisher;
	}

	/**
	 * Identify the article subject class and code.
	 * @param $article PublishedArticle
	 * @param $primaryObjectLocale string
	 * @return array The subject class and code.
	 */
	function getSubjectClass(&$article, $primaryObjectLocale) {
		$journal =& $this->getJournal();
		$subjectSchemeTitle = $journal->getSetting('metaSubjectClassTitle', $primaryObjectLocale);
		if (empty($subjectSchemeTitle)) {
			$subjectSchemeTitle = $journal->getLocalizedSetting('metaSubjectClassTitle');
		}
		$subjectSchemeUrl = $journal->getSetting('metaSubjectClassUrl', $primaryObjectLocale);
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
		return array($subjectSchemeName, $subjectCode);
	}

	/**
	 * Try to identify the resource type of the
	 * given article file.
	 * @param $articleFile ArticleFile
	 * @return string|null One of the DOI_EXPORT_FILTYPE_* constants or null.
	 */
	function getFileType($articleFile) {
		// Identify the galley type.
		$resourceType = null;
		if (is_a($articleFile, 'ArticleXMLGalley')) {
			return DOI_EXPORT_FILETYPE_XML;
		}
		if (is_a($articleFile, 'ArticleHTMLGalley')) {
			return DOI_EXPORT_FILETYPE_HTML;
		}
		if (is_null($resourceType)) {
			// Try to guess the resource type from the MIME type.
			$fileType = $articleFile->getFileType();
			if (!empty($fileType)) {
				switch (true) {
					case strstr($fileType, 'html'):
						return DOI_EXPORT_FILETYPE_HTML;

					case strstr($fileType, 'pdf'):
						return DOI_EXPORT_FILETYPE_PDF;

					case strstr($fileType, 'postscript'):
						return DOI_EXPORT_FILETYPE_PS;

					case strstr($fileType, 'xml'):
						return DOI_EXPORT_FILETYPE_XML;

					default:
						return null;
				}
			}
		}
	}
}

?>
