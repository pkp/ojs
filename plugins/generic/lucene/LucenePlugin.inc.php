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

	/** @var array */
	var $_mailTemplates = array();


	//
	// Constructor
	//
	function LucenePlugin() {
		parent::GenericPlugin();
	}


	//
	// Getters and Setters
	//
	/**
	 * Set an alternative article mailer implementation.
	 *
	 * NB: Required to override the mailer
	 * implementation for testing.
	 *
	 * @param $emailKey string
	 * @param $mailTemplate MailTemplate
	 */
	function setMailTemplate($emailKey, &$mailTemplate) {
		$this->_mailTemplates[$emailKey] =& $mailTemplate;
	}

	/**
	 * Instantiate a MailTemplate
	 *
	 * @param $emailKey string
	 * @param $journal Journal
	 * @param $article Article
	 */
	function &getMailTemplate($emailKey, &$journal, $article = null) {
		if (!isset($this->_mailTemplates[$emailKey])) {
			if (is_a($article, 'Article')) {
				import('classes.mail.ArticleMailTemplate');
				$mailTemplate = new ArticleMailTemplate($article, $emailKey, null, null, $journal, true, true);
			} else {
				import('classes.mail.MailTemplate');
				$mailTemplate = new MailTemplate($emailKey, null, null, $journal, true, true);
			}
			$this->_mailTemplates[$emailKey] =& $mailTemplate;
		}
		return $this->_mailTemplates[$emailKey];
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
			HookRegistry::register('Templates::Search::SearchResults::SyntaxInstructions', array($this, 'callbackTemplateSyntaxInstructions'));

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
	 * @see PKPPlugin::getInstallEmailTemplatesFile()
	 */
	function getInstallEmailTemplatesFile() {
		return ($this->getPluginPath() . '/emailTemplates.xml');
	}

	/**
	 * @see PKPPlugin::getInstallEmailTemplateDataFile()
	 */
	function getInstallEmailTemplateDataFile() {
		return ($this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml');
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
		$request =& $this->getRequest();

		switch ($verb) {
			case 'settings':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
				$this->import('classes.form.LuceneSettingsForm');
				$form = new LuceneSettingsForm($this);
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$request->redirect(null, 'manager', 'plugins', 'generic');
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
		list($journal, $query, $fromDate, $toDate, $page, $itemsPerPage, $dummy) = $params;
		$totalResults =& $params[6]; // need to use reference
		$error =& $params[7]; // need to use reference

		// Instantiate a search request.
		$searchRequest = new SolrSearchRequest();
		$searchRequest->setJournal($journal);
		$searchRequest->setFromDate($fromDate);
		$searchRequest->setToDate($toDate);
		$searchRequest->setPage($page);
		$searchRequest->setItemsPerPage($itemsPerPage);

		// Get a mapping of OJS search fields to Lucene search fields.
		$indexFieldMap = ArticleSearch::getIndexFieldMap();

		// We query fields with search phrases.
		foreach($query as $searchField => $searchPhrase) {
			// Translate query keywords.
			$searchPhrase = $this->_translateSearchPhrase($searchPhrase);

			// Translate the search field from OJS to solr nomenclature.
			if (empty($searchField)) {
				// An empty search field means "all fields".
				$solrFields = array_values($indexFieldMap);
			} else {
				$solrFields = array();
				foreach($indexFieldMap as $ojsField => $solrField) {
					// The search field is a bitmap which may stand for
					// several actual index fields (e.g. the index terms
					// field).
					if ($searchField & $ojsField) {
						$solrFields[] = $solrField;
					}
				}
			}
			$solrFieldString = implode('|', $solrFields);
			$searchRequest->addQueryFieldPhrase($solrFieldString, $searchPhrase);
		}

		// Get the ordering criteria.
		list($orderBy, $orderDir) = $this->_getResultSetOrdering($journal);
		$searchRequest->setOrderBy($orderBy);
		$searchRequest->setOrderDir($orderDir == 'asc' ? true : false);

		// Call the solr web service.
		$result =& $this->_solrWebService->retrieveResults($searchRequest, $totalResults);
		if (is_null($result)) {
			$result = array();
			$error = $this->_solrWebService->getServiceMessage();
			$this->_informTechAdmin(null, $journal, false);
		}
		return $result;
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
			$this->_informTechAdmin($articleId);
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
			$this->_informTechAdmin($article->getId());
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
			$this->_informTechAdmin($article->getId());
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
			$this->_informTechAdmin($suppFile->getArticleId());
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
			$this->_informTechAdmin($articleId);
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

			if ($log) echo 'LucenePlugin: Indexing "', $journal->getLocalizedTitle(), '" ';
			$numIndexed = $this->_solrWebService->indexJournal($journal, $log);
			unset($journal);

			if (is_null($numIndexed)) {
				if ($log) {
					echo " error\n";
				} else {
					$this->_informTechAdmin(null, $journal);
				}
				return false;
			} else {
				if ($log) echo " $numIndexed article(s) indexed\n";
			}
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

		// Get request and context.
		$request =& PKPApplication::getRequest();
		$journal =& $request->getContext();

		// Assign our private stylesheet.
		$templateMgr =& $params[0];
		$templateMgr->addStylesheet($request->getBaseUrl() . '/' . $this->getPluginPath() . '/templates/lucene.css');

		// Result set ordering options.
		$orderByOptions = $this->_getResultSetOrderingOptions($journal);
		$templateMgr->assign('luceneOrderByOptions', $orderByOptions);
		$orderDirOptions = $this->_getResultSetOrderingDirectionOptions();
		$templateMgr->assign('luceneOrderDirOptions', $orderDirOptions);

		// Result set ordering selection.
		list($orderBy, $orderDir) = $this->_getResultSetOrdering($journal);
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

	/**
	 * @see templates/search/searchResults.tpl
	 */
	function callbackTemplateSyntaxInstructions($hookName, $params) {
		$output =& $params[2];
		$output .= __('plugins.generic.lucene.results.syntaxInstructions');
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

		// If the article object is not of type PublishedArticle
		// or if it is not a fully populated published article
		// then upgrade/populate the object.
		if (is_a($article, 'PublishedArticle') && is_numeric($article->getJournalId())) {
			$publishedArticle =& $article;
		} else {
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
			$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($article->getId());
		}

		if(!is_a($publishedArticle, 'PublishedArticle')) {
			// The article is no longer public. Delete possible
			// remainders from the index.
			return $this->_solrWebService->deleteArticleFromIndex($article->getId());
		}

		// We need the article's journal to index the article.
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journal =& $journalDao->getById($publishedArticle->getJournalId());
		if(!is_a($journal, 'Journal')) return false;

		// We cannot re-index article files only. We have
		// to re-index the whole article.
		return $this->_solrWebService->indexArticle($publishedArticle, $journal);
	}

	/**
	 * Index a single article when an article ID is given.
	 * @param $articleId integer
	 * @return boolean Whether or not the indexing was successful.
	 */
	function _indexArticleId($articleId) {
		if (!is_numeric($articleId)) return false;

		// Retrieve the article object.
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		$publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($articleId);

		if(!is_a($publishedArticle, 'PublishedArticle')) {
			// The article doesn't exist any more
			// or is no longer public. Delete possible
			// remainders from the index.
			return $this->_solrWebService->deleteArticleFromIndex($articleId);
		}

		// Re-index the article.
		return $this->_indexArticle($publishedArticle);
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 */
	function _setBreadcrumbs() {
		$templateMgr =& TemplateManager::getManager();
		$request =& $this->getRequest();
		$pageCrumbs = array(
			array(
				$request->url(null, 'user'),
				'navigation.user'
			),
			array(
				$request->url('index', 'admin'),
				'user.role.siteAdmin'
			),
			array(
				$request->url(null, 'manager', 'plugins'),
				'manager.plugins'
			)
		);
		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}

	/**
	 * Return the available options for result
	 * set ordering.
	 * @param $journal Journal
	 * @return array
	 */
	function _getResultSetOrderingOptions($journal) {
		$resultSetOrderingOptions = array(
			'score' => __('plugins.generic.lucene.results.orderBy.relevance'),
			'authors' => __('plugins.generic.lucene.results.orderBy.author'),
			'issuePublicationDate' => __('plugins.generic.lucene.results.orderBy.issue'),
			'publicationDate' => __('plugins.generic.lucene.results.orderBy.date'),
			'title' => __('plugins.generic.lucene.results.orderBy.article')
		);

		// Only show the "journal title" option if we have several journals.
		if (!is_a($journal, 'Journal')) {
			$resultSetOrderingOptions['journalTitle'] = __('plugins.generic.lucene.results.orderBy.journal');
		}

		return $resultSetOrderingOptions;
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
	 * @param $journal Journal
	 * @return array An array with the order field as the
	 *  first entry and the order direction as the second
	 *  entry.
	 */
	function _getResultSetOrdering($journal) {
		// Retrieve the request.
		$request =& Application::getRequest();

		// Order field.
		$orderBy = $request->getUserVar('orderBy');
		$orderByOptions = $this->_getResultSetOrderingOptions($journal);
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

	/**
	 * Send an email to the tech admin of the
	 * journal warning that an indexing error
	 * has occured.
	 * @param $articleId integer
	 * @param $journal Journal
	 * @param $isIndexingProblem boolean True, if this is a problem with an
	 *  index update, otherwise indicates a problem during a search request.
	 */
	function _informTechAdmin($articleId, $journal = null, $isIndexingProblem = true) {
		// Avoid spam.
		$lastEmailTimstamp = (integer)$this->getSetting(0, 'lastEmailTimestamp');
		$threeHours = 60 * 60 * 3;
		$now = time();
		if ($now - $lastEmailTimstamp < $threeHours) return;
		$this->updateSetting(0, 'lastEmailTimestamp', $now);

		// Retrieve the article.
		$article = null;
		if (is_numeric($articleId)) {
			$articleDao =& DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao ArticleDAO */
			$article =& $articleDao->getArticle($articleId);
		}

		// Retrieve the article's journal
		if (is_a($article, 'Article')) {
			// This must be an article indexing problem.
			assert($isIndexingProblem);

			$journalDao =& DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
			$journal =& $journalDao->getById($article->getJournalId());

			// Instantiate an article mail template.
			$mail =& $this->getMailTemplate('LUCENE_ARTICLE_INDEXING_ERROR_NOTIFICATION', $journal, $article);
		} else {
			// Instantiate a mail template.
			if ($isIndexingProblem) {
				// This must be a journal indexing problem.
				assert(is_a($journal, 'Journal'));
				$mail =& $this->getMailTemplate('LUCENE_JOURNAL_INDEXING_ERROR_NOTIFICATION', $journal);
			} else {
				$mail =& $this->getMailTemplate('LUCENE_SEARCH_SERVICE_ERROR_NOTIFICATION', $journal);
			}
		}

		// Assign parameters.
		$request =& PKPApplication::getRequest();
		$site =& $request->getSite();
		$mail->assignParams(array('siteName' => $site->getLocalizedTitle()));

		// Send to the site's tech contact.
		$mail->addRecipient($site->getLocalizedContactEmail(), $site->getLocalizedContactName());

		// Send the mail.
		$mail->send($request);
	}

	/**
	 * Translate query keywords.
	 * @param $searchPhrase string
	 * @return The translated search phrase.
	 */
	function _translateSearchPhrase($searchPhrase) {
		static $queryKeywords;

		if (is_null($queryKeywords)) {
			// Query keywords.
			$queryKeywords = array(
				String::strtolower(__('search.operator.not')) => 'NOT',
				String::strtolower(__('search.operator.and')) => 'AND',
				String::strtolower(__('search.operator.or')) => 'OR'
			);
		}

		// Translate the search phrase.
		foreach($queryKeywords as $queryKeyword => $translation) {
			$searchPhrase = String::regexp_replace("/(^|\s)$queryKeyword(\s|$)/i", "\\1$translation\\2", $searchPhrase);
		}

		return $searchPhrase;
	}
}
?>
