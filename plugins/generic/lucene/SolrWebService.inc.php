<?php

/**
 * @file plugins/generic/lucene/SolrWebService.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SolrWebService
 * @ingroup plugins_generic_lucene
 *
 * @brief Implements the communication protocol with the solr search server.
 *
 * This class relies on the PHP curl extension. Please activate the
 * extension before trying to access a solr server through this class.
 */


define('SOLR_STATUS_ONLINE', 0x01);
define('SOLR_STATUS_OFFLINE', 0x02);

// FIXME: Move to plug-in settings.
define('SOLR_ADMIN_USER', 'admin');
define('SOLR_ADMIN_PASSWORD', 'ojsojs');
define('SOLR_INSTALLATION_ID', 'test-inst');

// The default endpoint for the embedded server.
define('SOLR_EMBEDDED_SERVER', 'http://localhost:8983/solr/ojs/search');

import('lib.pkp.classes.webservice.WebServiceRequest');
import('lib.pkp.classes.webservice.XmlWebService');
import('lib.pkp.classes.xml.XMLCustomWriter');

class SolrWebService extends XmlWebService {

	/** @var string The solr search handler name we place our searches on. */
	var $_solrSearchHandler;

	/** @var string The solr core we get our data from. */
	var $_solrCore;

	/** @var string The base URL of the solr server without core and search handler. */
	var $_solrServer;

	/** @var string A description of the last error that occured when calling the service. */
	var $_lastError;

	/** @var FileCache A cache containing the available search fields. */
	var $_fieldCache;

	/**
	 * Constructor
	 *
	 * @param $searchHandler string The search handler URL. We assume the embedded server
	 *  as a default.
	 */
	function SolrWebService($searchHandler = SOLR_EMBEDDED_SERVER) {
		// FIXME: Should we validate the search handler URL?

		parent::XmlWebService();

		// Configure the web service.
		$this->setAuthUsername(SOLR_ADMIN_USER);
		$this->setAuthPassword(SOLR_ADMIN_PASSWORD);

		// Remove trailing slashes.
		$searchHandler = rtrim($searchHandler, '/');

		// Parse the search handler URL.
		$searchHandlerParts = explode('/', $searchHandler);
		$this->_solrSearchHandler = array_pop($searchHandlerParts);
		$this->_solrCore = array_pop($searchHandlerParts);
		$this->_solrServer = implode('/', $searchHandlerParts) . '/';
	}


