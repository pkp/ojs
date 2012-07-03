<?php

/**
 * @file plugins/generic/lucene/LucenePlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LucenePlugin
 * @ingroup plugins_generic_lucene
 *
 * @brief Lucene plugin class
 */


import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.lucene.classes.SolrWebService');


class LucenePlugin extends GenericPlugin {

	/** @var SolrWebService */
	var $_solrWebService;

	//
	// Constructor
	//
	function LucenePlugin() {
		parent::GenericPlugin();
	}


	//
	// Implement template methods from PKPPlugin.
	//
	/**
	 * @see PKPPlugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			// Register callbacks (controller-level).
			HookRegistry::register('ArticleSearch::retrieveResults', array(&$this, 'callbackRetrieveResults'));
			HookRegistry::register('ArticleSearchIndex::deleteTextIndex', array(&$this, 'callbackDeleteTextIndex'));
			HookRegistry::register('ArticleSearchIndex::indexArticleFiles', array(&$this, 'callbackIndexArticleFiles'));
			HookRegistry::register('ArticleSearchIndex::indexArticleMetadata', array(&$this, 'callbackIndexArticleMetadata'));
			HookRegistry::register('ArticleSearchIndex::indexSuppFileMetadata', array(&$this, 'callbackIndexSuppFileMetadata'));
			HookRegistry::register('ArticleSearchIndex::updateFileIndex', array(&$this, 'callbackUpdateFileIndex'));
			HookRegistry::register('ArticleSearchIndex::rebuildIndex', array(&$this, 'callbackRebuildIndex'));

			// Register callbacks (view-level).
			HookRegistry::register('TemplateManager::display',array(&$this, 'callbackTemplateDisplay'));
			HookRegistry::register('Templates::Search::SearchResults::PreResults', array($this, 'callbackTemplatePreResults'));

			// Instantiate the web service.
			$searchHandler = $this->getSetting(0, 'searchEndpoint');
			$username = $this->getSetting(0, 'username');
			$password = $this->getSetting(0, 'password');
			$instId = $this->getSetting(0, 'instId');
			$this->_solrWebService = new SolrWebService($searchHandler, $username, $password, $instId);
		}
		return $success;
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.lucene.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.lucene.description');
	}

	/**
	 * @see PKPPlugin::getInstallSitePluginSettingsFile()
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * @see PKPPlugin::isSitePlugin()
	 */
	function isSitePlugin() {
		return true;
	}

	/**
	 * @see PKPPlugin::getTemplatePath()
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}

	//
	// Implement template methods from GenericPlugin.
	//
	/**
	 * @see GenericPlugin::getManagementVerbs()
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.lucene.settings'));
		}
		return parent::getManagementVerbs($verbs);
	}

	/**
	 * @see GenericPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;

		switch ($verb) {
			case 'settings':
				$templateMgr =& TemplateManager::getManager();
				$this->import('classes.form.LuceneSettingsForm');
				$form = new LuceneSettingsForm($this);
				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, 'manager', 'plugins', 'generic');
						return false;
					} else {
						$this->_setBreadCrumbs();
						$form->display();
					}
				} else {
					$this->_setBreadCrumbs();
					$form->initData();
					$form->display();
				}
				return true;

			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}


	//
	// Public Search API
	//
	/**
	 * @see ArticleSearch::retrieveResults()
	 */
	function callbackRetrieveResults($hookName, $params) {
		assert($hookName == 'ArticleSearch::retrieveResults');

		// Unpack the parameters.
		list($journal, $search, $fromDate, $toDate, $page, $itemsPerPage, $dummy) = $params;
		$totalResults =& $params[6];

		// Translate the search to the Lucene search fields.
		$searchTypes = array(
			ARTICLE_SEARCH_AUTHOR => 'authors',
			ARTICLE_SEARCH_TITLE => 'title',
			ARTICLE_SEARCH_ABSTRACT => 'abstract',
			ARTICLE_SEARCH_DISCIPLINE => 'discipline',
			ARTICLE_SEARCH_SUBJECT => 'subject',
			ARTICLE_SEARCH_TYPE => 'type',
			ARTICLE_SEARCH_COVERAGE => 'coverage',
			ARTICLE_SEARCH_GALLEY_FILE => 'galleyFullText',
			ARTICLE_SEARCH_SUPPLEMENTARY_FILE => 'suppFileFullText',
			ARTICLE_SEARCH_INDEX_TERMS => 'indexTerms'
		);

		// Search keywords.
		$searchKeywords = array(
			String::strtolower(__('search.operator.not')) => 'NOT',
			String::strtolower(__('search.operator.and')) => 'AND',
			String::strtolower(__('search.operator.or')) => 'OR'
		);

		$translatedSearch = array();
		foreach($search as $type => $query) {
			// Translate search keywords.
			foreach($searchKeywords as $searchKeyword => $translation) {
				$query = String::regexp_replace("/(^|\s)$searchKeyword(\s|$)/i", "\\1$translation\\2", $query);
			}
			// Translate the search key.
			if (empty($type)) {
				$translatedSearch['all'] = $query;
			} else {
				assert(isset($searchTypes[$type]));
				$translatedSearch[$searchTypes[$type]] = $query;
			}
		}

		// Transform the date format. Lucene does not accept localized ISO-8601 dates
		// so we cannot use date('c',...).
		if (!empty($fromDate)) $fromDate = str_replace(' ', 'T', $fromDate) . 'Z';
		if (!empty($toDate)) $toDate = str_replace(' ', 'T', $fromDate) . 'Z';

		// Get the ordering criteria.
		list($orderBy, $orderDir) = $this->_getResultSetOrdering();
		$orderDir = ($orderDir == 'asc' ? true : false);

		// Call the solr web service.
		return $this->_solrWebService->retrieveResults(
			$journal, $translatedSearch, $totalResults, $page, $itemsPerPage,
			$fromDate, $toDate, $orderBy, $orderDir
		);
	}


