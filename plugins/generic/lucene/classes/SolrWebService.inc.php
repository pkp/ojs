<?php

/**
 * @file plugins/generic/lucene/classes/SolrWebService.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SolrWebService
 * @ingroup plugins_generic_lucene_classes
 *
 * @brief Implements the communication protocol with the solr search server.
 *
 * This class relies on the PHP curl extension. Please activate the
 * extension before trying to access a solr server through this class.
 */


define('SOLR_STATUS_ONLINE', 0x01);
define('SOLR_STATUS_OFFLINE', 0x02);

// Flags used for index maintenance.
define('SOLR_INDEXINGSTATE_DIRTY', true);
define('SOLR_INDEXINGSTATE_CLEAN', false);

// Autosuggest-type:
// - suggester-based: fast and scalable, may propose terms that produce no
//   results, changes to the index will be reflected only after a dictionary
//   rebuild
// - faceting-based: slower and does not scale well, uses lots of cache
//   memory, only makes suggestions that will produce search results, index
//   changes appear immediately
define('SOLR_AUTOSUGGEST_SUGGESTER', 0x01);
define('SOLR_AUTOSUGGEST_FACETING', 0x02);

// The max. number of articles that can
// be indexed in a single batch.
define('SOLR_INDEXING_MAX_BATCHSIZE', 200);


import('lib.pkp.classes.webservice.WebServiceRequest');
import('lib.pkp.classes.webservice.XmlWebService');
import('lib.pkp.classes.xml.XMLCustomWriter');
import('plugins.generic.lucene.classes.SolrSearchRequest');
import('classes.search.ArticleSearch');

class SolrWebService extends XmlWebService {

	/** @var string The solr search handler name we place our searches on. */
	var $_solrSearchHandler;

	/** @var string The solr core we get our data from. */
	var $_solrCore;

	/** @var string The base URL of the solr server without core and search handler. */
	var $_solrServer;

	/** @var string The unique ID identifying this OJS installation to the solr server. */
	var $_instId;

	/** @var string A description of the last error or message that occured when calling the service. */
	var $_serviceMessage = '';

	/** @var FileCache A cache containing the available search fields. */
	var $_fieldCache;

	/** @var array A journal cache. */
	var $_journalCache;

	/** @var array An issue cache. */
	var $_issueCache;