	//
	// Public API
	//
	/**
	 * Execute a search against the Solr search server.
	 *
	 * @param $journal Journal
	 * @param $search array a raw search query as given by the end user
	 *  (one query per field).
	 * @param $totalResults integer An output parameter returning the
	 *  total number of search results found by the query. This differs
	 *  from the actual number of returned results as the search can
	 *  be limited.
	 * @param $fromDate string An ISO 8601 date string or null.
	 * @param $toDate string An ISO 8601 date string or null.
	 *
	 * @return array An array of search results. The keys are
	 *  scores (1-9999) and the values are article IDs. Null if an error
	 *  occured while querying the server.
	 */
	function retrieveResults($journal, $search, &$totalResults, $page = 1, $itemsPerPage = 20, $fromDate = null, $toDate = null) {
		// Expand the search to all locales/formats.
		$expandedSearch = '';
		foreach ($search as $field => $query) {
			if (empty($query)) continue;

			// Do we expand a specific field or is this a
			// search on all fields?
			$fieldSearch = $this->_expandField($field, $query);
			if (!empty($fieldSearch)) {
				if (!empty($expandedSearch)) $expandedSearch .= ' AND ';
				$expandedSearch .= '( ' . $fieldSearch . ' )';
			}
		}

		// Add a range search on the publication date.
		if (!(is_null($fromDate) && is_null($toDate))) {
			if (is_null($fromDate)) $fromDate = '*';
			if (is_null($toDate)) $toDate = '*';
			if (!empty($expandedSearch)) $expandedSearch .= ' AND ';
			$expandedSearch .= 'publication_date_dt:[' . $fromDate . ' TO ' . $toDate . ']';
		}

		// Add the journal (if set).
		if (is_a($journal, 'Journal')) {
			if (!empty($expandedSearch)) $expandedSearch .= ' AND ';
			$expandedSearch .= 'journal_id:' . SOLR_INSTALLATION_ID . '-' . $journal->getId();
		}

		// Add the installation ID.
		if (!empty($expandedSearch)) $expandedSearch .= ' AND ';
		$expandedSearch .= 'inst_id:' . SOLR_INSTALLATION_ID;

		// Pagination.
		$start = ($page-1) * $itemsPerPage;
		$rows = $itemsPerPage;

		// Execute the search.
		$url = $this->_getSearchUrl();
		$params = array(
			'q' => $expandedSearch,
			'start' => (int) $start,
			'rows' => (int) $rows
		);
		$response = $this->_makeRequest($url, $params);

		// Did we get a result?
		if (is_null($response)) return $response;

		// Get the total number of documents found.
		$nodeList = $response->query('//response/result[@name="response"]/@numFound');
		assert($nodeList->length == 1);
		$resultNode = $nodeList->item(0);
		assert(is_numeric($resultNode->textContent));
		$totalResults = (int) $resultNode->textContent;

		// Run through all returned documents and read the ID fields.
		$results = array();
		$docs =& $response->query('//response/result/doc');
		foreach ($docs as $doc) {
			$currentDoc = array();
			foreach ($doc->childNodes as $docField) {
				// Get the document field
				$docFieldAtts = $docField->attributes;
				$fieldNameAtt = $docFieldAtts->getNamedItem('name');

				switch($docField->tagName) {
					case 'float':
						$currentDoc[$fieldNameAtt->value] = (float)$docField->textContent;
						break;

					case 'str':
						$currentDoc[$fieldNameAtt->value] = $docField->textContent;
						break;
				}
			}
			$results[] = $currentDoc;
		}

		// Re-index by score. There's no need to re-order as the
		// results come back ordered by score from the solr server.
		$scoredResults = array();
		foreach($results as $result) {
			// We only need the score and the article ID.
			assert(isset($result['score']) && isset($result['article_id']));

			// Transform the score into a positive integer between 0 and 9999.
			$score = intval($result['score'] * 10000);

			// Transform the article ID into an integer.
			$articleId = $result['article_id'];
			if (strpos($articleId, SOLR_INSTALLATION_ID . '-') !== 0) continue;
			$articleId = substr($articleId, strlen(SOLR_INSTALLATION_ID . '-'));
			if (!is_numeric($articleId)) continue;

			// Avoid loosing results due to duplicate scores.
			while(isset($scoredResults[$score])) {
				$score--;
			}

			// Store the result.
			$scoredResults[$score] = (int)$articleId;
		}
		return $scoredResults;
	}

	/**
	 * (Re-)indexes the given article in Solr.
	 *
	 * In Solr we cannot partially (re-)index an article. We always
	 * have to refresh the whole document if parts of it change.
	 *
	 * @param $article Article The article to be (re-)indexed.
	 * @param $journal Journal
	 *
	 * @return boolean true, if the indexing succeeded, otherwise false.
	 */
	function indexArticle(&$article, &$journal) {
		assert($article->getJournalId() == $journal->getId());

		// Generate the transfer XML for the article and POST it to the web service.
		$articleDoc =& $this->_getArticleXml($article, $journal);
		$articleXml = XMLCustomWriter::getXml($articleDoc);

		$url = $this->_getDihUrl() . '?command=full-import';
		$result = $this->_makeRequest($url, $articleXml, 'POST');
		if (is_null($result)) return false;

		// Return the number of documents that were indexed.
		$nodeList = $result->query('//response/lst[@name="statusMessages"]/str[@name="Total Documents Processed"]');
		assert($nodeList->length == 1);
		$resultNode = $nodeList->item(0);
		assert(is_numeric($resultNode->textContent));
		return ($resultNode->textContent == '1');
	}

