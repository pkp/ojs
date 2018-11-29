<?php

/**
 * @file plugins/generic/lucene/LuceneHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LuceneHandler
 * @ingroup plugins_generic_lucene
 *
 * @brief Handle lucene AJAX and XML requests (auto-completion, pull indexation, etc.)
 */

import('classes.handler.Handler');
import('plugins.generic.lucene.classes.SolrWebService');
import('lib.pkp.classes.core.JSONMessage');
import('classes.search.ArticleSearch');

class LuceneHandler extends Handler {

	/**
	 * Constructor
	 * @param $request Request
	 */
	function __construct($request) {
		parent::__construct();
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));

		import('classes.security.authorization.OjsJournalMustPublishPolicy');
		$this->addPolicy(new OjsJournalMustPublishPolicy($request));

		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Public operations
	//
	/**
	 * AJAX request for search query auto-completion.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function queryAutocomplete($args, $request) {
		$this->validate(null, $request);

		// Check whether auto-suggest is enabled.
		$suggestionList = array();
		$lucenePlugin = $this->_getLucenePlugin();
		$enabled = (bool)$lucenePlugin->getSetting(0, 'autosuggest');
		if ($enabled) {
			// Retrieve search criteria from the user input.
			$articleSearch = new ArticleSearch();
			$searchFilters = $articleSearch->getSearchFilters($request);

			// Get the autosuggest input and remove it from
			// the filter array.
			$autosuggestField = $request->getUserVar('searchField');
			$userInput = $searchFilters[$autosuggestField];
			if (isset($searchFilters[$autosuggestField])) {
				unset($searchFilters[$autosuggestField]);
			}

			// Instantiate a search request.
			$searchRequest = new SolrSearchRequest();
			$searchRequest->setJournal($searchFilters['searchJournal']);
			$searchRequest->setFromDate($searchFilters['fromDate']);
			$searchRequest->setToDate($searchFilters['toDate']);
			$keywords = $articleSearch->getKeywordsFromSearchFilters($searchFilters);
			$searchRequest->addQueryFromKeywords($keywords);

			// Get the web service.
			$solrWebService = $lucenePlugin->getSolrWebService(); /* @var $solrWebService SolrWebService */
			$suggestions = $solrWebService->getAutosuggestions(
				$searchRequest, $autosuggestField, $userInput,
				(int)$lucenePlugin->getSetting(0, 'autosuggestType')
			);

			// Prepare a suggestion list as understood by the
			// autocomplete JS handler.
			foreach($suggestions as $suggestion) {
				$suggestionList[] = array('label' => $suggestion, 'value' => $suggestion);
			}
		}

		// Return the suggestions as JSON message.
		return new JSONMessage(true, $suggestionList);
	}

	/**
	 * If pull-indexing is enabled then this handler returns
	 * article metadata in a formate that can be consumed by
	 * the Solr data import handler.
	 * @param $args array
	 * @param $request Request
	 * @return JSON string
	 */
	function pullChangedArticles($args, $request) {
		$this->validate(null, $request);

		// Do not allow access to this operation from journal context.
		$router = $request->getRouter();
		$journal = $router->getContext($request);
		if (!is_null($journal)) {
			// Redirect to the index context. We do this so that providers
			// can secure a single entry point when providing subscription-only
			// content.
			$request->redirect('index', 'lucene', 'pullChangedArticles');
		}

		// Die if pull indexing is disabled.
		$lucenePlugin = $this->_getLucenePlugin();
		if (!$lucenePlugin->getSetting(0, 'pullIndexing')) die(__('plugins.generic.lucene.message.pullIndexingDisabled'));

		// Execute the pull indexing transaction.
		$solrWebService = $lucenePlugin->getSolrWebService(); /* @var $solrWebService SolrWebService */
		$solrWebService->pullChangedArticles(
			array($this, 'pullIndexingCallback'), SOLR_INDEXING_MAX_BATCHSIZE
		);
	}

	/**
	 * If the "ranking-by-metric" feature is enabled then this
	 * handler returns a file with normalized boost data.
	 * @param $args array
	 * @param $request Request
	 */
	function usageMetricBoost($args, $request) {
		$this->validate(null, $request);

		// We return a text file.
		header('Content-type: text/plain');

		// Make sure that we got a parameter telling us
		// whether to generate all-time or monthly statistics.
		$filter = $request->getUserVar('filter');
		if ($filter != 'all' && $filter != 'month') return;

		// Only allow external report generation in the pull scenario.
		$lucenePlugin = $this->_getLucenePlugin();
		if (!$lucenePlugin->getSetting(0, 'pullIndexing')) return;

		// Generate (and output) the report.
		if (!$lucenePlugin->generateBoostFile($filter));
	}


	//
	// Public methods
	//
	/**
	 * Return XML with index changes to the Solr server
	 * where it will be stored for later processing.
	 *
	 * @param $articleXml string The XML with index changes
	 *  to be transferred to the Solr server.
	 * @param $batchCount integer The number of articles in
	 *  the XML list (i.e. the expected number of documents
	 *  to be indexed).
	 * @param $numDeleted integer The number of articles in
	 *  the XML list that are marked for deletion.
	 *
	 * @return integer The number of articles processed.
	 */
	function pullIndexingCallback($articleXml, $batchCount, $numDeleted) {
		// Flush the XML to the Solr server to make sure it
		// arrives there before we commit our transaction.
		echo $articleXml;
		flush();

		// We assume that when the flush succeeds that
		// all changed documents will eventually be indexed.
		// By implementing a rejection mechanism on the server
		// we make sure this actually happens (or that we at
		// least realize if something goes wrong). If this
		// is not working in practice then we'll have to
		// implement a real application-level two-way handshake.
		return $batchCount;
	}


	//
	// Private helper methods
	//
	/**
	 * Get the lucene plugin object
	 * @return LucenePlugin
	 */
	function _getLucenePlugin() {
		return PluginRegistry::getPlugin('generic', LUCENE_PLUGIN_NAME);
	}
}