	/**
	 * Constructor
	 *
	 * @param $searchHandler string The search handler URL. We assume the embedded server
	 *  as a default.
	 * @param $username string The HTTP BASIC authentication username.
	 * @param $password string The corresponding password.
	 * @param $instId string The unique ID of this OJS installation to partition
	 *  a shared index.
	 */
	function SolrWebService($searchHandler, $username, $password, $instId) {
		parent::XmlWebService();

		// Configure the web service.
		$this->setAuthUsername($username);
		$this->setAuthPassword($password);

		// Remove trailing slashes.
		assert(is_string($searchHandler) && !empty($searchHandler));
		$searchHandler = rtrim($searchHandler, '/');

		// Parse the search handler URL.
		$searchHandlerParts = explode('/', $searchHandler);
		$this->_solrSearchHandler = array_pop($searchHandlerParts);
		$this->_solrCore = array_pop($searchHandlerParts);
		$this->_solrServer = implode('/', $searchHandlerParts) . '/';

		// Set the installation ID.
		assert(is_string($instId) && !empty($instId));
		$this->_instId = $instId;
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the last service message.
	 * @return string
	 */
	function getServiceMessage() {
		return (string)$this->_serviceMessage;
	}


	/**
	 * Retrieve a journal (possibly from the cache).
	 * @param $journalId int
	 * @return Journal
	 */
	function &_getJournal($journalId) {
		if (isset($this->_journalCache[$journalId])) {
			$journal =& $this->_journalCache[$journalId];
		} else {
			$journalDao =& DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
			$journal =& $journalDao->getById($journalId);
			$this->_journalCache[$journalId] =& $journal;
		}

		return $journal;
	}

	/**
	 * Retrieve an issue (possibly from the cache).
	 * @param $issueId int
	 * @param $journalId int
	 * @return Issue
	 */
	function &_getIssue($issueId, $journalId) {
		if (isset($this->_issueCache[$issueId])) {
			$issue =& $this->_issueCache[$issueId];
		} else {
			$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issue =& $issueDao->getIssueById($issueId, $journalId, true);
			$this->_issueCache[$issueId] =& $issue;
		}

		return $issue;
	}


	//
	// Public API
	//
	/**
	 * Mark a single article "changed" so that the indexing
	 * back-end will update it during the next batch update.
	 * @param $articleId Integer
	 */
	function markArticleChanged($articleId) {
		if(!is_numeric($articleId)) {
			assert(false);
			return;
		}

		// Mark the article "changed".
		$articleDao =& DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
		$articleDao->updateSetting(
			$articleId, 'indexingState', SOLR_INDEXINGSTATE_DIRTY, 'bool'
		);
	}

	/**
	 * Mark the given journal for re-indexing.
	 * @param $journalId integer The ID of the journal to be (re-)indexed.
	 * @return integer The number of articles that have been marked.
	 */
	function markJournalChanged($journalId) {
		if (!is_numeric($journalId)) {
			assert(false);
			return;
		}

		// Retrieve all articles of the journal.
		$articleDao =& DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
		$articles =& $articleDao->getArticlesByJournalId($journalId);

		// Run through the articles and mark them "changed".
		while(!$articles->eof()) {
			$article =& $articles->next();
			$this->markArticleChanged($article->getId());
			unset($article);
		}
		return $articles->getCount();
	}

	/**
	 * (Re-)indexes all changed articles in Solr.
	 *
	 * This is the push-indexing implementation of the Solr
	 * web service.
	 *
	 * To control memory usage and response time we
	 * index articles in batches. Batches should be as
	 * large as possible to reduce index commit overhead.
	 *
	 * @param $batchSize integer The maximum number of articles
	 *  to be indexed in this run.
	 * @param $journalId integer If given, restrains index
	 *  updates to the given journal.
	 *
	 * @return integer The number of articles processed or
	 *  null if an error occured. After an error the method
	 *  SolrWebService::getServiceMessage() will return details
	 *  of the error.
	 */
	function pushChangedArticles($batchSize = SOLR_INDEXING_MAX_BATCHSIZE, $journalId = null) {
		// Internally we just execute an indexing transaction with
		// a push indexing callback.
		return $this->_indexingTransaction(
			array($this, '_pushIndexingCallback'), $batchSize, $journalId
		);
	}

	/**
	 * Retrieves a batch of articles in XML format.
	 *
	 * This is the pull-indexing implementation of the Solr
	 * web service.
	 *
	 * To control memory usage and response time we
	 * index articles in batches. Batches should be as
	 * large as possible to reduce index commit overhead.
	 *
	 * @param $sendXmlCallback callback This function will be called
	 *  with the generated XML. We do not send the XML from here
	 *  as communication with the requesting counterparty should
	 *  be done from the controller and not from the back-end.
	 * @param $batchSize integer The maximum number of articles
	 *  to be returned.
	 * @param $journalId integer If given, only returns
	 *  articles from the given journal.
	 *
	 * @return integer The number of articles processed or
	 *  null if an error occured. After an error the method
	 *  SolrWebService::getServiceMessage() will return details
	 *  of the error.
	 */
	function pullChangedArticles($pullIndexingCallback, $batchSize = SOLR_INDEXING_MAX_BATCHSIZE, $journalId = null) {
		// Internally we just execute an indexing transaction with
		// a pull indexing callback.
		return $this->_indexingTransaction(
			$pullIndexingCallback, $batchSize, $journalId
		);
	}

	/**
	 * Deletes the given article from the Solr index.
	 *
	 * @param $articleId integer The ID of the article to be deleted.
	 *
	 * @return boolean true if successful, otherwise false.
	 */
	function deleteArticleFromIndex($articleId) {
		$xml = '<id>' . $this->_instId . '-' . $articleId . '</id>';
		return $this->_deleteFromIndex($xml);
	}

	/**
	 * Deletes all articles of a journal or of the
	 * installation from the Solr index.
	 *
	 * @param $journalId integer If given, only articles
	 *  from this journal will be deleted.
	 * @return boolean true if successful, otherwise false.
	 */
	function deleteArticlesFromIndex($journalId = null) {
		// Delete only articles from one journal if a
		// journal ID is given.
		$journalQuery = '';
		if (is_numeric($journalId)) {
			$journalQuery = ' AND journal_id:' . $this->_instId . '-' . $journalId;
		}

		// Delete all articles of the installation (or journal).
		$xml = '<query>inst_id:' . $this->_instId . $journalQuery . '</query>';
		return $this->_deleteFromIndex($xml);
	}

	/**
	 * Execute a search against the Solr search server.
	 *
	 * @param $searchRequest SolrSearchRequest
	 * @param $totalResults integer An output parameter returning the
	 *  total number of search results found by the query. This differs
	 *  from the actual number of returned results as the search can
	 *  be limited.
	 *
	 * @return array An array of search results. The main keys are result
	 *  types. These are "scoredResults" and "alternativeSpelling".
	 *  The keys in the "scoredResults" sub-array are scores (1-9999) and the
	 *  values are article IDs. The alternative spelling sub-array returns
	 *  an alternative query string (if any) and the number of hits for this
	 *  string. Null if an error occured while querying the server.
	 */
	function retrieveResults(&$searchRequest, &$totalResults) {
		// Construct the main query.
		$params = $this->_getSearchQueryParameters($searchRequest);

		// If we have no filters at all then return an
		// empty result set.
		if (!isset($params['q'])) return array();

		// Pagination.
		$itemsPerPage = $searchRequest->getItemsPerPage();
		$params['start'] = ($searchRequest->getPage() - 1) * $itemsPerPage;
		$params['rows'] = $itemsPerPage;

		// Ordering.
		$params['sort'] = $this->_getOrdering($searchRequest->getOrderBy(), $searchRequest->getOrderDir());

		// Highlighting.
		if ($searchRequest->getHighlighting()) {
			$params['hl'] = 'on';
			$params['hl.fl'] = $this->_expandFieldList(array('abstract', 'galleyFullText'));
		}

		// Faceting.
		$facetCategories = $searchRequest->getFacetCategories();
		if (!empty($facetCategories)) {
			$params['facet'] = 'on';
			$params['facet.field'] = array();

			// NB: We only add fields in the current UI locale, i.e.
			// facets are considered part of the navigation and not
			// search results.
			$locale = AppLocale::getLocale();

			// Add facet fields corresponding to the
			// solicited facet categories.
			$facetFields = $this->_getFieldNames('facet');
			$enabledFields = 0;
			foreach($facetFields['localized'] as $fieldName) {
				if (in_array($fieldName, $facetCategories)) {
					$params['facet.field'][] = $fieldName . '_' . $locale . '_facet';
					$enabledFields++;
				}
			}
			foreach($facetFields['static'] as $categoryName => $fieldName) {
				if (in_array($categoryName, $facetCategories)) {
					$params['facet.field'][] = $fieldName;
					$enabledFields++;
				}
			}
			if (in_array('publicationDate', $facetCategories)) {
				$params['facet.range'] = 'publicationDate_dt';
				$params['facet.range.start'] = 'NOW/YEAR-50YEARS';
				$params['facet.range.end'] = 'NOW';
				$params['facet.range.gap'] = '+1YEAR';
				$params['facet.range.other'] = 'all';
				$enabledFields++;
			}

			// Did we find all solicited categories?
			assert($enabledFields == count($facetCategories));
		}

		// Boost factors.
		$boostFactors = $searchRequest->getBoostFactors();
		foreach($boostFactors as $field => $valueBoost) {
			foreach ($valueBoost as $value => $boostFactor) {
				if ($boostFactor == 0) {
					// Add a filter query to remove all results.
					if (!isset($params['fq'])) $params['fq'] = array();
					$params['fq'][] = "-$field:$value";
				} elseif ($boostFactor > 0) {
					// Add a boost function query (only works for numeric fields!).
					if (!isset($params['boost'])) $params['boost'] = array();
					$params['boost'][] = "map($field,$value,$value,$boostFactor,1)";
				}
			}
		}

		// Make the search request.
		$url = $this->_getSearchUrl();
		$response = $this->_makeRequest($url, $params);

		// Did we get a result?
		if (is_null($response)) return null;

		// Get the total number of documents found.
		$nodeList = $response->query('/response/result[@name="response"]/@numFound');
		assert($nodeList->length == 1);
		$resultNode = $nodeList->item(0);
		assert(is_numeric($resultNode->textContent));
		$totalResults = (int) $resultNode->textContent;

		// Run through all returned documents and read the ID fields.
		$results = array();
		$docs =& $response->query('/response/result/doc');
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

		// Re-index the result set. There's no need to re-order as the
		// results come back ordered from the solr server.
		$scoredResults = array();
		foreach($results as $resultIndex => $result) {
			// We only need the article ID.
			assert(isset($result['article_id']));

			// Use the result order to "score" results. This
			// will do relevance sorting and field sorting.
			$score = $itemsPerPage - $resultIndex;

			// Transform the article ID into an integer.
			$articleId = $result['article_id'];
			if (strpos($articleId, $this->_instId . '-') !== 0) continue;
			$articleId = substr($articleId, strlen($this->_instId . '-'));
			if (!is_numeric($articleId)) continue;

			// Store the result.
			$scoredResults[$score] = (int)$articleId;
		}

		// Read alternative spelling suggestions (if any).
		$spellingSuggestion = null;
		if ($searchRequest->getSpellcheck()) {
			$alternativeSpellingNodeList =& $response->query('/response/lst[@name="spellcheck"]/lst[@name="suggestions"]/str[@name="collation"]');
			if ($alternativeSpellingNodeList->length == 1) {
				$alternativeSpellingNode = $alternativeSpellingNodeList->item(0);
				$spellingSuggestion = $alternativeSpellingNode->textContent;

				// Translate back to the current language.
				$spellingSuggestion = $this->_translateSearchPhrase($spellingSuggestion, true);
			}
		}

		// Read highlighting results (if any).
		$highligthedArticles = null;
		if ($searchRequest->getHighlighting()) {
			$highligthedArticles = array();
			$highlightingNodeList =& $response->query('/response/lst[@name="highlighting"]/lst');
			foreach($highlightingNodeList as $highlightingNode) { /* @var $highlightingNode DOMElement */
				if ($highlightingNode->hasChildNodes()) {
					$indexArticleId = $highlightingNode->attributes->getNamedItem('name')->nodeValue;
					$articleIdParts = explode('-', $indexArticleId);
					$articleId = array_pop($articleIdParts);
					$excerpt = $highlightingNode->firstChild->firstChild->textContent;
					if (is_numeric($articleId) && !empty($excerpt)) {
						$highligthedArticles[$articleId] = $excerpt;
					}
				}
			}
		}

		// Read facets (if any).
		$facets = null;
		if (!empty($facetCategories)) {
			$facets = array();

			// Read field-based facets.
			$facetsNodeList =& $response->query('/response/lst[@name="facet_counts"]/lst[@name="facet_fields"]/lst');
			foreach($facetsNodeList as $facetFieldNode) { /* @var $facetFieldNode DOMElement */
				$facetField = $facetFieldNode->attributes->getNamedItem('name')->nodeValue;
				$facetFieldParts = explode('_', $facetField);
				$facetCategory = array_shift($facetFieldParts);
				$facets[$facetCategory] = array();
				foreach($facetFieldNode->childNodes as $facetNode) { /* @var $facetNode DOMElement */
					$facet = $facetNode->attributes->getNamedItem('name')->nodeValue;
					$facetCount = (integer)$facetNode->textContent;
					// Only select facets that return results and are more selective than
					// the current search criteria.
					if (!empty($facet) && $facetCount > 0 && $facetCount < $totalResults) {
						$facets[$facetCategory][$facet] = $facetCount;
					}
				}
			}

			// Read range-based facets.
			$facetsNodeList =& $response->query('/response/lst[@name="facet_counts"]/lst[@name="facet_ranges"]/lst');
			foreach($facetsNodeList as $facetFieldNode) { /* @var $facetFieldNode DOMElement */
				$facetField = $facetFieldNode->attributes->getNamedItem('name')->nodeValue;
				$facetFieldParts = explode('_', $facetField);
				$facetCategory = array_shift($facetFieldParts);
				$facets[$facetCategory] = array();
				foreach($facetFieldNode->childNodes as $rangeInfoNode) { /* @var $rangeInfoNode DOMElement */
					// Search for the "counts" node in the range info.
					if($rangeInfoNode->attributes->getNamedItem('name')->nodeValue == 'counts') {
						// Run through all ranges.
						foreach($rangeInfoNode->childNodes as $facetNode) { /* @var $facetNode DOMElement */
							// Retrieve and format the date range facet.
							$facet = $facetNode->attributes->getNamedItem('name')->nodeValue;
							$facet = date('Y', strtotime(substr($facet, 0, 10)));
							$facetCount = (integer)$facetNode->textContent;
							// Only select ranges that return results and are more selective than
							// the current search criteria.
							if ($facetCount > 0 && $facetCount < $totalResults) {
								$facets[$facetCategory][$facet] = $facetCount;
							}
						}

						// We do not need the other children.
						break;
					}
				}
			}
		}

		return array(
			'scoredResults' => $scoredResults,
			'spellingSuggestion' => $spellingSuggestion,
			'highlightedArticles' => $highligthedArticles,
			'facets' => $facets
		);
	}

	/**
	 * Retrieve auto-suggestions from the solr index
	 * corresponding to the given user input.
	 *
	 * @param $searchRequest SolrSearchRequest Active search filters. Choosing
	 *  the faceting auto-suggest implementation via $autosuggestType will
	 *  pre-filter auto-suggestions based on this search request. In case of
	 *  the suggester component, the search request will simply be ignored.
	 * @param $fieldName string The field to suggest values for. Values are
	 *  queried on field level to improve relevance of suggestions.
	 * @param $userInput string Partial query input. This input will be split
	 *  split up. Only the last query term will be used to suggest values.
	 * @param $autosuggestType string One of the SOLR_AUTOSUGGEST_* constants.
	 *  The faceting implementation is slower but will return more relevant
	 *  suggestions. The suggestor implementation is faster and scales better
	 *  in large deployments. It will return terms from a field-specific global
	 *  dictionary, though, e.g. from different journals.
	 *
	 * @return array A list of suggested queries
	 */
	function getAutosuggestions($searchRequest, $fieldName, $userInput, $autosuggestType) {
		// Validate input.
		$allowedFieldNames = array_values(ArticleSearch::getIndexFieldMap());
		$allowedFieldNames[] = 'query';
		$allowedFieldNames[] = 'indexTerms';
		if (!in_array($fieldName, $allowedFieldNames)) return array();

		// Check the auto-suggest type.
		$autosuggestTypes = array(SOLR_AUTOSUGGEST_SUGGESTER, SOLR_AUTOSUGGEST_FACETING);
		if (!in_array($autosuggestType, $autosuggestTypes)) return array();

		// Execute an auto-suggest request.
		$url = $this->_getAutosuggestUrl($autosuggestType);
		if ($autosuggestType == SOLR_AUTOSUGGEST_SUGGESTER) {
			$suggestions = $this->_getSuggesterAutosuggestions($url, $userInput, $fieldName);
		} else {
			$suggestions = $this->_getFacetingAutosuggestions($url, $searchRequest, $userInput, $fieldName);
		}
		return $suggestions;
	}

	/**
	 * Retrieve "interesting terms" from a document to be used in a "similar
	 * documents" search.
	 *
	 * @param $articleId integer The article from which we retrieve "interesting
	 *  terms".
	 *
	 * @return array An array of terms that can be used to execute a search
	 *  for similar documents.
	 */
	function getInterestingTerms($articleId) {
		// Make a request to the MLT request handler.
		$url = $this->_getInterestingTermsUrl();
		$params = array(
			'q' => $this->_instId . '-' . $articleId,
			'mlt.fl' => $this->_expandFieldList(array('title', 'abstract'))
		);
		$response = $this->_makeRequest($url, $params); /* @var $response DOMXPath */
		if (!is_a($response, 'DOMXPath')) return null;

		// Check whether a query will actually return something.
		// This is an optimization to avoid unnecessary requests
		// in case they won't return anything interesting.
		$nodeList = $response->query('/response/result[@name="response"]/@numFound');
		if ($nodeList->length != 1) return array();
		$numFound =& $nodeList->item(0)->textContent;
		if ($numFound = 0) return array();

		// Retrieve interesting terms from the response.
		$terms = array();
		$nodeList = $response->query('/response/arr[@name="interestingTerms"]/str');
		foreach ($nodeList as $node) {
			// Get the field name.
			$term = $node->textContent;
			// Filter reverse wildcard terms.
			if (substr($term,0,3) === '#1;') continue;
			$terms[] = $term;
		}
		return $terms;
	}

	/**
	 * Returns an array with all (dynamic) fields in the index.
	 *
	 * NB: This is cached data so after an index update we may
	 * have to flush the index to re-read the current index state.
	 *
	 * @param $fieldType string Either 'search' or 'sort'.
	 * @return array
	 */
	function getAvailableFields($fieldType) {
		$cache =& $this->_getCache();
		$fieldCache = $cache->get($fieldType);
		return $fieldCache;
	}

	/**
	 * Flush the field cache.
	 */
	function flushFieldCache() {
		$cache =& $this->_getCache();
		$cache->flush();
	}

	/**
	 * Retrieve a document directly from the index
	 * (for testing/debugging purposes only).
	 *
	 * @param $articleId
	 *
	 * @return array The document fields.
	 */
	function getArticleFromIndex($articleId) {
		// Make a request to the luke request handler.
		$url = $this->_getCoreAdminUrl() . 'luke';
		$params = array('id' => $this->_instId . '-' . $articleId);
		$response = $this->_makeRequest($url, $params);
		if (!is_a($response, 'DOMXPath')) return false;

		// Retrieve all fields from the response.
		$doc = array();
		$nodeList = $response->query('/response/lst[@name="doc"]/doc[@name="solr"]/str');
		foreach ($nodeList as $node) {
			// Get the field name.
			$fieldName = $node->attributes->getNamedItem('name')->value;
			$fieldValue = $node->textContent;
			$doc[$fieldName] = $fieldValue;
		}

		return $doc;
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
			return SOLR_STATUS_OFFLINE;
		}

		// Is the core online?
		assert(is_a($response, 'DOMXPath'));
		$nodeList = $response->query('/response/lst[@name="status"]/lst[@name="ojs"]/lst[@name="index"]/int[@name="numDocs"]');

		// Check whether the core is active.
		if ($nodeList->length != 1) {
			$this->_serviceMessage = __('plugins.generic.lucene.message.coreNotFound', array('core' => $this->_solrCore));
			return SOLR_STATUS_OFFLINE;
		}

		$this->_serviceMessage = __('plugins.generic.lucene.message.indexOnline', array('numDocs' => $nodeList->item(0)->textContent));
		return SOLR_STATUS_ONLINE;
	}


