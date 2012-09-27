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

	/** @var string */
	var $_spellingSuggestion;

	/** @var string */
	var $_spellingSuggestionField;


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
	 * Get the solr web service.
	 * @return SolrWebService
	 */
	function &getSolrWebService() {
		return $this->_solrWebService;
	}

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
	 */
	function &getMailTemplate($emailKey, $journal = null) {
		if (!isset($this->_mailTemplates[$emailKey])) {
			import('classes.mail.MailTemplate');
			$mailTemplate = new MailTemplate($emailKey, null, null, $journal, true, true);
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
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;

		if ($success && $this->getEnabled()) {
			// This plug-in requires PHP 5.0.
			if (!checkPhpVersion('5.0.0')) return false;

			// Register callbacks (application-level).
			HookRegistry::register('LoadHandler', array(&$this, 'callbackLoadHandler'));

			// Register callbacks (data-access level).
			HookRegistry::register('articledao::getAdditionalFieldNames', array(&$this, 'callbackArticleDaoAdditionalFieldNames'));

			// Register callbacks (controller-level).
			HookRegistry::register('ArticleSearch::retrieveResults', array(&$this, 'callbackRetrieveResults'));
			HookRegistry::register('ArticleSearchIndex::articleMetadataChanged', array(&$this, 'callbackArticleMetadataChanged'));
			HookRegistry::register('ArticleSearchIndex::articleFileChanged', array(&$this, 'callbackArticleFileChanged'));
			HookRegistry::register('ArticleSearchIndex::articleFileDeleted', array(&$this, 'callbackArticleFileDeleted'));
			HookRegistry::register('ArticleSearchIndex::articleFilesChanged', array(&$this, 'callbackArticleFilesChanged'));
			HookRegistry::register('ArticleSearchIndex::suppFileMetadataChanged', array(&$this, 'callbackSuppFileMetadataChanged'));
			HookRegistry::register('ArticleSearchIndex::articleDeleted', array(&$this, 'callbackArticleDeleted'));
			HookRegistry::register('ArticleSearchIndex::articleChangesFinished', array(&$this, 'callbackArticleChangesFinished'));
			HookRegistry::register('ArticleSearchIndex::rebuildIndex', array(&$this, 'callbackRebuildIndex'));

			// Register callbacks (view-level).
			HookRegistry::register('TemplateManager::display',array(&$this, 'callbackTemplateDisplay'));
			if ($this->getSetting(0, 'autosuggest')) {
				HookRegistry::register('Templates::Search::SearchResults::FilterInput', array($this, 'callbackTemplateFilterInput'));
			}
			HookRegistry::register('Templates::Search::SearchResults::PreResults', array($this, 'callbackTemplatePreResults'));
			HookRegistry::register('Templates::Search::SearchResults::AdditionalArticleLinks', array($this, 'callbackTemplateAdditionalArticleLinks'));
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
	// Application level hook implementations.
	//
	/**
	 * @see PKPPageRouter::route()
	 */
	function callbackLoadHandler($hookName, $args) {
		// Check the page.
		$page = $args[0];
		if ($page !== 'lucene') return;

		// Check the operation.
		$op = $args[1];
		$publicOps = array(
			'queryAutocomplete',
			'pullChangedArticles',
			'similarDocuments'
		);
		if (!in_array($op, $publicOps)) return;

		// Looks as if our handler had been requested.
		define('HANDLER_CLASS', 'LuceneHandler');
		define('LUCENE_PLUGIN_NAME', $this->getName());
		$handlerFile =& $args[2];
		$handlerFile = $this->getPluginPath() . '/' . 'LuceneHandler.inc.php';
	}


	//
	// Data-access level hook implementations.
	//
	/**
	 * @see DAO::getAdditionalFieldNames()
	 */
	function callbackArticleDaoAdditionalFieldNames($hookName, $args) {
		// Add the indexing state setting to the field names.
		// This will be used to mark articles as "dirty" or "clean"
		// when pull-indexing is enabled.
		$returner =& $args[1];
		$returner[] = 'indexingState';
	}


	//
	// Controller level hook implementations.
	//
	/**
	 * @see ArticleSearch::retrieveResults()
	 */
	function callbackRetrieveResults($hookName, $params) {
		assert($hookName == 'ArticleSearch::retrieveResults');

		// Unpack the parameters.
		list($journal, $keywords, $fromDate, $toDate, $page, $itemsPerPage, $dummy) = $params;
		$totalResults =& $params[6]; // need to use reference
		$error =& $params[7]; // need to use reference

		// Instantiate a search request.
		$searchRequest = new SolrSearchRequest();
		$searchRequest->setJournal($journal);
		$searchRequest->setFromDate($fromDate);
		$searchRequest->setToDate($toDate);
		$searchRequest->setPage($page);
		$searchRequest->setItemsPerPage($itemsPerPage);
		$searchRequest->addQueryFromKeywords($keywords);

		// Get the ordering criteria.
		list($orderBy, $orderDir) = $this->_getResultSetOrdering($journal);
		$searchRequest->setOrderBy($orderBy);
		$searchRequest->setOrderDir($orderDir == 'asc' ? true : false);

		// Configure alternative spelling suggestions.
		$spellcheck = (boolean)$this->getSetting(0, 'spellcheck');
		$searchRequest->setSpellcheck($spellcheck);

		// Call the solr web service.
		$solrWebService =& $this->getSolrWebService();
		$result =& $solrWebService->retrieveResults($searchRequest, $totalResults);
		if (is_null($result)) {
			$error = $solrWebService->getServiceMessage();
			$this->_informTechAdmin($error, $journal, true);
			$error .=  ' ' . __('plugins.generic.lucene.message.techAdminInformed');
			return array();
		} else {
			if ($spellcheck && isset($result['spellingSuggestion'])) {
				// Store the spelling suggestion internally. We cannot
				// route it back through the request as the default
				// search implementation does not support spelling
				// suggestions.
				$this->_spellingSuggestion = $result['spellingSuggestion'];

				// Identify the field for which we got the suggestion.
				foreach($keywords as $bitmap => $searchPhrase) {
					if (!empty($searchPhrase)) {
						switch ($bitmap) {
							case null:
								$queryField = 'query';
								break;

							case ARTICLE_SEARCH_INDEX_TERMS:
								$queryField = 'indexTerms';
								break;

							default:
								$indexFieldMap = ArticleSearch::getIndexFieldMap();
								assert(isset($indexFieldMap[$bitmap]));
								$queryField = $indexFieldMap[$bitmap];
						}
					}
				}
				$this->_spellingSuggestionField = $queryField;
			}
			if (isset($result['scoredResults'])) {
				return $result['scoredResults'];
			} else {
				return array();
			}
		}
	}

	/**
	 * @see ArticleSearchIndex::articleMetadataChanged()
	 */
	function callbackArticleMetadataChanged($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::articleMetadataChanged');
		list($article) = $params; /* @var $article Article */
		$this->_solrWebService->markArticleChanged($article->getId());
		return true;
	}

	/**
	 * @see ArticleSearchIndex::articleFilesChanged()
	 */
	function callbackArticleFilesChanged($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::articleFilesChanged');
		list($article) = $params; /* @var $article Article */
		$this->_solrWebService->markArticleChanged($article->getId());
		return true;
	}

	/**
	 * @see ArticleSearchIndex::articleFileChanged()
	 */
	function callbackArticleFileChanged($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::articleFileChanged');
		list($articleId, $type, $fileId) = $params;
		$this->_solrWebService->markArticleChanged($articleId);
		return true;
	}

	/**
	 * @see ArticleSearchIndex::articleFileDeleted()
	 */
	function callbackArticleFileDeleted($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::articleFileDeleted');
		list($articleId, $type, $assocId) = $params;
		$this->_solrWebService->markArticleChanged($articleId);
		return true;
	}

	/**
	 * @see ArticleSearchIndex::suppFileMetadataChanged()
	 */
	function callbackSuppFileMetadataChanged($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::suppFileMetadataChanged');
		list($suppFile) = $params; /* @var $suppFile SuppFile */
		if (!is_a($suppFile, 'SuppFile')) return true;
		$this->_solrWebService->markArticleChanged($suppFile->getArticleId());
		return true;
	}

	/**
	 * @see ArticleSearchIndex::articleDeleted()
	 */
	function callbackArticleDeleted($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::articleDeleted');
		list($articleId) = $params;
		// Deleting an article must always be done synchronously
		// (even in pull-mode) as we'll no longer have an object
		// to keep our change information.
		$this->_solrWebService->deleteArticleFromIndex($articleId);
		return true;
	}

	/**
	 * @see ArticleSearchIndex::articleChangesFinished()
	 */
	function callbackArticleChangesFinished($hookName, $params) {
		// In the case of pull-indexing we ignore this call
		// and let the Solr server initiate indexing.
		if ($this->getSetting(0, 'pullindexing')) return true;

		// If the plugin is configured to push changes to the
		// server then we'll now batch-update all articles that
		// changed since the last update. We use a batch size of 5
		// for online index updates to limit the time a request may be
		// locked in case a race condition with a large index update
		// occurs.
		$solrWebService =& $this->getSolrWebService();
		$result = $solrWebService->pushChangedArticles(5);
		if (is_null($result)) {
			$this->_informTechAdmin($solrWebService->getServiceMessage());
		}
		return true;
	}

	/**
	 * @see ArticleSearchIndex::rebuildIndex()
	 */
	function callbackRebuildIndex($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::rebuildIndex');
		$solrWebService = $this->getSolrWebService();

		// Unpack the parameters.
		list($log, $journal) = $params;

		// If we got a journal instance then only re-index
		// articles from that journal.
		$journalIdOrNull = (is_a($journal, 'Journal') ? $journal->getId() : null);

		// Clear index (if the journal id is null then
		// all journals will be deleted from the index).
		if ($log) echo 'LucenePlugin: ' . __('search.cli.rebuildIndex.clearingIndex') . ' ... ';
		$solrWebService->deleteArticlesFromIndex($journalIdOrNull);
		if ($log) echo __('search.cli.rebuildIndex.done') . "\n";

		// Re-build index, either of a single journal...
		if (is_a($journal, 'Journal')) {
			$journals = array($journal);
			unset($journal);
		// ...or for all journals.
		} else {
			$journalDao =& DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
			$journalIterator =& $journalDao->getJournals();
			$journals = $journalIterator->toArray();
		}

		// We re-index journal by journal to partition the task a bit
		// and provide better progress information to the user.
		foreach($journals as $journal) {
			if ($log) echo __('search.cli.rebuildIndex.indexing', array('journalName' => $journal->getLocalizedTitle())) . ' ';

			// Mark all articles in the journal for re-indexing.
			$numMarked = $this->_solrWebService->markJournalChanged($journal->getId());

			// Pull or push?
			if ($this->getSetting(0, 'pullindexing')) {
				// When pull-indexing is configured then we leave it up to the
				// Solr server to decide when the updates will actually be done.
				if ($log) echo '... ' . __('plugins.generic.lucene.rebuildIndex.pullResult', array('numMarked' => $numMarked)) . "\n";
			} else {
				// In case of push indexing we immediately update the index.
				$numIndexed = 0;
				do {
					// We update the index in batches to reduce max memory usage
					// and make a few intermediate commits.
					$articlesInBatch = $solrWebService->pushChangedArticles(SOLR_INDEXING_MAX_BATCHSIZE, $journal->getId());
					if (is_null($articlesInBatch)) {
						$error = $solrWebService->getServiceMessage();
						if ($log) {
							echo ' ' . __('search.cli.rebuildIndex.error') . (empty($error) ? '' : ": $error") . "\n";
						} else {
							$this->_informTechAdmin($error, $journal);
						}
						return false;
					}
					if ($log) echo '.';
					$numIndexed += $articlesInBatch;
				} while ($articlesInBatch == SOLR_INDEXING_MAX_BATCHSIZE);
				if ($log) echo ' ' . __('search.cli.rebuildIndex.result', array('numIndexed' => $numIndexed)) . "\n";
			}
		}
		return true;
	}


	//
	// View level hook implementations.
	//
	/**
	 * @see TemplateManager::display()
	 */
	function callbackTemplateDisplay($hookName, $params) {
		// We only plug into the search results list.
		$template = $params[1];
		if ($template != 'search/search.tpl') return false;

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
	function callbackTemplateFilterInput($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];
		$smarty->assign($params[0]);
		$output .= $smarty->fetch($this->getTemplatePath() . 'filterInput.tpl');
		return false;
	}

	/**
	 * @see templates/search/searchResults.tpl
	 */
	function callbackTemplatePreResults($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];
		// The spelling suggestion value is set in
		// LucenePlugin::callbackRetrieveResults(), see there.
		$smarty->assign('spellingSuggestion', $this->_spellingSuggestion);
		$smarty->assign(
			'spellingSuggestionUrlParams',
			array($this->_spellingSuggestionField => $this->_spellingSuggestion)
		);
		$output .= $smarty->fetch($this->getTemplatePath() . 'preResults.tpl');
		return false;
	}

	/**
	 * @see templates/search/searchResults.tpl
	 */
	function callbackTemplateAdditionalArticleLinks($hookName, $params) {
		// Check whether the "similar documents" feature is
		// enabled.
		if (!$this->getSetting(0, 'simdocs')) return false;

		// Check and prepare the article parameter.
		$hookParams = $params[0];
		if (!(isset($hookParams['articleId']) && is_numeric($hookParams['articleId']))) {
			return false;
		}
		$urlParams = array(
			'articleId' => $hookParams['articleId']
		);

		// Create a URL that links to "similar documents".
		$request =& PKPApplication::getRequest();
		$router =& $request->getRouter();
		$simdocsUrl = $router->url(
			$request, null, 'lucene', 'similarDocuments', null, $urlParams
		);

		// Return a link to the URL.
		$output =& $params[2];
		$output .= '&nbsp;<a href="' . $simdocsUrl . '" class="file">'
			. __('plugins.generic.lucene.results.similarDocuments')
			. '</a>';
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
	 * Checks whether a minimum amount of time has passed since
	 * the last email message went out.
	 *
	 * @return boolean True if a new email can be sent, false if
	 *  we better keep silent.
	 */
	function _spamCheck() {
		// Avoid spam.
		$lastEmailTimstamp = (integer)$this->getSetting(0, 'lastEmailTimestamp');
		$threeHours = 60 * 60 * 3;
		$now = time();
		if ($now - $lastEmailTimstamp < $threeHours) return false;
		$this->updateSetting(0, 'lastEmailTimestamp', $now);
		return true;
	}

	/**
	 * Send an email to the site's tech admin
	 * warning that an indexing error has occured.
	 *
	 * @param $error array An array of article ids.
	 * @param $journal Journal A journal object.
	 * @param $isSearchProblem boolean Whether a search problem
	 *  is being reported.
	 */
	function _informTechAdmin($error, $journal = null, $isSearchProblem = false) {
		if (!$this->_spamCheck()) return;

		// Is this a search or an indexing problem?
		if ($isSearchProblem) {
			assert(is_a($journal, 'Journal'));
			$mail =& $this->getMailTemplate('LUCENE_SEARCH_SERVICE_ERROR_NOTIFICATION', $journal);
		} else {
			// Check whether this is journal or article index update problem.
			if (is_a($journal, 'Journal')) {
				// This must be a journal indexing problem.
				$mail =& $this->getMailTemplate('LUCENE_JOURNAL_INDEXING_ERROR_NOTIFICATION', $journal);
			} else {
				// Instantiate an article mail template.
				$mail =& $this->getMailTemplate('LUCENE_ARTICLE_INDEXING_ERROR_NOTIFICATION');
			}
		}

		// Assign parameters.
		$request =& PKPApplication::getRequest();
		$site =& $request->getSite();
		$mail->assignParams(
			array('siteName' => $site->getLocalizedTitle(), 'error' => $error)
		);

		// Send to the site's tech contact.
		$mail->addRecipient($site->getLocalizedContactEmail(), $site->getLocalizedContactName());

		// Send the mail.
		$mail->send($request);
	}
}
?>
