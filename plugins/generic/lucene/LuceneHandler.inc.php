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

define('LUCENE_PLUGIN_INDEXINGSTATE_DIRTY', true);
define('LUCENE_PLUGIN_INDEXINGSTATE_CLEAN', false);


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


	//
	// Public operations
	//
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

	/**
	 * If pull-indexing is enabled then this handler returns
	 * article metadata in a formate that can be consumed by
	 * the Solr data import handler.
	 * @param $args array
	 * @param $request Request
	 * @return JSON string
	 */
	function pullChangedArticles($args, &$request) {
		$this->validate(null, $request);

		// Do not allow access to this operation from journal context.
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);
		if (!is_null($journal)) {
			// Redirect to the index context. We do this so that providers
			// can secure a single entry point when providing subscription-only
			// content.
			$request->redirect('index', 'lucene', 'pullChangedArticles');
		}

		// Die if pull indexing is disabled.
		$lucenePlugin =& $this->_getLucenePlugin();
		if (!$lucenePlugin->getSetting(0, 'pullindexing')) die(__('plugins.generic.lucene.message.pullIndexingDisabled'));

		// Get the web service.
		$solrWebService =& $lucenePlugin->getSolrWebService(); /* @var $solrWebService SolrWebService */

		// Create an empty article list XML.
		$articleDoc =& XMLCustomWriter::createDocument(); /* @var DOMDocument */
		$articleList =& XMLCustomWriter::createElement($articleDoc, 'articleList');
		XMLCustomWriter::appendChild($articleDoc, $articleList);

		// Retrieve all "dirty" articles.
		$articleDao =& DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		$changedArticles = $articleDao->getBySetting('indexingState', LUCENE_PLUGIN_INDEXINGSTATE_DIRTY);
		$hasMore = false;
		$indexedArticles = 0;
		foreach($changedArticles as $changedArticle) {
			// Make sure that we do not exceed the allowed
			// batch size.
			if ($indexedArticles >= SOLR_INDEXING_BATCHSIZE) {
				$hasMore = true;
				break;
			}

			// Try to upgrade the article to a published article.
			$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($changedArticle->getId());
			if (is_a($publishedArticle, 'PublishedArticle')) {
				$changedArticle =& $publishedArticle;
			}

			// Check the publication state and subscription state of the article.
			if ($this->_articleAccessAuthorized($request, $changedArticle)) {
				// Mark the article for update.
				$journal =& $this->_getJournal($changedArticle->getJournalId());
				$articleDoc =& $solrWebService->getArticleXml($changedArticle, $journal, $articleDoc);
			} else {
				// Mark the article for deletion.
				$articleNode =& XMLCustomWriter::createElement($articleDoc, 'article');
				XMLCustomWriter::setAttribute($articleNode, 'id', $changedArticle->getId());
				XMLCustomWriter::setAttribute($articleNode, 'journalId', $changedArticle->getJournalId());
				XMLCustomWriter::setAttribute($articleNode, 'instId', $solrWebService->getInstId());
				XMLCustomWriter::setAttribute($articleNode, 'loadAction', 'delete');
				XMLCustomWriter::appendChild($articleList, $articleNode);
			}

			// Count the indexed articles so that we can check the number
			// against the allowed batch size.
			$indexedArticles++;
		}

		// Add the "has more" attribute so that the server knows
		// whether this was the last batch.
		assert(is_a($articleDoc, 'DOMDocument'));
		$articleDoc->documentElement->setAttribute('hasMore', ($hasMore ? 'yes' : 'no'));

		// Flush XML.
		echo XMLCustomWriter::getXml($articleDoc);
		flush();

		// Now that we are as sure as we can that the counterparty received
		// our XML, let's mark the articles "clean". The worst that could
		// happen now is that an article could not be marked clean. This is
		// not a problem as our indexing process is idempotent.
		foreach($changedArticles as $changedArticle) {
			$changedArticle->setData('indexingState', LUCENE_PLUGIN_INDEXINGSTATE_CLEAN);
			$articleDao->updateLocaleFields($changedArticle);
		}
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

	/**
	 * Retrieve a journal (possibly from the cache).
	 * @param $journalId int
	 * @return Journal
	 */
	function &_getJournal($journalId) {
		static $journalCache;

		if (isset($journalCache[$journalId])) {
			$journal =& $journalCache[$journalId];
		} else {
			$journalDao =& DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
			$journal =& $journalDao->getById($journalId);
			$journalCache[$journalId] =& $journal;
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
		static $issueCache;

		if (isset($issueCache[$issueId])) {
			$issue =& $issueCache[$issueId];
		} else {
			$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issue =& $issueDao->getIssueById($issueId, $journalId, true);
			$issueCache[$issueId] =& $issue;
		}

		return $issue;
	}

	/**
	 * Check whether access to the given article
	 * is authorized to the requesting party (i.e. the
	 * Solr server).
	 *
	 * @param $request Request
	 * @param $article Article
	 * @return boolean True if authorized, otherwise false.
	 */
	function _articleAccessAuthorized(&$request, &$article) {
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