	//
	// Field cache implementation
	//
	/**
	 * Refresh the cache from the solr server.
	 *
	 * @param $cache FileCache
	 * @param $id string The field type.
	 *
	 * @return array The available field names.
	 */
	function _cacheMiss(&$cache, $id) {
		assert(in_array($id, array('search', 'sort')));

		// Get the fields that may be found in the index.
		$fields = $this->_getFieldNames('all');

		// Prepare the cache.
		$fieldCache = array();
		foreach(array('search', 'sort') as $fieldType) {
			$fieldCache[$fieldType] = array();
			foreach(array('localized', 'multiformat', 'static') as $fieldSubType) {
				if ($fieldSubType == 'static') {
					foreach($fields[$fieldType][$fieldSubType] as $fieldName => $dummy) {
						$fieldCache[$fieldType][$fieldName] = array();
					}
				} else {
					foreach($fields[$fieldType][$fieldSubType] as $fieldName) {
						$fieldCache[$fieldType][$fieldName] = array();
					}
				}
			}
		}

		// Make a request to the luke request handler.
		$url = $this->_getCoreAdminUrl() . 'luke';
		$response = $this->_makeRequest($url);
		if (!is_a($response, 'DOMXPath')) return false;

		// Retrieve the field names from the response.
		$nodeList = $response->query('/response/lst[@name="fields"]/lst/@name');
		foreach ($nodeList as $node) {
			// Get the field name.
			$fieldName = $node->textContent;

			// Split the field name.
			$fieldNameParts = explode('_', $fieldName);

			// Identify the field type.
			$fieldSuffix = array_pop($fieldNameParts);
			if (in_array($fieldSuffix, array('spell', 'facet'))) continue;
			if (strpos($fieldSuffix, 'sort') !== false) {
				$fieldType = 'sort';
				$fieldSuffix = array_pop($fieldNameParts);
			} else {
				$fieldType = 'search';
			}

			// 1) Is this a static field?
			foreach($fields[$fieldType]['static'] as $staticField => $fullFieldName) {
				if ($fieldName == $fullFieldName) {
					$fieldCache[$fieldType][$staticField][] = $fullFieldName;
					continue 2;
				}
			}

			// Localized and multiformat fields have a locale suffix.
			$locale = $fieldSuffix;
			if ($locale != 'txt') {
				$locale = array_pop($fieldNameParts) . '_' . $locale;
			}

			// 2) Is this a dynamic localized field?
			foreach($fields[$fieldType]['localized'] as $localizedField) {
				if (strpos($fieldName, $localizedField) === 0) {
					$fieldCache[$fieldType][$localizedField][] = $locale;
				}
			}

			// 3) Is this a dynamic multi-format field?
			foreach($fields[$fieldType]['multiformat'] as $multiformatField) {
				if (strpos($fieldName, $multiformatField) === 0) {
					// Identify the format of the field.
					$format = array_pop($fieldNameParts);

					// Add the field to the field cache.
					if (!isset($fieldCache[$fieldType][$multiformatField][$format])) {
						$fieldCache[$fieldType][$multiformatField][$format] = array();
					}
					$fieldCache[$fieldType][$multiformatField][$format][] = $locale;

					// Continue the outer loop.
					continue 2;
				}
			}
		}

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
	 * Returns the solr update endpoint.
	 *
	 * @return string
	 */
	function _getUpdateUrl() {
		$updateUrl = $this->_solrServer . $this->_solrCore . '/update';
		return $updateUrl;
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
	 * Returns the solr search endpoint.
	 * @return string
	 */
	function _getSearchUrl() {
		$searchUrl = $this->_solrServer . $this->_solrCore . '/' . $this->_solrSearchHandler;
		return $searchUrl;
	}

	/**
	 * Returns the solr auto-suggestion endpoint.
	 * @param $autosuggestType string One of the SOLR_AUTOSUGGEST_* constants
	 * @return string
	 */
	function _getAutosuggestUrl($autosuggestType) {
		$autosuggestUrl = $this->_solrServer . $this->_solrCore;
		switch ($autosuggestType) {
			case SOLR_AUTOSUGGEST_SUGGESTER:
				$autosuggestUrl .= '/dictBasedSuggest';
				break;

			case SOLR_AUTOSUGGEST_FACETING:
				$autosuggestUrl .= '/facetBasedSuggest';
				break;

			default:
				$autosuggestUrl = null;
				assert(false);
		}
		return $autosuggestUrl;
	}


	/**
	 * Returns the solr endpoint to retrieve
	 * "interesting terms" from a given document.
	 * @return string
	 */
	function _getInterestingTermsUrl() {
		return $this->_solrServer . $this->_solrCore . '/simdocs';
	}

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
	 * Make a request
	 *
	 * @param $url string The request URL
	 * @param $params mixed array (key value pairs) or string request parameters
	 * @param $method string GET or POST
	 *
	 * @return DOMXPath An XPath object with the response loaded. Null if an error occurred.
	 *  See _serviceMessage for more details about the error.
	 */
	function &_makeRequest($url, $params = array(), $method = 'GET') {
		$webServiceRequest = new WebServiceRequest($url, $params, $method);
		if ($method == 'POST') {
			$webServiceRequest->setHeader('Content-Type', 'text/xml; charset=utf-8');
		}
		$this->setReturnType(XSL_TRANSFORMER_DOCTYPE_DOM);
		$response = $this->call($webServiceRequest);
		$nullValue = null;

		// Did we get a response at all?
		if (!$response) {
			$this->_serviceMessage = __('plugins.generic.lucene.message.searchServiceOffline');
			return $nullValue;
		}

		// Did we get a "200 - OK" response?
		$status = $this->getLastResponseStatus();
		if ($status !== WEBSERVICE_RESPONSE_OK) {
			// We show a generic error message to the end user
			// to avoid information leakage and log the exact error.
			$application =& PKPApplication::getApplication();
			error_log($application->getName() . ' - Lucene plugin:' . "\nThe Lucene web service returned a status code $status and the message\n" . $response->saveXML());
			$this->_serviceMessage = __('plugins.generic.lucene.message.webServiceError');
			return $nullValue;
		}

		// Prepare an XPath object.
		assert(is_a($response, 'DOMDocument'));
		$result = new DOMXPath($response);

		// Return the result.
		return $result;
	}

	/**
	 * Return a list of all text fields that may occur in the
	 * index.
	 * @param $fieldType string "search", "sort" or "all"
	 *
	 * @return array
	 */
	function _getFieldNames($fieldType) {
		$fieldNames = array(
			'search' => array(
				'localized' => array(
					'title', 'abstract', 'discipline', 'subject',
					'type', 'coverage', 'suppFiles'
				),
				'multiformat' => array(
					'galleyFullText'
				),
				'static' => array(
					'authors' => 'authors_txt',
					'publicationDate' => 'publicationDate_dt'
				)
			),
			'sort' => array(
				'localized' => array(
					'title', 'journalTitle'
				),
				'multiformat' => array(),
				'static' => array(
					'authors' => 'authors_txtsort',
					'publicationDate' => 'publicationDate_dtsort',
					'issuePublicationDate' => 'issuePublicationDate_dtsort'
				)
			),
			'facet' => array(
				'localized' => array(
					'discipline', 'subject', 'type', 'coverage', 'journalTitle'
				),
				'multiformat' => array(),
				'static' => array(
					'authors' => 'authors_facet',
				)
			)
		);
		if ($fieldType == 'all') {
			return $fieldNames;
		} else {
			assert(isset($fieldNames[$fieldType]));
			return $fieldNames[$fieldType];
		}
	}

	/**
	 * Identify all format/locale versions of the given field.
	 * @param $field string A field name without any extension.
	 * @return array A list of index fields.
	 */
	function _getLocalesAndFormats($field) {
		$availableFields = $this->getAvailableFields('search');
		$fieldNames = $this->_getFieldNames('search');

		$indexFields = array();
		if (isset($availableFields[$field])) {
			if (in_array($field, $fieldNames['multiformat'])) {
				// This is a multiformat field.
				foreach($availableFields[$field] as $format => $locales) {
					foreach($locales as $locale) {
						$indexFields[] = $field . '_' . $format . '_' . $locale;
					}
				}
			} elseif(in_array($field, $fieldNames['localized'])) {
				// This is a localized field.
				foreach($availableFields[$field] as $locale) {
					$indexFields[] = $field . '_' . $locale;
				}
			} else {
				// This must be a static field.
				assert(isset($fieldNames['static'][$field]));
				$indexFields[] = $fieldNames['static'][$field];
			}
		}
		return $indexFields;
	}

	/**
	 * Expand the given list of fields.
	 * @param $fields array
	 * @return string A space-separated field list (e.g. to
	 *  be used in edismax's qf parameter).
	 */
	function _expandFieldList($fields) {
		$expandedFields = array();
		foreach($fields as $field) {
			$expandedFields = array_merge($expandedFields, $this->_getLocalesAndFormats($field));
		}
		return implode(' ', $expandedFields);
	}

	/**
	 * Generate the ordering parameter of a search query.
	 * @param $field string the field to order by
	 * @param $direction boolean true for ascending, false for descending
	 * @return string The ordering to be used (default: descending relevance).
	 */
	function _getOrdering($field, $direction) {
		// Translate the direction.
		$dirString = ($direction?' asc':' desc');

		// Relevance ordering.
		if ($field == 'score') {
			return $field . $dirString;
		}

		// We order by descending relevance by default.
		$defaultSort = 'score desc';

		// We have to check whether the sort field is
		// available in the index.
		$availableFields = $this->getAvailableFields('sort');
		if (!isset($availableFields[$field])) return $defaultSort;

		// Retrieve all possible sort fields.
		$fieldNames = $this->_getFieldNames('sort');

		// Order by a static (non-localized) field.
		if(isset($fieldNames['static'][$field])) {
			return $fieldNames['static'][$field] . $dirString . ',' . $defaultSort;
		}

		// Order by a localized field.
		if (in_array($field, $fieldNames['localized'])) {
			// We can only sort if the current locale is indexed.
			$currentLocale = AppLocale::getLocale();
			if (in_array($currentLocale, $availableFields[$field])) {
				// Return the localized sort field name.
				return $field . '_' . $currentLocale . '_txtsort' . $dirString . ',' . $defaultSort;
			}
		}

		// In all other cases return the default ordering.
		return $defaultSort;
	}

	/**
	 * This method encapsulates an indexing transaction (pull or push).
	 * It consists in generating the XML, transferring it to the server
	 * and marking the transferred articles as "indexed".
	 *
	 * @param $sendXmlCallback callback This function will be called
	 *  with the generated XML.
	 * @param $batchSize integer The maximum number of articles to
	 *  be returned.
	 * @param $journalId integer If given, only retrieves articles
	 *  for the given journal.
	 */
	function _indexingTransaction($sendXmlCallback, $batchSize = SOLR_INDEXING_MAX_BATCHSIZE, $journalId = null) {
		// Retrieve a batch of "changed" articles.
		import('lib.pkp.classes.db.DBResultRange');
		$range = new DBResultRange($batchSize);
		$articleDao =& DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
		$changedArticlesIterator =& $articleDao->getBySetting(
			'indexingState', SOLR_INDEXINGSTATE_DIRTY, $journalId, $range
		);
		unset($range);

		// Retrieve articles and overall count from the result set.
		$changedArticles =& $changedArticlesIterator->toArray();
		$batchCount = count($changedArticles);
		$totalCount = $changedArticlesIterator->getCount();
		unset($changedArticlesIterator);

		// Get the XML article list for this batch of articles.
		$numDeleted = null;
		$articleXml = $this->_getArticleListXml($changedArticles, $totalCount, $numDeleted);

		// Let the specific indexing implementation (pull or push)
		// transfer the generated XML.
		$numProcessed = call_user_func_array($sendXmlCallback, array(&$articleXml, $batchCount, $numDeleted));

		// Check error conditions.
		if (!is_numeric($numProcessed)) return null;
		$numProcessed = (integer)$numProcessed;
		if ($numProcessed != $batchCount) {
			$this->_serviceMessage = __(
				'plugins.generic.lucene.message.indexingIncomplete',
				array('numProcessed' => $numProcessed, 'numDeleted' => $numDeleted, 'batchCount' => $batchCount)
			);
			return null;
		}

		// Now that we are as sure as we can that the counterparty received
		// our XML, let's mark the changed articles as "updated". This "commits"
		// the indexing transaction.
		foreach($changedArticles as $indexedArticle) {
			$indexedArticle->setData('indexingState', SOLR_INDEXINGSTATE_CLEAN);
			$articleDao->updateLocaleFields($indexedArticle);
		}

		return $numProcessed;
	}

	/**
	 * Handle push indexing.
	 *
	 * This method pushes XML with index changes
	 * directly to the Solr data import handler for
	 * immediate processing.
	 *
	 * @param $articleXml string The XML with index changes
	 *  to be pushed to the Solr server.
	 * @param $batchCount integer The number of articles in
	 *  the XML list (i.e. the expected number of documents
	 *  to be indexed).
	 * @param $numDeleted integer The number of articles in
	 *  the XML list that are marked for deletion.
	 *
	 * @return integer The number of articles processed or
	 *  null if an error occured.
	 *
	 *  After an error the method SolrWebService::getServiceMessage()
	 *  will return details of the error.
	 */
	function _pushIndexingCallback(&$articleXml, $batchCount, $numDeleted) {
		if ($batchCount > 0) {
			// Make a POST request with all articles in this batch.
			$url = $this->_getDihUrl() . '?command=full-import&clean=false';
			$result = $this->_makeRequest($url, $articleXml, 'POST');
			if (is_null($result)) return null;

			// Retrieve the number of successfully indexed articles.
			$numProcessed = $this->_getDocumentsProcessed($result);
			return $numProcessed;
		} else {
			// Nothing to update.
			return 0;
		}
	}

	/**
	 * Retrieve the XML for a batch of articles to be updated.
	 *
	 * @param $articles DBResultFactory The articles to be included
	 *  in the list.
	 * @param $totalCount integer The overall number of changed articles
	 *  (not only the current batch).
	 * @param $numDeleted integer Variable to receive the number of deleted
	 *  articles.
	 *
	 * @return string The XML ready to be consumed by the Solr data
	 *  import service.
	 */
	function _getArticleListXml(&$articles, $totalCount, &$numDeleted) {
		// Create the DOM document.
		$articleDoc =& XMLCustomWriter::createDocument();
		assert(is_a($articleDoc, 'DOMDocument'));

		// Create the root node.
		$articleList =& XMLCustomWriter::createElement($articleDoc, 'articleList');
		XMLCustomWriter::appendChild($articleDoc, $articleList);

		// Run through all articles in the batch and generate an
		// XML list for them.
		$numDeleted = 0;
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		foreach($articles as $article) {
			if (!is_a($article, 'PublishedArticle')) {
				// Try to upgrade the article to a published article.
				$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($article->getId());
				if (is_a($publishedArticle, 'PublishedArticle')) {
					unset($article);
					$article =& $publishedArticle;
					unset($publishedArticle);
				}
			}
			$journal =& $this->_getJournal($article->getJournalId());

			// Check the publication state and subscription state of the article.
			if ($this->_isArticleAccessAuthorized($article)) {
				// Mark the article for update.
				$this->_addArticleXml($articleDoc, $article, $journal);
			} else {
				// Mark the article for deletion.
				$numDeleted++;
				$this->_addArticleXml($articleDoc, $article, $journal, true);
			}
			unset($journal, $article);
		}

		// Add the "has more" attribute so that the server knows
		// whether more batches may have to be pulled (useful for
		// pull indexing only).
		$hasMore = (count($articles) < $totalCount ? 'yes' : 'no');
		$articleDoc->documentElement->setAttribute('hasMore', $hasMore);

		// Return XML.
		return XMLCustomWriter::getXml($articleDoc);
	}

	/**
	 * Add the metadata XML of a single article to an
	 * XML article list.
	 *
	 * @param $articleDoc DOMDocument
	 * @param $article PublishedArticle
	 * @param $journal Journal
	 * @param $markToDelete boolean If true the returned XML
	 *  will only contain a deletion marker.
	 */
	function _addArticleXml(&$articleDoc, &$article, &$journal, $markToDelete = false) {
		assert(is_a($article, 'Article'));

		// Get the root node of the list.
		assert(is_a($articleDoc, 'DOMDocument'));
		$articleList =& $articleDoc->documentElement;

		// Create a new article node.
		$articleNode =& XMLCustomWriter::createElement($articleDoc, 'article');

		// Add ID information.
		XMLCustomWriter::setAttribute($articleNode, 'id', $article->getId());
		XMLCustomWriter::setAttribute($articleNode, 'sectionId', $article->getSectionId());
		XMLCustomWriter::setAttribute($articleNode, 'journalId', $article->getJournalId());
		XMLCustomWriter::setAttribute($articleNode, 'instId', $this->_instId);

		// Set the load action.
		$loadAction = ($markToDelete ? 'delete' : 'replace');
		XMLCustomWriter::setAttribute($articleNode, 'loadAction', $loadAction);
		XMLCustomWriter::appendChild($articleList, $articleNode);

		// The XML for an article marked to be deleted contains no metadata.
		if ($markToDelete) return;
		assert(is_a($article, 'PublishedArticle'));

		// Add authors.
		$authors = $article->getAuthors();
		if (!empty($authors)) {
			$authorList =& XMLCustomWriter::createElement($articleDoc, 'authorList');
			foreach ($authors as $author) { /* @var $author Author */
				XMLCustomWriter::createChildWithText($articleDoc, $authorList, 'author', $author->getFullName(true));
			}
			XMLCustomWriter::appendChild($articleNode, $authorList);
		}

		// We need the request to retrieve locales and build URLs.
		$request =& PKPApplication::getRequest();

		// Get all supported locales.
		$site =& $request->getSite();
		$supportedLocales = $site->getSupportedLocales() + array_keys($journal->getSupportedLocaleNames());
		assert(!empty($supportedLocales));

		// Add titles.
		$titleList =& XMLCustomWriter::createElement($articleDoc, 'titleList');
		// Titles are used for sorting, we therefore need
		// them in all supported locales.
		assert(!empty($supportedLocales));
		foreach($supportedLocales as $locale) {
			$localizedTitle = $article->getLocalizedTitle($locale);
			if (!is_null($localizedTitle)) {
				// Add the localized title.
				$titleNode =& XMLCustomWriter::createChildWithText($articleDoc, $titleList, 'title', $localizedTitle);
				XMLCustomWriter::setAttribute($titleNode, 'locale', $locale);

				// If the title does not exist in the given locale
				// then use the localized title for sorting only.
				$title = $article->getTitle($locale);
				$sortOnly = (empty($title) ? 'true' : 'false');
				XMLCustomWriter::setAttribute($titleNode, 'sortOnly', $sortOnly);
			}
		}
		XMLCustomWriter::appendChild($articleNode, $titleList);

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
					if (!empty($coverage)) $coverage .= '; ';
					$coverage .= $coverageChron[$locale];
				}
				if (isset($coverageSample[$locale])) {
					if (!empty($coverage)) $coverage .= '; ';
					$coverage .= $coverageSample[$locale];
				}
				$coverageNode =& XMLCustomWriter::createChildWithText($articleDoc, $coverageList, 'coverage', $coverage);
				XMLCustomWriter::setAttribute($coverageNode, 'locale', $locale);
			}
			XMLCustomWriter::appendChild($articleNode, $coverageList);
		}

