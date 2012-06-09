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

// The default endpoint for the embedded server.
define('SOLR_EMBEDDED_SERVER', 'http://localhost:8983/solr/ojs/search');

import('lib.pkp.classes.webservice.WebServiceRequest');
import('lib.pkp.classes.webservice.XmlWebService');

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
		$this->setReturnType(XSL_TRANSFORMER_DOCTYPE_DOM);
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
	 * @param $search array a raw search query as given by the end user
	 *  (one query per field).
	 *
	 * @return array An array of search results. The keys are
	 *  scores (1-9999) and the values are article IDs, journal IDs and
	 *  intallation IDs. Null if an error occured while querying the server.
	 */
	function retrieveResults($search) {
		$availableFields = $this->getAvailableFields();
		$fieldNames = $this->_getFieldNames();

		// Expand the search to all locales/formats.
		$expandedSearch = '';
		foreach ($search as $field => $query) {
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
				if(in_array($field, $fieldNames['static'])) {
					if (!empty($fieldSearch)) $fieldSearch .= ' OR ';
					$fieldSearch .= $field . ':(' . $query . ')';
				}
			}
			if (!empty($fieldSearch)) {
				if (!empty($expandedSearch)) $expandedSearch .= ' AND ';
				$expandedSearch .= '(' . $fieldSearch . ')';
			}
		}

		// Execute the search.
		$url = $this->_getSearchUrl();
		$params = array('q' => $expandedSearch);
		$response = $this->_makeRequest($url, $params);

		// Did we get a result?
		if (is_null($response)) return $response;

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
			assert(isset($result['score']));
			$score = intval($result['score'] * 10000);
			unset($result['score']);
			$scoredResults[$score] = $result;
		}
		return $scoredResults;
	}

	/**
	 * (Re-)indexes the given article in Solr.
	 *
	 * In Solr we cannot partially (re-)index an article. We always
	 * have to refresh the whole document if parts of it change.
	 *
	 * @param $articleId integer The article to be (re-)indexed.
	 */
	function indexArticle($articleId) {
		// FIXME: Not yet implemented.
	}

	/**
	 * Deletes the given article from the Solr index.
	 *
	 * @param $articleId integer The article to be deleted.
	 */
	function deleteArticleFromIndex($articleId) {
		// FIXME: Not yet implemented.
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

			// Identify the field.
			// 1) Is it a static field?
			if (in_array($fieldName, $fields['static'])) {
				// Static fields are "well known", we do not have to cache them.
				continue;
			}

			// We got a dynamic field which we have to parse individually.
			$fieldNameParts = explode('_', $fieldName);

			// 2) Is it a dynamic multi-format field?
			foreach($fields['multiformat'] as $multiformatField) {
				if (strpos($fieldName, $multiformatField) === 0) {
					// Parse the dynamic field name.
					$locale = array_pop($fieldNameParts);
					if ($locale != 'txt') {
						$locale = array_pop($fieldNameParts) . '_' . $locale;
					}
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

			// 3) Is it a dynamic localized field?
			$localizedField = array_shift($fieldNameParts);
			assert(in_array($localizedField, $fields['localized']));
			assert(count($fieldNameParts) == 2);
			$locale = implode('_', $fieldNameParts);
			$fieldCache[$localizedField][] = $locale;
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
	 * Make a request
	 *
	 * @param $url string
	 * @param $params array
	 *
	 * @return DOMXPath An XPath object with the response loaded. Null if an error occurred.
	 *  See _lastError for more details about the error.
	 */
	function &_makeRequest($url, $params = array()) {
		$webServiceRequest = new WebServiceRequest($url, $params);
		$response = $this->call($webServiceRequest);
		$nullValue = null;

		// Did we get a response at all?
		if (!$response) {
			$this->_lastError = 'Solr server not reachable. Is the solr server running? Does the configured search handler point to the right URL?'; // FIXME: Translate.
			return $nullValue;
		}

		// Did we get a 200OK response?
		$status = $this->getLastResponseStatus();
		if ($status !== WEBSERVICE_RESPONSE_OK) {
			$this->_lastError = $status. ' - ' . $response->saveXML();
			return $nullValue;
		}

		// Prepare the XPath object.
		assert(is_a($response, 'DOMDocument'));
		$xPath = new DOMXPath($response);
		return $xPath;
	}

	/**
	 * Return a list of all fields that may occur in the
	 * index.
	 *
	 * @return array
	 */
	function _getFieldNames() {
		return array(
			'localized' => array(
				'title', 'abstract', 'discipline', 'subject', 'type',
				'coverageGeo', 'coverageChron', 'coverageSample'
			),
			'multiformat' => array(
				'galley_full_text', 'suppFile_full_text'
			),
			'static' => array(
				'article_id', 'journal_id', 'inst_id', 'authors_s',
				'publication_date_dt', 'default_spell'
			)
		);
	}
}

?>