	/**
	 * (Re-)indexes the given journal in Solr.
	 *
	 * We use asynchronous indexing when re-indexing a journal.
	 * This means that we transfer all meta-data to the solr
	 * server at once and the server will then pull full text
	 * files one-by-one.
	 *
	 * This is much faster than synchronous indexing and the
	 * request returns immediately after preparing and transmitting
	 * the (relatively small) meta-data package.
	 *
	 * @param $journal Journal The journal to be (re-)indexed.
	 *
	 * @return integer The number of documents processed or null if
	 *  an error occured.
	 */
	function indexJournal(&$journal) {
		// Run through all articles of the journal.
		$articleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
		$articles =& $articleDao->getPublishedArticlesByJournalId($journal->getId());
		$numIndexed = 0;
		$articleDoc = null;
		while (!$articles->eof()) {
			$article =& $articles->next();

			// Add the article to the article list if it has been fully submitted.
			if ($article->getDateSubmitted()) {
				$articleDoc =& $this->_getArticleXml($article, $journal, $articleDoc);
			}

			unset($article);
			$numIndexed++;
		}

		// Make an asynchronous POST request with all
		// articles of the journal.
		$articleXml = XMLCustomWriter::getXml($articleDoc);
		$url = $this->_getDihUrl() . '?command=full-import';
		$result = $this->_makeRequest($url, $articleXml, 'POST', true);

		if ($result == true) {
			return $numIndexed;
		} else {
			return null;
		}
	}

	/**
	 * Deletes the given article from the Solr index.
	 *
	 * @param $articleId integer The ID of the article to be deleted.
	 *
	 * @return boolean true if successful, otherwise false.
	 */
	function deleteArticleFromIndex($articleId) {
		$xml = '<id>' . SOLR_INSTALLATION_ID . '-' . $articleId . '</id>';
		return $this->_deleteFromIndex($xml);
	}

	/**
	 * Deletes all articles of this installation from the Solr index.
	 *
	 * @return boolean true if successful, otherwise false.
	 */
	function deleteAllArticlesFromIndex() {
		// Delete all articles of the installation.
		$xml = '<query>inst_id:' . SOLR_INSTALLATION_ID . '</query>';
		return $this->_deleteFromIndex($xml);
	}

	/**
	 * Checks the solr server status.
	 *
	 * @return integer One of the SOLR_STATUS_* constants.
	 */
	function getServerStatus() {
		// Make status request.
		$url = $this->_getAdminUrl() . 'cores';
		$params = array(
			'action' => 'STATUS',
			'core' => $this->_solrCore
		);
		$response = $this->_makeRequest($url, $params);

		// Did we get a response at all?
		if (is_null($response)) {
			return array(
				'status' => SOLR_STATUS_OFFLINE,
				'message' => $this->_lastError
			);
		}

		// Is the core online?
		assert(is_a($response, 'DOMXPath'));
		$nodeList = $response->query('//response/lst[@name="status"]/lst[@name="ojs"]/lst[@name="index"]/int[@name="numDocs"]');

		// Check whether the core is active.
		if ($nodeList->length != 1) {
			return array(
				'status' => SOLR_STATUS_OFFLINE,
				'message' => 'The requested core "' . $this->_solrCore . '" was not found on the solr server. Is it online?' // FIXME: Translate.
			);
		}

		$result = array(
			'status' => SOLR_STATUS_ONLINE,
			'message' => 'Index with ' . $nodeList->item(0)->textContent . ' documents online.'
		);

		return $result;
	}

	/**
	 * Returns an array with all (dynamic) fields in the index.
	 *
	 * @return array
	 */
	function getAvailableFields() {
		$cache =& $this->_getCache();
		$fieldCache = $cache->get('fields');
		return $fieldCache;
	}

	/**
	 * Flush the field cache.
	 */
	function flushFieldCache() {
		$cache =& $this->_getCache();
		$cache->flush();
	}