		// Add journal titles.
		$journalTitleList =& XMLCustomWriter::createElement($articleDoc, 'journalTitleList');
		// Journal titles are used for sorting, we therefore need
		// them in all supported locales.
		foreach($supportedLocales as $locale) {
			$localizedTitle = $journal->getLocalizedTitle($locale);
			if (!is_null($localizedTitle)) {
				// Add the localized title.
				$journalTitleNode =& XMLCustomWriter::createChildWithText($articleDoc, $journalTitleList, 'journalTitle', $localizedTitle);
				XMLCustomWriter::setAttribute($journalTitleNode, 'locale', $locale);

				// If the title does not exist in the given locale
				// then use the localized title for sorting only.
				$journalTitle = $journal->getTitle($locale);
				$sortOnly = (empty($journalTitle) ? 'true' : 'false');
				XMLCustomWriter::setAttribute($journalTitleNode, 'sortOnly', $sortOnly);
			}
		}
		XMLCustomWriter::appendChild($articleNode, $journalTitleList);

		// Add publication dates.
		$publicationDate = $article->getDatePublished();
		if (!empty($publicationDate)) {
			// Transform and store article publication date.
			$publicationDate = $this->_convertDate($publicationDate);
			$dateNode =& XMLCustomWriter::createChildWithText($articleDoc, $articleNode, 'publicationDate', $publicationDate);
		}