	//
	// Public Indexing API
	//
	/**
	 * @see ArticleSearchIndex::deleteTextIndex()
	 */
	function callbackDeleteTextIndex($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::deleteTextIndex');
		list($articleId, $type, $assocId) = $params;
		$success = $this->_indexArticleId($articleId);
		if (!$success) {
			// TODO: Return a notification to the user.
		}
		return true;
	}

	/**
	 * @see ArticleSearchIndex::indexArticleFiles()
	 */
	function callbackIndexArticleFiles($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::indexArticleFiles');
		list($article) = $params; /* @var $article Article */
		$success = $this->_indexArticle($article);
		if (!$success) {
			// TODO: Return a notification to the user.
		}
		return true;
	}

	/**
	 * @see ArticleSearchIndex::indexArticleMetadata()
	 */
	function callbackIndexArticleMetadata($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::indexArticleMetadata');
		list($article) = $params; /* @var $article Article */
		$success = $this->_indexArticle($article);
		if (!$success) {
			// TODO: Return a notification to the user.
		}
		return true;
	}

	/**
	 * @see ArticleSearchIndex::indexSuppFileMetadata()
	 */
	function callbackIndexSuppFileMetadata($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::indexSuppFileMetadata');
		list($suppFile) = $params; /* @var $suppFile SuppFile */
		if (!is_a($suppFile, 'SuppFile')) return true;
		$success = $this->_indexArticleId($suppFile->getArticleId());
		if (!$success) {
			// TODO: Return a notification to the user.
		}
		return true;
	}

	/**
	 * @see ArticleSearchIndex::updateFileIndex()
	 */
	function callbackUpdateFileIndex($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::updateFileIndex');
		list($articleId, $type, $fileId) = $params;
		$success = $this->_indexArticleId($articleId);
		if (!$success) {
			// TODO: Return a notification to the user.
		}
		return true;
	}

	/**
	 * @see ArticleSearchIndex::rebuildIndex()
	 */
	function callbackRebuildIndex($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::rebuildIndex');

		// Unpack the parameters.
		list($log) = $params;

		// Clear index
		if ($log) echo 'LucenePlugin: Clearing index ... ';
		$this->_solrWebService->deleteAllArticlesFromIndex();
		if ($log) echo "done\n";

		// Build index
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journals =& $journalDao->getJournals();
		while (!$journals->eof()) {
			$journal =& $journals->next();
			$numIndexed = 0;

			if ($log) echo "LucenePlugin: Indexing \"", $journal->getLocalizedTitle(), "\" ... ";

			$numIndexed = $this->_solrWebService->indexJournal($journal);

			if ($log) echo $numIndexed, " articles indexed\n";
			unset($journal);
		}
		return true;
	}