	//
	// Implement cache functions.
	//
	/**
	 * Refresh the cache from the solr server.
	 * @param $cache FileCache
	 * @param $id string not used in our case
	 *
	 * @return array The available field names.
	 */
	function _cacheMiss(&$cache, $id) {
		assert($id == 'fields');

		// Get the fields that may be found in the index.
		$fields = $this->_getFieldNames();

		// Prepare the cache.
		$fieldCache = array();
		foreach(array('localized', 'multiformat') as $fieldType) {
			foreach($fields[$fieldType] as $fieldName) {
				$fieldCache[$fieldName] = array();
			}
		}

		// Make a request to the luke request handler.
		$url = $this->_getCoreAdminUrl() . 'luke';
		$response = $this->_makeRequest($url);
		if (!is_a($response, 'DOMXPath')) return false;

		// Retrieve the field names from the response.
		$nodeList = $response->query('//response/lst[@name="fields"]/lst/@name');
		foreach ($nodeList as $node) {
			// Get the field name.
			$fieldName = $node->textContent;

			// Split the field name.
			$fieldNameParts = explode('_', $fieldName);

			// Identify the locale of the field.
			$locale = array_pop($fieldNameParts);
			if ($locale != 'txt') {
				$locale = array_pop($fieldNameParts) . '_' . $locale;
			}

			// 1) Is it a dynamic multi-format field?
			foreach($fields['multiformat'] as $multiformatField) {
				if (strpos($fieldName, $multiformatField) === 0) {
					// Identify the format of the field.
					$format = array_pop($fieldNameParts);

					// Add the field to the field cache.
					if (!isset($fieldCache[$multiformatField][$format])) {
						$fieldCache[$multiformatField][$format] = array();
					}
					$fieldCache[$multiformatField][$format][] = $locale;

					// Continue the outer loop.
					continue 2;
				}
			}

			// 2) Is it a dynamic localized field?
			foreach($fields['localized'] as $localizedField) {
				if (strpos($fieldName, $localizedField) === 0) {
					$fieldCache[$localizedField][] = $locale;
				}
			}
		}

		$fieldCache = array($id => $fieldCache);
		$cache->setEntireCache($fieldCache);
		return $fieldCache[$id];
	}

	/**
	 * Get the field cache.
	 * @return FileCache
	 */
	function &_getCache() {
		if (!isset($this->_fieldCache)) {
			// Instantiate a file cache.
			$cacheManager =& CacheManager::getManager();
			$this->_fieldCache = $cacheManager->getFileCache(
				'plugins-lucene', 'fieldCache',
				array(&$this, '_cacheMiss')
			);

			// Check to see if the data is outdated (24 hours).
			$cacheTime = $this->_fieldCache->getCacheTime();
			if (!is_null($cacheTime) && $cacheTime < (time() - 24 * 60 * 60)) {
				$this->_fieldCache->flush();
			}
		}
		return $this->_fieldCache;
	}


	//
	// Private helper methods
	//
	/**
	 * Identifies the general solr admin endpoint from the
	 * search handler URL.
	 *
	 * @return string
	 */
	function _getAdminUrl() {
		$adminUrl = $this->_solrServer . 'admin/';
		return $adminUrl;
	}

	/**
	 * Identifies the solr core-specific admin endpoint
	 * from the search handler URL.
	 *
	 * @return string
	 */
	function _getCoreAdminUrl() {
		$adminUrl = $this->_solrServer . $this->_solrCore . '/admin/';
		return $adminUrl;
	}

	/**
	 * Returns the solr search endpoint.
	 *
	 * @return string
	 */
	function _getSearchUrl() {
		$searchUrl = $this->_solrServer . $this->_solrCore . '/' . $this->_solrSearchHandler;
		return $searchUrl;
	}

	/**
	 * Returns the solr DIH endpoint.
	 *
	 * @return string
	 */
	function _getDihUrl() {
		$dihUrl = $this->_solrServer . $this->_solrCore . '/dih';
		return $dihUrl;
	}

	/**
	 * Returns the solr update endpoint.
	 *
	 * @return string
	 */
	function _getUpdateUrl() {
		$updateUrl = $this->_solrServer . $this->_solrCore . '/update';
		return $updateUrl;
	}

