<?php

/**
 * @file plugins/generic/lucene/LucenePlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LucenePlugin
 * @ingroup plugins_generic_lucene
 *
 * @brief Lucene plugin class
 */


import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.lucene.classes.SolrWebService');

define('LUCENE_PLUGIN_DEFAULT_RANKING_BOOST', 1.0); // Default: No boost (=weight factor one).

class LucenePlugin extends GenericPlugin {

	/** @var SolrWebService */
	var $_solrWebService;

	/** @var array */
	var $_mailTemplates = array();

	/** @var string */
	var $_spellingSuggestion;

	/** @var string */
	var $_spellingSuggestionField;

	/** @var array */
	var $_highlightedArticles;

	/** @var array */
	var $_enabledFacetCategories;

	/** @var array */
	var $_facets;


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
	 * Facets corresponding to a recent search
	 * (if any).
	 * @return boolean
	 */
	function getFacets() {
		return $this->_facets;
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
			import('lib.pkp.classes.mail.MailTemplate');
			$mailTemplate = new MailTemplate($emailKey, null, $journal, true, true);
			$this->_mailTemplates[$emailKey] =& $mailTemplate;
		}
		return $this->_mailTemplates[$emailKey];
	}


	//
	// Implement template methods from Plugin.
	//
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;

		if ($success && $this->getEnabled($mainContextId)) {
			// This plug-in requires PHP 5.0.
			if (!checkPhpVersion('5.0.0')) return false;

			// Register callbacks (application-level).
			HookRegistry::register('PluginRegistry::loadCategory', array($this, 'callbackLoadCategory'));
			HookRegistry::register('LoadHandler', array($this, 'callbackLoadHandler'));

			// Register callbacks (data-access level).
			HookRegistry::register('articledao::getAdditionalFieldNames', array($this, 'callbackArticleDaoAdditionalFieldNames'));
			$customRanking = (boolean)$this->getSetting(0, 'customRanking');
			if ($customRanking) {
				HookRegistry::register('sectiondao::getAdditionalFieldNames', array($this, 'callbackSectionDaoAdditionalFieldNames'));
			}

			// Register callbacks (controller-level).
			HookRegistry::register('ArticleSearch::getResultSetOrderingOptions', array($this, 'callbackGetResultSetOrderingOptions'));
			HookRegistry::register('SubmissionSearch::retrieveResults', array($this, 'callbackRetrieveResults'));
			HookRegistry::register('ArticleSearchIndex::articleMetadataChanged', array($this, 'callbackArticleMetadataChanged'));
			HookRegistry::register('ArticleSearchIndex::submissionFileChanged', array($this, 'callbackSubmissionFileChanged'));
			HookRegistry::register('ArticleSearchIndex::submissionFileDeleted', array($this, 'callbackSubmissionFileDeleted'));
			HookRegistry::register('ArticleSearchIndex::submissionFilesChanged', array($this, 'callbackSubmissionFilesChanged'));
			HookRegistry::register('ArticleSearchIndex::articleDeleted', array($this, 'callbackArticleDeleted'));
			HookRegistry::register('ArticleSearchIndex::articleChangesFinished', array($this, 'callbackArticleChangesFinished'));
			HookRegistry::register('ArticleSearchIndex::rebuildIndex', array($this, 'callbackRebuildIndex'));
			HookRegistry::register('ArticleSearch::getSimilarityTerms', array($this, 'callbackGetSimilarityTerms'));

			// Register callbacks (forms).
			if ($customRanking) {
				HookRegistry::register('sectionform::Constructor', array($this, 'callbackSectionFormConstructor'));
				HookRegistry::register('sectionform::initdata', array($this, 'callbackSectionFormInitData'));
				HookRegistry::register('sectionform::readuservars', array($this, 'callbackSectionFormReadUserVars'));
				HookRegistry::register('sectionform::execute', array($this, 'callbackSectionFormExecute'));
			}

			// Register callbacks (view-level).
			HookRegistry::register('TemplateManager::display',array($this, 'callbackTemplateDisplay'));
			if ($this->getSetting(0, 'autosuggest')) {
				HookRegistry::register('Templates::Search::SearchResults::FilterInput', array($this, 'callbackTemplateFilterInput'));
			}
			if ($customRanking) {
				HookRegistry::register('Templates::Manager::Sections::SectionForm::AdditionalMetadata', array($this, 'callbackTemplateSectionFormAdditionalMetadata'));
			}
			HookRegistry::register('Templates::Search::SearchResults::PreResults', array($this, 'callbackTemplatePreResults'));
			HookRegistry::register('Templates::Search::SearchResults::AdditionalArticleInfo', array($this, 'callbackTemplateAdditionalArticleInfo'));
			HookRegistry::register('Templates::Search::SearchResults::SyntaxInstructions', array($this, 'callbackTemplateSyntaxInstructions'));

			// Instantiate the web service.
			$searchHandler = $this->getSetting(0, 'searchEndpoint');
			$username = $this->getSetting(0, 'username');
			$password = $this->getSetting(0, 'password');
			$instId = $this->getSetting(0, 'instId');
			$useProxySettings = $this->getSetting(0, 'useProxySettings');
			if (!$useProxySettings) $useProxySettings = false;

			$this->_solrWebService = new SolrWebService($searchHandler, $username, $password, $instId, $useProxySettings);
		}
		return $success;
	}

	/**
	 * @see Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.lucene.displayName');
	}

	/**
	 * @see Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.lucene.description');
	}

	/**
	 * @see Plugin::getInstallSitePluginSettingsFile()
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * @see Plugin::getInstallEmailTemplatesFile()
	 */
	function getInstallEmailTemplatesFile() {
		return ($this->getPluginPath() . '/emailTemplates.xml');
	}

	/**
	 * @see Plugin::getInstallEmailTemplateDataFile()
	 */
	function getInstallEmailTemplateDataFile() {
		return ($this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml');
	}

	/**
	 * @see Plugin::isSitePlugin()
	 */
	function isSitePlugin() {
		return true;
	}

	//
	// Implement template methods from GenericPlugin.
	//
 	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		if (!parent::manage($args, $request)) return false;

		switch (array_shift($args)) {
			case 'settings':
				// Prepare the template manager.
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));

				// Instantiate an embedded server instance.
				$this->import('classes.EmbeddedServer');
				$embeddedServer = new EmbeddedServer();

				// Instantiate the settings form.
				$this->import('classes.form.LuceneSettingsForm');
				$form = new LuceneSettingsForm($this, $embeddedServer);

				// Handle request to save configuration data.
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$request->redirect(null, 'manager', 'plugins', 'generic');
						return false;
					} else {
						$form->display($request);
					}

				// Handle administrative request.
				} else {
					// Re-init data. It should be visible to users
					// that whatever data they may have entered into
					// the form was not saved.
					$form->initData();

					// Index rebuild.
					if ($request->getUserVar('rebuildIndex')) {
						// Check whether we got valid index rebuild options.
						if ($form->validate()) {
							// Check whether a journal was selected.
							$journal = null;
							$journalId = $request->getUserVar('journalToReindex');
							if (!empty($journalId)) {
								$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
								$journal = $journalDao->getById($journalId);
								if (!is_a($journal, 'Journal')) $journal = null;
							}
							if (empty($journalId) || (!empty($journalId) && is_a($journal, 'Journal'))) {
								// Rebuild index and dictionaries.
								$messages = null;
								$this->_rebuildIndex(false, $journal, true, true, true, $messages);

								// Transfer indexing output to the form template.
								$form->setData('rebuildIndexMessages', $messages);
							}
						}

					// Dictionary rebuild.
					} elseif ($request->getUserVar('rebuildDictionaries')) {
						// Rebuild dictionaries.
						$journal = null;
						$this->_rebuildIndex(false, null, false, true, false, $messages);

						// Transfer indexing output to the form template.
						$form->setData('rebuildIndexMessages', $messages);

					// Boost File Update.
					} elseif ($request->getUserVar('updateBoostFile')) {
						$this->_updateBoostFiles();

					// Start/Stop solr server.
					} elseif ($request->getUserVar('stopServer')) {
						// As this is a system plug-in we follow usual
						// plug-in policy and allow journal managers to start/
						// stop the server although this will affect all journals
						// of the installation.
						$embeddedServer->stopAndWait();
					} elseif ($request->getUserVar('startServer')) {
						$embeddedServer->start();
					}

					// Re-display the settings page after executing
					// an administrative task.
					$form->display($request);
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
	 * @see PluginRegistry::loadCategory()
	 */
	function callbackLoadCategory($hookName, $args) {
		// We only contribute to the block plug-in category.
		$category = $args[0];
		if ($category != 'blocks') return false;

		// We only contribute a plug-in if at least one
		// faceting category is enabled.
		$enabledFacetCategories = $this->_getEnabledFacetCategories();
		if (empty($enabledFacetCategories)) return false;

		// Instantiate the block plug-in for facets.
		$this->import('LuceneFacetsBlockPlugin');
		$luceneFacetsBlockPlugin = new LuceneFacetsBlockPlugin($this->getName());

		// Add the plug-in to the registry.
		$plugins =& $args[1];
		$seq = $luceneFacetsBlockPlugin->getSeq();
		if (!isset($plugins[$seq])) $plugins[$seq] = array();
		$plugins[$seq][$luceneFacetsBlockPlugin->getPluginPath()] =& $luceneFacetsBlockPlugin;

		return false;
	}

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
			'usageMetricBoost'
		);
		if (!in_array($op, $publicOps)) return;

		// Get the journal object from the context (optimized).
		$request = Application::getRequest();
		$router = $request->getRouter();
		$journal = $router->getContext($request); /* @var $journal Journal */
		if ($op == 'usageMetricBoost' && $journal != null) return;

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

	/**
	 * @see DAO::getAdditionalFieldNames()
	 */
	function callbackSectionDaoAdditionalFieldNames($hookName, $args) {
		// Add the custom ranking setting to the field names.
		// This will be used to adjust the ranking boost of all
		// articles in a given section.
		$returner =& $args[1];
		$returner[] = 'rankingBoost';
	}


	//
	// Controller level hook implementations.
	//
	/**
	 * @see ArticleSearch::getResultSetOrderingOptions()
	 */
	function callbackGetResultSetOrderingOptions($hookName, $params) {
		$resultSetOrderingOptions =& $params[1];

		// Only show the "popularity" option when sorting-by-metric is enabled.
		if (!$this->getSetting(0, 'sortingByMetric')) {
			unset($resultSetOrderingOptions['popularityAll'], $resultSetOrderingOptions['popularityMonth']);
		}
	}

	/**
	 */
	function callbackRetrieveResults($hookName, $params) {
		assert($hookName == 'SubmissionSearch::retrieveResults');

		// Unpack the parameters.
		list($journal, $keywords, $fromDate, $toDate, $orderBy, $orderDir, $exclude, $page, $itemsPerPage) = $params;
		$totalResults =& $params[9]; // need to use reference
		$error =& $params[10]; // need to use reference

		// Instantiate a search request.
		$searchRequest = new SolrSearchRequest();
		$searchRequest->setJournal($journal);
		$searchRequest->setFromDate($fromDate);
		$searchRequest->setToDate($toDate);
		$searchRequest->setOrderBy($orderBy);
		$searchRequest->setOrderDir($orderDir == 'asc' ? true : false);
		$searchRequest->setPage($page);
		$searchRequest->setItemsPerPage($itemsPerPage);
		$searchRequest->addQueryFromKeywords($keywords);
		$searchRequest->setExcludedIds($exclude);

		// Configure alternative spelling suggestions.
		$spellcheck = (boolean)$this->getSetting(0, 'spellcheck');
		$searchRequest->setSpellcheck($spellcheck);

		// Configure highlighting.
		$highlighting = (boolean)$this->getSetting(0, 'highlighting');
		$searchRequest->setHighlighting($highlighting);

		// Configure faceting.
		// 1) Faceting will be disabled for filtered search categories.
		$activeFilters = array_keys($searchRequest->getQuery());
		if (is_a($journal, 'Journal')) $activeFilters[] = 'journalTitle';
		if (!empty($fromDate) || !empty($toDate)) $activeFilters[] = 'publicationDate';
		// 2) Switch faceting on for enabled categories that have no
		// active filters.
		$facetCategories = array_values(array_diff($this->_getEnabledFacetCategories(), $activeFilters));
		$searchRequest->setFacetCategories($facetCategories);

		// Configure custom ranking.
		$customRanking = (boolean)$this->getSetting(0, 'customRanking');
		if ($customRanking) {
			$sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
			if (is_a($journal, 'Journal')) {
				$sections = $sectionDao->getByJournalId($journal->getId());
			} else {
				$sections = $sectionDao->getAll();
			}
			while ($section = $sections->next()) { /* @var $sections DAOResultFactory */
				$section = $sections->next();
				$sectionBoost = (float)$section->getData('rankingBoost');
				if ($sectionBoost != 1.0) {
					$searchRequest->addBoostFactor(
						'section_id', $section->getId(), $sectionBoost
					);
				}
			}
			unset($sections);
		}

		// Configure ranking-by-metric.
		$rankingByMetric = (boolean)$this->getSetting(0, 'rankingByMetric');
		if ($rankingByMetric) {
			// The 'usageMetricAll' field is an external file field containing
			// multiplicative boost values calculated from usage metrics and
			// normalized to values between 1.0 and 2.0.
			$searchRequest->addBoostField('usageMetricAll');
		}

		// Call the solr web service.
		$solrWebService =& $this->getSolrWebService();
		$result = $solrWebService->retrieveResults($searchRequest, $totalResults);
		if (is_null($result)) {
			$error = $solrWebService->getServiceMessage();
			$this->_informTechAdmin($error, $journal, true);
			$error .=  ' ' . __('plugins.generic.lucene.message.techAdminInformed');
			return array();
		} else {
			// Store spelling suggestion, highlighting and faceting info
			// internally. We cannot route these back through the request
			// as the default search implementation does not support
			// these features.
			if ($spellcheck && isset($result['spellingSuggestion'])) {
				$this->_spellingSuggestion = $result['spellingSuggestion'];

				// Identify the field for which we got the suggestion.
				foreach($keywords as $bitmap => $searchPhrase) {
					if (!empty($searchPhrase)) {
						switch ($bitmap) {
							case null:
								$queryField = 'query';
								break;

							case SUBMISSION_SEARCH_INDEX_TERMS:
								$queryField = 'indexTerms';
								break;

							default:
								$articleSearch = new ArticleSearch();
								$indexFieldMap = $articleSearch->getIndexFieldMap();
								assert(isset($indexFieldMap[$bitmap]));
								$queryField = $indexFieldMap[$bitmap];
						}
					}
				}
				$this->_spellingSuggestionField = $queryField;
			}
			if ($highlighting && isset($result['highlightedArticles'])) {
				$this->_highlightedArticles = $result['highlightedArticles'];
			}
			if (!empty($facetCategories) && isset($result['facets'])) {
				$this->_facets = $result['facets'];
			}

			// Return the scored results.
			if (isset($result['scoredResults']) && !empty($result['scoredResults'])) {
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
	 * @see ArticleSearchIndex::submissionFilesChanged()
	 */
	function callbackSubmissionFilesChanged($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::submissionFilesChanged');
		list($article) = $params; /* @var $article Article */
		$this->_solrWebService->markArticleChanged($article->getId());
		return true;
	}

	/**
	 * @see ArticleSearchIndex::submissionFileChanged()
	 */
	function callbackSubmissionFileChanged($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::submissionFileChanged');
		list($articleId, $type, $fileId) = $params;
		$this->_solrWebService->markArticleChanged($articleId);
		return true;
	}

	/**
	 * @see ArticleSearchIndex::submissionFileDeleted()
	 */
	function callbackSubmissionFileDeleted($hookName, $params) {
		assert($hookName == 'ArticleSearchIndex::submissionFileDeleted');
		list($articleId, $type, $assocId) = $params;
		$this->_solrWebService->markArticleChanged($articleId);
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
		if ($this->getSetting(0, 'pullIndexing')) return true;

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

		// Unpack the parameters.
		list($log, $journal, $switches) = $params;

		// Check switches.
		$rebuildIndex = true;
		$rebuildDictionaries = false;
		$updateBoostFile = false;
		if (is_array($switches)) {
			if (in_array('-n', $switches)) {
				$rebuildIndex = false;
			}
			if (in_array('-d', $switches)) {
				$rebuildDictionaries = true;
			}
			if (in_array('-b', $switches)) {
				$updateBoostFile = true;
			}
		}

		// Rebuild the index.
		$messages = null;
		$this->_rebuildIndex($log, $journal, $rebuildIndex, $rebuildDictionaries, $updateBoostFile, $messages);
		return true;
	}

	/**
	 * @see ArticleSearch::getSimilarityTerms()
	 */
	function callbackGetSimilarityTerms($hookName, $params) {
		$articleId = $params[0];
		$searchTerms =& $params[1];

		// Identify "interesting" terms of the
		// given article and return them "by ref".
		$solrWebService = $this->getSolrWebService();
		$searchTerms = $solrWebService->getInterestingTerms($articleId);

		return true;
	}


	//
	// Form hook implementations.
	//
	/**
	 * @see Form::__construct()
	 */
	function callbackSectionFormConstructor($hookName, $params) {
		// Check whether we got a valid ranking boost option.
		$acceptedValues = array_keys($this->_getRankingBoostOptions());
		$form =& $params[0];
		$form->addCheck(
			new FormValidatorInSet(
				$form, 'rankingBoostOption', FORM_VALIDATOR_REQUIRED_VALUE,
				'plugins.generic.lucene.sectionForm.rankingBoostInvalid',
				$acceptedValues
			)
		);
		return false;
	}

	/**
	 * @see Form::initData()
	 */
	function callbackSectionFormInitData($hookName, $params) {
		$form =& $params[0]; /* @var $form SectionForm */

		// Read the section's ranking boost.
		$rankingBoost = LUCENE_PLUGIN_DEFAULT_RANKING_BOOST;
		$section =& $form->section;
		if (is_a($section, 'Section')) {
			$rankingBoostSetting = $section->getData('rankingBoost');
			if (is_numeric($rankingBoostSetting)) $rankingBoost = (float)$rankingBoostSetting;
		}

		// The ranking boost is a floating-poing multiplication
		// factor (0, 0.5, 1, 2). Translate this into an integer
		// option value (0, 1, 2, 4).
		$rankingBoostOption = (int)($rankingBoost * 2);
		$rankingBoostOptions = $this->_getRankingBoostOptions();
		if (!in_array($rankingBoostOption, array_keys($rankingBoostOptions))) {
			$rankingBoostOption = (int)(LUCENE_PLUGIN_DEFAULT_RANKING_BOOST * 2);
		}
		$form->setData('rankingBoostOption', $rankingBoostOption);
		return false;
	}

	/**
	 * @see Form::readUserVars()
	 */
	function callbackSectionFormReadUserVars($hookName, $params) {
		$userVars =& $params[1];
		$userVars[] = 'rankingBoostOption';
		return false;
	}

	/**
	 * Callback for execution upon section form save
	 * @param $hookName string
	 * @param $params array
	 */
	function callbackSectionFormExecute($hookName, $params) {
		// Convert the ranking boost option back into a ranking boost factor.
		$form =& $params[0]; /* @var $form SectionForm */
		$rankingBoostOption = $form->getData('rankingBoostOption');
		$rankingBoostOptions = $this->_getRankingBoostOptions();
		if (in_array($rankingBoostOption, array_keys($rankingBoostOptions))) {
			$rankingBoost = ((float)$rankingBoostOption)/2;
		} else {
			$rankingBoost = LUCENE_PLUGIN_DEFAULT_RANKING_BOOST;
		}

		// Update the ranking boost of the section.
		$section =& $params[1]; /* @var $section Section */
		$section->setData('rankingBoost', $rankingBoost);
		return false;
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

		// Get the request.
		$request = Application::getRequest();

		// Assign our private stylesheet.
		$templateMgr = $params[0];
		$templateMgr->addStylesheet('lucene', $request->getBaseUrl() . '/' . $this->getPluginPath() . '/templates/lucene.css');

		// Instant search.
		if ($this->getSetting(0, 'instantSearch')) {
			$instantSearch = (boolean)$request->getUserVar('instantSearch');
			$templateMgr->assign('instantSearch', $instantSearch);
			$templateMgr->assign('instantSearchEnabled', true);
		}

		// Similar documents.
		if ($this->getSetting(0, 'simdocs')) {
			$templateMgr->assign('simDocsEnabled', true);
		}

		return false;
	}

	/**
	 * @see templates/search/searchResults.tpl
	 */
	function callbackTemplateFilterInput($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];
		$smarty->assign($params[0]);
		$output .= $smarty->fetch($this->getTemplateResource('filterInput.tpl'));
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
		$output .= $smarty->fetch($this->getTemplateResource('preResults.tpl'));
		return false;
	}

	/**
	 * @see templates/search/searchResults.tpl
	 */
	function callbackTemplateAdditionalArticleInfo($hookName, $params) {
		// Check whether the "highlighting" feature is enabled.
		if (!$this->getSetting(0, 'highlighting')) return false;

		// Check and prepare the article parameter.
		$hookParams = $params[0];
		if (!(isset($hookParams['articleId']) && is_numeric($hookParams['articleId'])
			&& isset($hookParams['numCols']))) {
			return false;
		}
		$articleId = $hookParams['articleId'];

		// Check whether we have highlighting info for the given article.
		if (!isset($this->_highlightedArticles[$articleId])) return false;

		// Return the excerpt (a template seems overkill here).
		// Escaping should have been taken care of when analyzing the text, so
		// there should be no XSS risk here (but we need the <em> tag in the
		// highlighted result).
		$output =& $params[2];
		$output .= '<tr class="plugins_generic_lucene_highlighting"><td colspan=' . $hookParams['numCols'] . '>"...&nbsp;'
			. trim($this->_highlightedArticles[$articleId]) . '&nbsp;..."</td></tr>';
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

	/**
	 * @see templates/manager/sections/sectionForm.tpl
	 */
	function callbackTemplateSectionFormAdditionalMetadata($hookName, $params) {
		// Assign the ranking boost options to the template.
		$smarty =& $params[1];
		$smarty->assign('rankingBoostOptions', $this->_getRankingBoostOptions());

		// Render the template.
		$output =& $params[2];
		$output .= $smarty->fetch($this->getTemplateResource('additionalSectionMetadata.tpl'));
		return false;
	}


	//
	// Public methods
	//
	/**
	 * Generate an external boost file from usage statistics data.
	 * The file will be empty when an error condition is met.
	 * @param $timeFilter string Can be one of "all" (all-time statistics) or
	 *   "month" (last month only).
	 * @param $output boolean|string When true then write to stdout, otherwise
	 *   interpret the variable as file name and write to the given file.
	 */
	function generateBoostFile($timeFilter, $output = true) {
		// Check error conditions:
		// - the "ranking/sorting-by-metric" feature is not enabled
		// - a "main metric" is not configured
		$application = Application::getApplication();
		$metricType = $application->getDefaultMetricType();
		if (!($this->getSetting(0, 'rankingByMetric') || $this->getSetting(0, 'sortingByMetric')) ||
				empty($metricType)) return;

		// Retrieve a usage report for all articles ordered by the article ID.
		// Ordering seems to be important, see the remark about pre-sorting the file here:
		// https://lucene.apache.org/solr/api-3_6_2/org/apache/solr/schema/ExternalFileField.html
		$column = STATISTICS_DIMENSION_ARTICLE_ID;
		$filter = array(STATISTICS_DIMENSION_ASSOC_TYPE => array(ASSOC_TYPE_GALLEY, ASSOC_TYPE_ARTICLE));
		if ($timeFilter == 'month') {
			$oneMonthAgo = date('Ymd', strtotime('-1 month'));
			$today = date('Ymd');
			$filter[STATISTICS_DIMENSION_DAY] = array('from' => $oneMonthAgo, 'to' => $today);
		}
		$orderBy = array(STATISTICS_DIMENSION_ARTICLE_ID => STATISTICS_ORDER_ASC);
		$metricReport = $application->getMetrics($metricType, $column, $filter, $orderBy);
		if (empty($metricReport)) return;

		// Pluck the metric values and find the maximum.
		$max = max(array_map(function($reportRow) {
			return $reportRow['metric'];
		}, $metricReport));
		if ($max <= 0) return;

		// Get the Lucene plugin installation ID.
		$instId = $this->getSetting(0, 'instId');

		$file = null;
		if (is_string($output)) {
			// Write the result to a file.
			$file = fopen($output, 'w');
			if ($file === false) return;
		}

		// Normalize and return the metric values.
		// NB: We do not return values for articles that have no data.
		foreach ($metricReport as $reportRow) {
			// The normalization function is: 2 ^ (metric / max).
			// This normalizes the metric to values between 1.0 and 2.0.
			$record = $instId . '-' . $reportRow['submission_id'] . '=' .
					round(pow(2, $reportRow['metric'] / $max), 5) . PHP_EOL;
			if (is_null($file)) {
				echo $record;
			} else {
				fwrite($file, $record);
			}
		}

		if (!is_null($file)) fclose($file);
	}


	//
	// Private helper methods
	//
	/**
	 * Get all currently enabled facet categories.
	 * @return array
	 */
	function _getEnabledFacetCategories() {
		if (!is_array($this->_enabledFacetCategories)) {
			$this->_enabledFacetCategories = array();
			$availableFacetCategories = array(
				'discipline', 'subject', 'type', 'coverage',
				'journalTitle', 'authors', 'publicationDate'
			);
			foreach($availableFacetCategories as $facetCategory) {
				if ($this->getSetting(0, 'facetCategory' . ucfirst($facetCategory))) {
					$this->_enabledFacetCategories[] = $facetCategory;
				}
			}
		}
		return $this->_enabledFacetCategories;
	}

	/**
	 * Rebuild the index for all journals or a single journal
	 * @param $log boolean Whether to write the log to standard output.
	 * @param $journal Journal If given, only re-index this journal.
	 * @param $buildIndex boolean Whether to rebuild the journal index.
	 * @param $buildDictionaries boolean Whether to rebuild dictionaries.
	 * @param $messages string Return parameter for log message output.
	 * @return boolean True on success, otherwise false.
	 */
	function _rebuildIndex($log, $journal, $buildIndex, $buildDictionaries, $updateBoostFile, &$messages) {
		// Rebuilding the index can take a long time.
		@set_time_limit(0);
		$solrWebService = $this->getSolrWebService();

		if ($buildIndex) {
			// If we got a journal instance then only re-index
			// articles from that journal.
			$journalIdOrNull = (is_a($journal, 'Journal') ? $journal->getId() : null);

			// Clear index (if the journal id is null then
			// all journals will be deleted from the index).
			$this->_indexingMessage($log, 'LucenePlugin: ' . __('search.cli.rebuildIndex.clearingIndex') . ' ... ', $messages);
			$solrWebService->deleteArticlesFromIndex($journalIdOrNull);
			$this->_indexingMessage($log, __('search.cli.rebuildIndex.done') . PHP_EOL, $messages);

			// Re-build index, either of a single journal...
			if (is_a($journal, 'Journal')) {
				$journals = array($journal);
				unset($journal);
			// ...or for all journals.
			} else {
				$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
				$journalIterator = $journalDao->getAll();
				$journals = $journalIterator->toArray();
			}

			// We re-index journal by journal to partition the task a bit
			// and provide better progress information to the user.
			foreach($journals as $journal) {
				$this->_indexingMessage($log, 'LucenePlugin: ' . __('search.cli.rebuildIndex.indexing', array('journalName' => $journal->getLocalizedName())) . ' ', $messages);

				// Mark all articles in the journal for re-indexing.
				$numMarked = $this->_solrWebService->markJournalChanged($journal->getId());

				// Pull or push?
				if ($this->getSetting(0, 'pullIndexing')) {
					// When pull-indexing is configured then we leave it up to the
					// Solr server to decide when the updates will actually be done.
					$this->_indexingMessage($log, '... ' . __('plugins.generic.lucene.rebuildIndex.pullResult', array('numMarked' => $numMarked)) . PHP_EOL, $messages);
				} else {
					// In case of push indexing we immediately update the index.
					$numIndexed = 0;
					do {
						// We update the index in batches to reduce max memory usage
						// and make a few intermediate commits.
						$articlesInBatch = $solrWebService->pushChangedArticles(SOLR_INDEXING_MAX_BATCHSIZE, $journal->getId());
						if (is_null($articlesInBatch)) {
							$error = $solrWebService->getServiceMessage();
							$this->_indexingMessage($log, ' ' . __('search.cli.rebuildIndex.error') . (empty($error) ? '' : ": $error") . PHP_EOL, $messages);
							if (!$log) {
								// If logging is switched off then inform the
								// tech admin with an email (e.g. in the case of
								// an OJS upgrade).
								$this->_informTechAdmin($error, $journal);
							}
							return true;
						}
						$this->_indexingMessage($log, '.', $messages);
						$numIndexed += $articlesInBatch;
					} while ($articlesInBatch == SOLR_INDEXING_MAX_BATCHSIZE);
					$this->_indexingMessage($log, ' ' . __('search.cli.rebuildIndex.result', array('numIndexed' => $numIndexed)) . PHP_EOL, $messages);
				}
			}
		}

		if ($buildDictionaries) {
			// Rebuild dictionaries.
			$this->_indexingMessage($log, 'LucenePlugin: ' . __('plugins.generic.lucene.rebuildIndex.rebuildDictionaries') . ' ... ', $messages);
			$solrWebService->rebuildDictionaries();
			if ($updateBoostFile) $this->_indexingMessage($log, __('search.cli.rebuildIndex.done') . PHP_EOL, $messages);
		}

		// Remove the field cache file as additional fields may be available after re-indexing. If we don't
		// do this it may seem that indexing didn't work as the cache will only be invalidated after 24 hours.
		$cacheFile = 'cache/fc-plugins-lucene-fieldCache.php';
		if (file_exists($cacheFile)) {
			if (is_writable(dirname($cacheFile))) {
				unlink($cacheFile);
			} else {
				$this->_indexingMessage($log, 'LucenePlugin: ' . __('plugins.generic.lucene.rebuildIndex.couldNotDeleteFieldCache') . PHP_EOL, $messages);
			}
		}

		if ($updateBoostFile) {
			// Update the boost file.
			$this->_indexingMessage($log, 'LucenePlugin: ' . __('plugins.generic.lucene.rebuildIndex.updateBoostFile') . ' ... ', $messages);
			$this->_updateBoostFiles();
		}

		$this->_indexingMessage($log, __('search.cli.rebuildIndex.done') . PHP_EOL, $messages);

		return true;
	}

	/**
	 * Output an indexing message.
	 * @param $log boolean Whether to write the log to standard output.
	 * @param $message string The message to display/add.
	 * @param $messages string Return parameter for log message output.
	 */
	function _indexingMessage($log, $message, &$messages) {
		if ($log) echo $message;
		$messages .= $message;
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
	 * warning that an indexing error has occurred.
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
		$request = Application::getRequest();
		$site = $request->getSite();
		$mail->assignParams(
			array('siteName' => $site->getLocalizedTitle(), 'error' => $error)
		);

		// Send to the site's tech contact.
		$mail->addRecipient($site->getLocalizedContactEmail(), $site->getLocalizedContactName());

		// Send the mail.
		$mail->send($request);
	}

	/**
	 * Return the available ranking boost options.
	 * @return array
	 */
	function _getRankingBoostOptions() {
		return array(
			0 => __('plugins.generic.lucene.sectionForm.ranking.never'),
			1 => __('plugins.generic.lucene.sectionForm.ranking.low'),
			2 => __('plugins.generic.lucene.sectionForm.ranking.normal'),
			4 => __('plugins.generic.lucene.sectionForm.ranking.high')
		);
	}

	/**
	 * Generate and update the boost file.
	 */
	function _updateBoostFiles() {
		// Make sure that we have an embedded server.
		if ($this->getSetting(0, 'pullIndexing')) return;

		// Make sure that the ranking/sorting-by-metric feature is enabled.
		if (!($this->getSetting(0, 'rankingByMetric') || $this->getSetting(0, 'sortingByMetric'))) return;

		// Construct the file name.
		$ds = DIRECTORY_SEPARATOR;

		foreach (array('all', 'month') as $filter) {
			$fileName = Config::getVar('files', 'files_dir') . "${ds}lucene${ds}data${ds}external_usageMetric" . ucfirst($filter);

			// Find the next extension. We cannot write to the existing file
			// while it is in-use and locked (on Windows).
			// Solr lets us write a new file and will always use the
			// (alphabetically) last file. The older file will automatically
			// be deleted.
			$lastExtension = 0;
			foreach (glob($fileName . '.*') as $source) {
				$existingExtension = (int)pathinfo($source, PATHINFO_EXTENSION);
				if ($existingExtension > $lastExtension) $lastExtension = $existingExtension;
			}
			$newExtension = (string) $lastExtension + 1;
			$newExtension = str_pad($newExtension, 8, '0', STR_PAD_LEFT);

			// Generate the files.
			$this->generateBoostFile($filter, $fileName . '.' . $newExtension);
		}

		// Make the solr server aware of the boost file.
		$solr = $this->getSolrWebService();
		$solr->reloadExternalFiles();
	}
}
?>
