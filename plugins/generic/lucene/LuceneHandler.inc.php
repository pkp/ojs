<?php

/**
 * @file plugins/generic/lucene/LuceneHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
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
	function LuceneHandler(&$request) {
		parent::Handler();
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);
		$this->addCheck(new HandlerValidatorCustom($this, false, null, null, create_function('$journal', 'return !$journal || $journal->getSetting(\'publishingMode\') != PUBLISHING_MODE_NONE;'), array($journal)));
	}

	/**
	 * AJAX request for search query auto-completion.
	 * @param $args array
	 * @param $request Request
	 * @return JSON string
	 */
	function queryAutocomplete($args, &$request) {
		$this->validate(null, $request);

		// Check whether auto-suggest is enabled.
		$suggestionList = array();
		$lucenePlugin =& $this->_getLucenePlugin();
		$enabled = (bool)$lucenePlugin->getSetting(0, 'autosuggest');
		if ($enabled) {
			// Retrieve search criteria from the user input.
			$searchFilters = ArticleSearch::getSearchFilters($request);

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
			$keywords = ArticleSearch::getKeywordsFromSearchFilters($searchFilters);
			$searchRequest->addQueryFromKeywords($keywords);

			// Get the web service.
			$solrWebService =& $lucenePlugin->getSolrWebService(); /* @var $solrWebService SolrWebService */
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
		$json = new JSONMessage(true, $suggestionList);
		return $json->getString();
	}


	//
	// Private helper methods
	//
	/**
	 * Get the lucene plugin object
	 * @return LucenePlugin
	 */
	function &_getLucenePlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', LUCENE_PLUGIN_NAME);
		return $plugin;
	}
}

?>