	/**
	 * Make a request
	 *
	 * @param $url string The request URL
	 * @param $params array request parameters
	 * @param $method string GET or POST
	 *
	 * @return DOMXPath An XPath object with the response loaded. Null if an error occurred.
	 *  See _lastError for more details about the error.
	 */
	function &_makeRequest($url, $params = array(), $method = 'GET', $async = false) {
		$webServiceRequest = new WebServiceRequest($url, $params, $method);
		if ($method == 'POST') {
			$webServiceRequest->setHeader('Content-Type', 'text/xml; charset=utf-8');
		}
		if ($async) {
			$webServiceRequest->setAsync($async);
			$this->setReturnType(XSL_TRANSFORMER_DOCTYPE_STRING);
		} else {
			$this->setReturnType(XSL_TRANSFORMER_DOCTYPE_DOM);
		}
		$response = $this->call($webServiceRequest);
		$nullValue = null;

		// Did we get a response at all?
		if (!$response) {
			$this->_lastError = 'Solr server not reachable. Is the solr server running? Does the configured search handler point to the right URL?'; // FIXME: Translate.
			return $nullValue;
		}

		// Return the result.
		if ($async) {
			$result = $response;
		} else {
			// Did we get a 200OK response?
			$status = $this->getLastResponseStatus();
			if ($status !== WEBSERVICE_RESPONSE_OK) {
				$this->_lastError = $status. ' - ' . $response->saveXML();
				return $nullValue;
			}

			// Prepare an XPath object.
			assert(is_a($response, 'DOMDocument'));
			$result = new DOMXPath($response);
		}
		return $result;
	}

	/**
	 * Return a list of all text fields that may occur in the
	 * index.
	 *
	 * @return array
	 */
	function _getFieldNames() {
		return array(
			'localized' => array(
				'title', 'abstract', 'discipline', 'subject',
				'type', 'coverage', 'all', 'index_terms'
			),
			'multiformat' => array(
				'galley_full_text', 'suppFile_full_text'
			),
			'static' => array(
				'authors' => 'authors_txt'
			)
		);
	}

	/**
	 * Expand the given query to all format/locale versions
	 * of the given field.
	 * @param $field string A field name without any extension.
	 * @param $query string The search phrase to expand.
	 * @return string The expanded query.
	 */
	function _expandField($field, $query) {
		$availableFields = $this->getAvailableFields();
		$fieldNames = $this->_getFieldNames();

		$fieldSearch = '';
		if (isset($availableFields[$field])) {
			if (in_array($field, $fieldNames['multiformat'])) {
				foreach($availableFields[$field] as $format => $locales) {
					foreach($locales as $locale) {
						if (!empty($fieldSearch)) $fieldSearch .= ' OR ';
						$fieldSearch .= $field . '_' . $format . '_' . $locale . ':(' . $query . ')';
					}
				}
			} else {
				assert(in_array($field, $fieldNames['localized']));
				foreach($availableFields[$field] as $locale) {
					if (!empty($fieldSearch)) $fieldSearch .= ' OR ';
					$fieldSearch .= $field . '_' . $locale . ':(' . $query . ')';
				}
			}
		} else {
			if(isset($fieldNames['static'][$field])) {
				if (!empty($fieldSearch)) $fieldSearch .= ' OR ';
				$fieldSearch .= $fieldNames['static'][$field] . ':(' . $query . ')';
			}
		}
		return $fieldSearch;
	}