	/**
	 * @see TemplateManager::display()
	 */
	function callbackTemplateDisplay($hookName, $params) {
		// We only plug into the search results list.
		$template = $params[1];
		if ($template != 'search/searchResults.tpl') return false;

		// Assign our private stylesheet.
		$templateMgr =& $params[0];
		$templateMgr->addStylesheet(Request::getBaseUrl() . '/' . $this->getPluginPath() . '/templates/lucene.css');

		// Result set ordering options.
		$orderByOptions = $this->_getResultSetOrderingOptions();
		$templateMgr->assign('luceneOrderByOptions', $orderByOptions);
		$orderDirOptions = $this->_getResultSetOrderingDirectionOptions();
		$templateMgr->assign('luceneOrderDirOptions', $orderDirOptions);

		// Result set ordering selection.
		list($orderBy, $orderDir) = $this->_getResultSetOrdering();
		$templateMgr->assign('orderBy', $orderBy);
		$templateMgr->assign('orderDir', $orderDir);

		return false;
	}

	/**
	 * @see templates/search/searchResults.tpl
	 */
	function callbackTemplatePreResults($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];
		$output .= $smarty->fetch($this->getTemplatePath() . 'preResults.tpl');
		return false;
	}


	//
	// Private helper methods
	//
	/**
	 * Index a single article.
	 * @param $article Article
	 * @return boolean Whether or not the indexing was successful.
	 */
	function _indexArticle(&$article) {
		if(!is_a($article, 'Article')) return false;

		// We need the article's journal to index the article.
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journal =& $journalDao->getJournal($article->getJournalId());
		if(!is_a($journal, 'Journal')) return false;

		// We cannot re-index article files only. We have
		// to re-index the whole article.
		return $this->_solrWebService->indexArticle($article, $journal);
	}

	/**
	 * Index a single article when an article ID is given.
	 * @param $articleId integer
	 * @return boolean Whether or not the indexing was successful.
	 */
	function _indexArticleId($articleId) {
		if (!is_numeric($articleId)) return false;

		// Retrieve the article object.
		$articleDao =& DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
		$article =& $articleDao->getArticle($articleId);
		if(!is_a($article, 'Article')) {
			// The article doesn't seem to exist any more.
			// Delete possible remainders from the index.
			return $this->_solrWebService->deleteArticleFromIndex($articleId);
		}

		// Re-index the article.
		return $this->_indexArticle($article);
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 */
	function _setBreadcrumbs() {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url('index', 'admin'),
				'user.role.siteAdmin'
			),
			array(
				Request::url(null, 'manager', 'plugins'),
				'manager.plugins'
			)
		);
		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}

	/**
	 * Return the available options for result
	 * set ordering.
	 * @return array
	 */
	function _getResultSetOrderingOptions() {
		return array(
			'score' => __('plugins.generic.lucene.results.orderBy.relevance'),
			'authors' => __('plugins.generic.lucene.results.orderBy.author'),
			'issuePublicationDate' => __('plugins.generic.lucene.results.orderBy.issue'),
			'publicationDate' => __('plugins.generic.lucene.results.orderBy.date'),
			'journalTitle' => __('plugins.generic.lucene.results.orderBy.journal'),
			'title' => __('plugins.generic.lucene.results.orderBy.article')
		);
	}

	/**
	 * Return the available options for the result
	 * set ordering direction.
	 * @return array
	 */
	function _getResultSetOrderingDirectionOptions() {
		return array(
			'asc' => __('plugins.generic.lucene.results.orderDir.asc'),
			'desc' => __('plugins.generic.lucene.results.orderDir.desc')
		);
	}

	/**
	 * Return the currently selected result
	 * set ordering option (default: descending relevance).
	 * @return array An array with the order field as the
	 *  first entry and the order direction as the second
	 *  entry.
	 */
	function _getResultSetOrdering() {
		// Retrieve the request.
		$request =& Application::getRequest();

		// Order field.
		$orderBy = $request->getUserVar('orderBy');
		$orderByOptions = $this->_getResultSetOrderingOptions();
		if (is_null($orderBy) || !in_array($orderBy, array_keys($orderByOptions))) {
			$orderBy = 'score';
		}

		// Ordering direction.
		$orderDir = $request->getUserVar('orderDir');
		$orderDirOptions = $this->_getResultSetOrderingDirectionOptions();
		if (is_null($orderDir) || !in_array($orderDir, array_keys($orderDirOptions))) {
			if (in_array($orderBy, array('score', 'publicationDate', 'issuePublicationDate'))) {
				$orderDir = 'desc';
			} else {
				$orderDir = 'asc';
			}
		}

		return array($orderBy, $orderDir);
	}
}
?>