		$issueId = $article->getIssueId();
		if (is_numeric($issueId)) {
			$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issue =& $issueDao->getIssueById($issueId);
			if (is_a($issue, 'Issue')) {
				$issuePublicationDate = $issue->getDatePublished();
				if (!empty($issuePublicationDate)) {
					// Transform and store issue publication date.
					$issuePublicationDate = $this->_convertDate($issuePublicationDate);
					$dateNode =& XMLCustomWriter::createChildWithText($articleDoc, $articleNode, 'issuePublicationDate', $issuePublicationDate);
				}
			}
		}

		// We need the router to build file URLs.
		$router =& $request->getRouter(); /* @var $router PageRouter */

		// Add galley files
		$fileDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$galleys =& $fileDao->getGalleysByArticle($article->getId());
		$galleyList = null;
		foreach ($galleys as $galley) { /* @var $galley ArticleGalley */
			$locale = $galley->getLocale();
			$galleyUrl = $router->url($request, $journal->getPath(), 'article', 'download', array(intval($article->getId()), intval($galley->getId())));
			if (!empty($locale) && !empty($galleyUrl)) {
				if (is_null($galleyList)) {
					$galleyList =& XMLCustomWriter::createElement($articleDoc, 'galleyList');
				}
				$galleyNode =& XMLCustomWriter::createElement($articleDoc, 'galley');
				XMLCustomWriter::setAttribute($galleyNode, 'locale', $locale);
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

			$suppFileUrl = $router->url($request, $journal->getPath(), 'article', 'downloadSuppFile', array(intval($article->getId()), intval($suppFile->getId())));

			if (!empty($locale) && !empty($suppFileUrl)) {
				if (is_null($suppFileList)) {
					$suppFileList =& XMLCustomWriter::createElement($articleDoc, 'suppFileList');
				}
				$suppFileNode =& XMLCustomWriter::createElement($articleDoc, 'suppFile');
				XMLCustomWriter::setAttribute($suppFileNode, 'locale', $locale);
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
						$suppFileMDListNode =& XMLCustomWriter::createElement($articleDoc, $field . 'List');
						foreach($data as $locale => $value) {
							$suppFileMDNode =& XMLCustomWriter::createChildWithText($articleDoc, $suppFileMDListNode, $field, $value);
							XMLCustomWriter::setAttribute($suppFileMDNode, 'locale', $locale);
							unset($suppFileMDNode);
						}
						XMLCustomWriter::appendChild($suppFileNode, $suppFileMDListNode);
						unset($suppFileMDListNode);
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
	}

	/**
	 * Convert a date from local time (unix timestamp
	 * or ISO date string) to UTC time as understood
	 * by solr.
	 *
	 * NB: Using intermediate unix timestamps can be
	 * a problem in older PHP versions, especially on
	 * Windows where negative timestamps are not supported.
	 *
	 * As Solr requires PHP5 that should not be a big
	 * problem in practice, except for electronic
	 * publications that go back until earlier than 1901.
	 * It does not seem probable that such a situation
	 * could realistically arise with OJS.
	 *
	 * @param $timestamp int|string Unix timestamp or local ISO time.
	 * @return string ISO UTC timestamp
	 */
	function _convertDate($timestamp) {
		if (is_numeric($timestamp)) {
			// Assume that this is a unix timestamp.
			$timestamp = (integer) $timestamp;
		} else {
			// Assume that this is an ISO timestamp.
			$timestamp = strtotime($timestamp);
		}

		// Convert to UTC as understood by solr.
		return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
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
		$nodeList = $result->query('/response/lst[@name="responseHeader"]/int[@name="status"]');
		if($nodeList->length != 1) return false;
		$resultNode = $nodeList->item(0);
		if ($resultNode->textContent === '0') return true;
	}

	/**
	 * Retrieve the number of indexed documents
	 * from a DIH response XML
	 * @param $result DOMXPath
	 * @return integer
	 */
	function _getDocumentsProcessed($result) {
		// Return the number of documents that were indexed.
		$nodeList = $result->query('/response/lst[@name="statusMessages"]/str[@name="Total Documents Processed"]');
		assert($nodeList->length == 1);
		$resultNode = $nodeList->item(0);
		assert(is_numeric($resultNode->textContent));
		return (integer)$resultNode->textContent;
	}

	/**
	 * Set the query parameters for a search query.
	 *
	 * @param $fieldList string A list of fields to be queried, separated by '|'.
	 * @param $searchPhrase string The search phrase to be added.
	 * @param $params array The existing query parameters.
	 * @param $spellcheck boolean Whether to switch spellchecking on.
	 */
	function _setQuery($fieldList, $searchPhrase, $spellcheck = false) {
		// Expand the field list to all locales and formats.
		$fieldList = $this->_expandFieldList(explode('|', $fieldList));

		// Add the subquery to the query parameters.
		$params = array(
			'defType' => 'edismax',
			'qf' => $fieldList,
			// NB: mm=1 is equivalent to implicit OR
			// This deviates from previous OJS practice, please see
			// http://pkp.sfu.ca/wiki/index.php/OJSdeSearchConcept#Query_Parser
			// for the rationale of this change.
			'mm' => '1'
		);

		// Only set a query if we have one.
		if (!empty($searchPhrase)) {
			$params['q'] = $searchPhrase;
		}

		// Ask for alternative spelling suggestions.
		if ($spellcheck) {
			$params['spellcheck'] = 'on';
		}

		return $params;
	}

	/**
	 * Add a subquery to the search query.
	 *
	 * NB: subqueries do not support collation (for alternative
	 * spelling suggestions).
	 *
	 * @param $fieldList string A list of fields to be queried, separated by '|'.
	 * @param $searchPhrase string The search phrase to be added.
	 * @param $params array The existing query parameters.
	 */
	function _addSubquery($fieldList, $searchPhrase, $params) {
		// Get the list of fields to be queried.
		$fields = explode('|', $fieldList);

		// Expand the field list to all locales and formats.
		$fieldList = $this->_expandFieldList($fields);

		// Determine a query parameter name for this field list.
		if (count($fields) == 1) {
			// If we have a single field in the field list then
			// use the field name as alias.
			$fieldAlias = array_pop($fields);
		} else {
			// Use a generic name for multi-field searches.
			$fieldAlias = 'multi';
		}
		$fieldAlias = "q.$fieldAlias";

		// Make sure that the alias is unique.
		$fieldSuffix = '';
		while (isset($params[$fieldAlias . $fieldSuffix])) {
			if (empty($fieldSuffix)) $fieldSuffix = 1;
			$fieldSuffix ++;
		}
		$fieldAlias = $fieldAlias . $fieldSuffix;

		// Construct a subquery.
		// NB: mm=1 is equivalent to implicit OR
		// This deviates from previous OJS practice, please see
		// http://pkp.sfu.ca/wiki/index.php/OJSdeSearchConcept#Query_Parser
		// for the rationale of this change.
		$subQuery = "+_query_:\"{!edismax mm=1 qf='$fieldList' v=\$$fieldAlias}\"";

		// Add the subquery to the query parameters.
		if (isset($params['q'])) {
			$params['q'] .= ' ' . $subQuery;
		} else {
			$params['q'] = $subQuery;
		}
		$params[$fieldAlias] = $searchPhrase;
		return $params;
	}

	/**
	 * Translate query keywords.
	 * @param $searchPhrase string
	 * @return The translated search phrase.
	 */
	function _translateSearchPhrase($searchPhrase, $backwards = false) {
		static $queryKeywords;

		if (is_null($queryKeywords)) {
			// Query keywords.
			$queryKeywords = array(
				String::strtoupper(__('search.operator.not')) => 'NOT',
				String::strtoupper(__('search.operator.and')) => 'AND',
				String::strtoupper(__('search.operator.or')) => 'OR'
			);
		}

		if ($backwards) {
			$translationTable = array_flip($queryKeywords);
		} else {
			$translationTable = $queryKeywords;
		}

		// Translate the search phrase.
		foreach($translationTable as $translateFrom => $translateTo) {
			$searchPhrase = String::regexp_replace("/(^|\s)$translateFrom(\s|$)/i", "\\1$translateTo\\2", $searchPhrase);
		}

		return $searchPhrase;
	}

	/**
	 * Create the edismax query parameters from
	 * a search request.
	 * @param $searchRequest SolrSearchRequest
	 * @return array|null A parameter array or null if something
	 *  went wrong.
	 */
	function _getSearchQueryParameters(&$searchRequest) {
		// Pre-filter and translate query phrases.
		$subQueries = array();
		foreach($searchRequest->getQuery() as $fieldList => $searchPhrase) {
			// Ignore empty search phrases.
			if (empty($fieldList) || empty($searchPhrase)) continue;

			// Translate query keywords.
			$subQueries[$fieldList] = $this->_translateSearchPhrase($searchPhrase);
		}

		// We differentiate between simple and multi-phrase queries.
		$subQueryCount = count($subQueries);
		if ($subQueryCount == 1) {
			// Use a simplified query that allows us to provide
			// alternative spelling suggestions.
			$fieldList = key($subQueries);
			$searchPhrase = current($subQueries);
			$params = $this->_setQuery($fieldList, $searchPhrase, $searchRequest->getSpellcheck());
		} elseif ($subQueryCount > 1) {
			// Initialize the search request parameters.
			$params = array();
			foreach ($subQueries as $fieldList => $searchPhrase) {
				// Construct the sub-query and add it to the search query and params.
				$params = $this->_addSubquery($fieldList, $searchPhrase, $params, true);
			}
		}

		// Add the installation ID as a filter query.
		$params['fq'] = array('inst_id:"' . $this->_instId . '"');

		// Add a range search on the publication date (if set).
		$fromDate = $searchRequest->getFromDate();
		$toDate = $searchRequest->getToDate();
		if (!(empty($fromDate) && empty($toDate))) {
			if (empty($fromDate)) {
				$fromDate = '*';
			} else {
				$fromDate = $this->_convertDate($fromDate);
			}
			if (empty($toDate)) {
				$toDate = '*';
			} else {
				$toDate = $this->_convertDate($toDate);
			}
			// We do not cache this filter as reuse seems improbable.
			$params['fq'][] = "{!cache=false}publicationDate_dt:[$fromDate TO $toDate]";
		}

		// Add the journal as a filter query (if set).
		$journal =& $searchRequest->getJournal();
		if (is_a($journal, 'Journal')) {
			$params['fq'][] = 'journal_id:"' . $this->_instId . '-' . $journal->getId() . '"';
		}
		return $params;
	}

	/**
	 * Retrieve auto-suggestions from the suggester service.
	 * @param $url string
	 * @param $userInput string
	 * @param $fieldName string
	 * @return array The generated suggestions.
	 */
	function _getSuggesterAutosuggestions($url, $userInput, $fieldName) {
		// Select the dictionary appropriate for the field
		// the user input is coming from.
		if ($fieldName == 'query') {
			$dictionary = 'all';
		} else {
			$dictionary = $fieldName;
		}

		// Generate parameters for the suggester component.
		$params = array(
			'q' => $userInput,
			'spellcheck.dictionary' => $dictionary
		);

		// Make the request.
		$response = $this->_makeRequest($url, $params);
		if (!is_a($response, 'DOMXPath')) return array();

		// Extract suggestions for the last word in the query.
		$nodeList = $response->query('//lst[@name="suggestions"]/lst[last()]');
		if ($nodeList->length == 0) return array();
		$suggestionNode = $nodeList->item(0);
		foreach($suggestionNode->childNodes as $childNode) {
			$nodeType = $childNode->attributes->getNamedItem('name')->value;
			switch($nodeType) {
				case 'startOffset':
				case 'endOffset':
					$$nodeType = ((int)$childNode->textContent);
					break;

				case 'suggestion':
					$suggestions = array();
					foreach($childNode->childNodes as $suggestionNode) {
						$suggestions[] = $suggestionNode->textContent;
					}
					break;
			}
		}

		// Check whether the suggestion really concerns the
		// last word of the user input.
		if (!(isset($startOffset) && isset($endOffset)
			&& String::strlen($userInput) == $endOffset)) return array();

		// Replace the last word in the user input
		// with the suggestions maintaining case.
		foreach($suggestions as &$suggestion) {
			$suggestion = $userInput . String::substr($suggestion, $endOffset - $startOffset);
		}
		return $suggestions;
	}

	/**
	 * Retrieve auto-suggestions from the faceting service.
	 * @param $url string
	 * @param $searchRequest SolrSearchRequest
	 * @param $userInput string
	 * @param $fieldName string
	 * @return array The generated suggestions.
	 */
	function _getFacetingAutosuggestions($url, $searchRequest, $userInput, $fieldName) {
		// Remove special characters from the user input.
		$searchTerms = strtr($userInput, '"()+-|&!', '        ');

		// Cut off the last search term.
		$searchTerms = explode(' ', $searchTerms);
		$facetPrefix = array_pop($searchTerms);
		if (empty($facetPrefix)) return array();

		// Use the remaining search query to pre-filter
		// facet results. This may be an invalid query
		// but edismax will deal gracefully with syntax
		// errors.
		$userInput = String::substr($userInput, 0, -String::strlen($facetPrefix));
		switch ($fieldName) {
			case 'query':
				// The 'query' filter goes agains all fields.
				$solrFields = array_values(ArticleSearch::getIndexFieldMap());
				break;

			case 'indexTerms':
				// The 'index terms' filter goes against keyword index fields.
				$solrFields = array('discipline', 'subject', 'type', 'coverage');
				break;

			default:
				// All other filters can be used directly.
				$solrFields = array($fieldName);
		}
		$solrFieldString = implode('|', $solrFields);
		$searchRequest->addQueryFieldPhrase($solrFieldString, $userInput);

		// Construct the main query.
		$params = $this->_getSearchQueryParameters($searchRequest);
		if (!isset($params['q'])) {
			// Use a catch-all query in case we have no limiting
			// search.
			$params['q'] = '*:*';
		}
		if ($fieldName == 'query') {
			$params['facet.field'] = 'default_spell';
		} else {
			$params['facet.field'] = $fieldName . '_spell';
		}
		$facetPrefixLc = String::strtolower($facetPrefix);
		$params['facet.prefix'] = $facetPrefixLc;

		// Make the request.
		$response = $this->_makeRequest($url, $params);
		if (!is_a($response, 'DOMXPath')) return array();

		// Extract term suggestions.
		$nodeList = $response->query('//lst[@name="facet_fields"]/lst/int/@name');
		if ($nodeList->length == 0) return array();
		$termSuggestions = array();
		foreach($nodeList as $childNode) {
			$termSuggestions[] = $childNode->value;
		}

		// Add the term suggestion to the remaining user input.
		$suggestions = array();
		foreach($termSuggestions as $termSuggestion) {
			// Restore case if possible.
			if (strpos($termSuggestion, $facetPrefixLc) === 0) {
				$termSuggestion = $facetPrefix . String::substr($termSuggestion, String::strlen($facetPrefix));
			}
			$suggestions[] = $userInput . $termSuggestion;
		}
		return $suggestions;
	}

	/**
	 * Check whether access to the given article
	 * is authorized to the requesting party (i.e. the
	 * Solr server).
	 *
	 * @param $article Article
	 * @return boolean True if authorized, otherwise false.
	 */
	function _isArticleAccessAuthorized(&$article) {
		// Did we get a published article?
		if (!is_a($article, 'PublishedArticle')) return false;

		// Get the article's journal.
		$journal =& $this->_getJournal($article->getJournalId());
		if (!is_a($journal, 'Journal')) return false;

		// Get the article's issue.
		$issue =& $this->_getIssue($article->getIssueId(), $journal->getId());
		if (!is_a($issue, 'Issue')) return false;

		// Only index published articles.
		if (!$issue->getPublished() || $article->getStatus() != STATUS_PUBLISHED) return false;

		// Make sure the requesting party is authorized to acces the article/issue.
		import('classes.issue.IssueAction');
		$subscriptionRequired = IssueAction::subscriptionRequired($issue, $journal);
		if ($subscriptionRequired) {
			$isSubscribedDomain = IssueAction::subscribedDomain($journal, $issue->getId(), $article->getId());
			if (!$isSubscribedDomain) return false;
		}

		// All checks passed successfully - allow access.
		return true;
	}
}

?>