	/**
	 * Establish the XML used to communicate with the
	 * solr indexing engine DIH.
	 * @param $article Article
	 * @param $journal Journal
	 * @param $articleDoc DOMDocument|XMLNode
	 * @return DOMDocument|XMLNode
	 */
	function _getArticleXml(&$article, &$journal, $articleDoc = null) {
		assert(is_a($article, 'Article'));

		if (is_null($articleDoc)) {
			// Create the document.
			$articleDoc =& XMLCustomWriter::createDocument();

			// Create the root node.
			$articleList =& XMLCustomWriter::createElement($articleDoc, 'articleList');
			XMLCustomWriter::appendChild($articleDoc, $articleList);
		} else {
			if (is_a($articleDoc, 'XMLNode')) {
				$articleList =& $articleDoc->getChildByName('articleList');
			} else {
				$articleList =& $articleDoc->documentElement;
			}
		}

		// Create a new article node.
		$articleNode =& XMLCustomWriter::createElement($articleDoc, 'article');
		XMLCustomWriter::setAttribute($articleNode, 'id', $article->getId());
		XMLCustomWriter::setAttribute($articleNode, 'journalId', $article->getJournalId());
		XMLCustomWriter::setAttribute($articleNode, 'instId', SOLR_INSTALLATION_ID);
		XMLCustomWriter::appendChild($articleList, $articleNode);

		// Add authors.
		$authors = $article->getAuthors();
		if (!empty($authors)) {
			$authorList =& XMLCustomWriter::createElement($articleDoc, 'authorList');
			foreach ($authors as $author) {
				XMLCustomWriter::createChildWithText($articleDoc, $authorList, 'author', $author->getFullName());
			}
			XMLCustomWriter::appendChild($articleNode, $authorList);
		}

		// Add titles.
		$titles = $article->getTitle(null); // return all locales
		if (!empty($titles)) {
			$titleList =& XMLCustomWriter::createElement($articleDoc, 'titleList');
			foreach ($titles as $locale => $title) {
				$titleNode =& XMLCustomWriter::createChildWithText($articleDoc, $titleList, 'title', $title);
				XMLCustomWriter::setAttribute($titleNode, 'locale', $locale);
			}
			XMLCustomWriter::appendChild($articleNode, $titleList);
		}

		// Add abstracts.
		$abstracts = $article->getAbstract(null); // return all locales
		if (!empty($abstracts)) {
			$abstractList =& XMLCustomWriter::createElement($articleDoc, 'abstractList');
			foreach ($abstracts as $locale => $abstract) {
				$abstractNode =& XMLCustomWriter::createChildWithText($articleDoc, $abstractList, 'abstract', $abstract);
				XMLCustomWriter::setAttribute($abstractNode, 'locale', $locale);
			}
			XMLCustomWriter::appendChild($articleNode, $abstractList);
		}

		// Add discipline.
		$disciplines = $article->getDiscipline(null); // return all locales
		if (!empty($disciplines)) {
			$disciplineList =& XMLCustomWriter::createElement($articleDoc, 'disciplineList');
			foreach ($disciplines as $locale => $discipline) {
				$disciplineNode =& XMLCustomWriter::createChildWithText($articleDoc, $disciplineList, 'discipline', $discipline);
				XMLCustomWriter::setAttribute($disciplineNode, 'locale', $locale);
			}
			XMLCustomWriter::appendChild($articleNode, $disciplineList);
		}

		// Add subjects and subject classes.
		$subjectClasses = $article->getSubjectClass(null);
		$subjects = $article->getSubject(null);
		if (!empty($subjectClasses) || !empty($subjects)) {
			$subjectList =& XMLCustomWriter::createElement($articleDoc, 'subjectList');
			if (!is_array($subjectClasses)) $subjectClasses = array();
			if (!is_array($subjects)) $subjects = array();
			$locales = array_unique(array_merge(array_keys($subjectClasses), array_keys($subjects)));
			foreach($locales as $locale) {
				$subject = '';
				if (isset($subjectClasses[$locale])) $subject .= $subjectClasses[$locale];
				if (isset($subjects[$locale])) {
					if (!empty($subject)) $subject .= ' ';
					$subject .= $subjects[$locale];
				}
				$subjectNode =& XMLCustomWriter::createChildWithText($articleDoc, $subjectList, 'subject', $subject);
				XMLCustomWriter::setAttribute($subjectNode, 'locale', $locale);
			}
			XMLCustomWriter::appendChild($articleNode, $subjectList);
		}

		// Add type.
		$types = $article->getType(null); // return all locales
		if (!empty($types)) {
			$typeList =& XMLCustomWriter::createElement($articleDoc, 'typeList');
			foreach ($types as $locale => $type) {
				$typeNode =& XMLCustomWriter::createChildWithText($articleDoc, $typeList, 'type', $type);
				XMLCustomWriter::setAttribute($typeNode, 'locale', $locale);
			}
			XMLCustomWriter::appendChild($articleNode, $typeList);
		}

		// Add coverage.
		$coverageGeo = $article->getCoverageGeo(null);
		$coverageChron = $article->getCoverageChron(null);
		$coverageSample = $article->getCoverageSample(null);
		if (!empty($coverageGeo) || !empty($coverageChron) || !empty($coverageSample)) {
			$coverageList =& XMLCustomWriter::createElement($articleDoc, 'coverageList');
			if (!is_array($coverageGeo)) $coverageGeo = array();
			if (!is_array($coverageChron)) $coverageChron = array();
			if (!is_array($coverageSample)) $coverageSample = array();
			$locales = array_unique(array_merge(array_keys($coverageGeo), array_keys($coverageChron), array_keys($coverageSample)));
			foreach($locales as $locale) {
				$coverage = '';
				if (isset($coverageGeo[$locale])) $coverage .= $coverageGeo[$locale];
				if (isset($coverageChron[$locale])) {
					if (!empty($coverage)) $coverage .= ' ';
					$coverage .= $coverageChron[$locale];
				}
				if (isset($coverageSample[$locale])) {
					if (!empty($coverage)) $coverage .= ' ';
					$coverage .= $coverageSample[$locale];
				}
				$coverageNode =& XMLCustomWriter::createChildWithText($articleDoc, $coverageList, 'coverage', $coverage);
				XMLCustomWriter::setAttribute($coverageNode, 'locale', $locale);
			}
			XMLCustomWriter::appendChild($articleNode, $coverageList);
		}

		// Add publication date.
		if (is_a($article, 'PublishedArticle')) {
			$publicationDate = $article->getDatePublished();
			if (!empty($publicationDate)) {
				// Transform date.
				$publicationDate = str_replace(' ', 'T', $publicationDate) . 'Z';
				$dateNode =& XMLCustomWriter::createChildWithText($articleDoc, $articleNode, 'publicationDate', $publicationDate);
			}
		}

		// We need the request and router to build file URLs.
		$request =& PKPApplication::getRequest();
		$router =& $request->getRouter(); /* @var $router PageRouter */

		// Add galley files
		$fileDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galleys =& $fileDao->getGalleysByArticle($article->getId());
		$galleyList = null;
		foreach ($galleys as $galley) { /* @var $galley ArticleGalley */
			$locale = $galley->getLocale();
			$mimetype = $galley->getFileType();
			$galleyUrl = $router->url($request, $journal->getPath(), 'article', 'download', array(intval($article->getId()), intval($galley->getId())));
			if (!empty($locale) && !empty($mimetype) && !empty($galleyUrl)) {
				if (is_null($galleyList)) {
					$galleyList =& XMLCustomWriter::createElement($articleDoc, 'galleyList');
				}
				$galleyNode =& XMLCustomWriter::createElement($articleDoc, 'galley');
				XMLCustomWriter::setAttribute($galleyNode, 'locale', $locale);
				XMLCustomWriter::setAttribute($galleyNode, 'mimetype', $mimetype);
				XMLCustomWriter::setAttribute($galleyNode, 'fileName', $galleyUrl);
				XMLCustomWriter::appendChild($galleyList, $galleyNode);
			}
		}

		// Wrap the galley XML as CDATA.
		if (!is_null($galleyList)) {
			if (is_callable(array($articleDoc, 'saveXml'))) {
				$galleyXml = $articleDoc->saveXml($galleyList);
			} else {
				$galleyXml = $galleyList->toXml();
			}
			$galleyOuterNode =& XMLCustomWriter::createElement($articleDoc, 'galley-xml');
			if (is_callable(array($articleDoc, 'createCDATASection'))) {
				$cdataNode =& $articleDoc->createCDATASection($galleyXml);
			} else {
				$cdataNode = new XMLNode();
				$cdataNode->setValue('<![CDATA[' . $galleyXml . ']]>');
			}
			XMLCustomWriter::appendChild($galleyOuterNode, $cdataNode);
			XMLCustomWriter::appendChild($articleNode, $galleyOuterNode);
		}

		// Add supplementary files
		$fileDao =& DAORegistry::getDAO('SuppFileDAO');
		$suppFiles =& $fileDao->getSuppFilesByArticle($article->getId());
		$suppFileList = null;
		foreach ($suppFiles as $suppFile) { /* @var $suppFile SuppFile */
			// Try to map the supp-file language to a PKP locale.
			$locale = null;
			$language = $suppFile->getLanguage();
			if (strlen($language) == 2) {
				$language = AppLocale::get3LetterFrom2LetterIsoLanguage($language);
			}
			if (strlen($language) == 3) {
				$locale = AppLocale::getLocaleFrom3LetterIso($language);
			}
			if (!AppLocale::isLocaleValid($locale)) {
				$locale = 'unknown';
			}

			$mimetype = $suppFile->getFileType();
			$suppFileUrl = $router->url($request, $journal->getPath(), 'article', 'downloadSuppFile', array(intval($article->getId()), intval($suppFile->getId())));

			if (!empty($locale) && !empty($mimetype) && !empty($suppFileUrl)) {
				if (is_null($suppFileList)) {
					$suppFileList =& XMLCustomWriter::createElement($articleDoc, 'suppFileList');
				}
				$suppFileNode =& XMLCustomWriter::createElement($articleDoc, 'suppFile');
				XMLCustomWriter::setAttribute($suppFileNode, 'locale', $locale);
				XMLCustomWriter::setAttribute($suppFileNode, 'mimetype', $mimetype);
				XMLCustomWriter::setAttribute($suppFileNode, 'fileName', $suppFileUrl);
				XMLCustomWriter::appendChild($suppFileList, $suppFileNode);

				// Add supp file meta-data.
				$suppFileMetadata = array(
					'title' => $suppFile->getTitle(null),
					'creator' => $suppFile->getCreator(null),
					'subject' => $suppFile->getSubject(null),
					'typeOther' => $suppFile->getTypeOther(null),
					'description' => $suppFile->getDescription(null),
					'source' => $suppFile->getSource(null)
				);
				foreach($suppFileMetadata as $field => $data) {
					if (!empty($data)) {
						foreach($data as $locale => $value) {
							$suppFileMDNode =& XMLCustomWriter::createChildWithText($articleDoc, $suppFileNode, $field, $value);
							XMLCustomWriter::setAttribute($suppFileMDNode, 'locale', $locale);
							XMLCustomWriter::appendChild($suppFileNode, $suppFileMDNode);
							unset($suppFileMDNode);
						}
					}
				}
			}
		}

		// Wrap the suppFile XML as CDATA.
		if (!is_null($suppFileList)) {
			if (is_callable(array($articleDoc, 'saveXml'))) {
				$suppFileXml = $articleDoc->saveXml($suppFileList);
			} else {
				$suppFileXml = $suppFileList->toXml();
			}
			$suppFileOuterNode =& XMLCustomWriter::createElement($articleDoc, 'suppFile-xml');
			if (is_callable(array($articleDoc, 'createCDATASection'))) {
				$cdataNode =& $articleDoc->createCDATASection($suppFileXml);
			} else {
				$cdataNode = new XMLNode();
				$cdataNode->setValue('<![CDATA[' . $suppFileXml . ']]>');
			}
			XMLCustomWriter::appendChild($suppFileOuterNode, $cdataNode);
			XMLCustomWriter::appendChild($articleNode, $suppFileOuterNode);
		}

		// Return the XML.
		return $articleDoc;
	}

	/**
	 * Delete documents from the index (by
	 * ID or by query).
	 * @param $xml string The documents to delete.
	 * @return boolean true, if successful, otherwise false.
	 */
	function _deleteFromIndex($xml) {
		// Add the deletion tags.
		$xml = '<delete>' . $xml . '</delete>';

		// Post the XML.
		$url = $this->_getUpdateUrl() . '?commit=true';
		$result = $this->_makeRequest($url, $xml, 'POST');
		if (is_null($result)) return false;

		// Check the return status (must be 0).
		$nodeList = $result->query('//response/lst[@name="responseHeader"]/int[@name="status"]');
		if($nodeList->length != 1) return false;
		$resultNode = $nodeList->item(0);
		if ($resultNode->textContent === '0') return true;
	}
}

?>
