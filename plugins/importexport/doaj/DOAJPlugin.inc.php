<?php

/**
 * @file plugins/importexport/doaj/DOAJPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOAJPlugin
 * @ingroup plugins_importexport_doaj
 *
 * @brief DOAJ import/export plugin
 */

import('lib.pkp.classes.xml.XMLCustomWriter');

import('classes.plugins.ImportExportPlugin');

// Export types.
define('DOAJ_EXPORT_ISSUES', 0x01);
define('DOAJ_EXPORT_ARTICLES', 0x02);


define('DOAJ_XSD_URL', 'http://doaj.org/static/doaj/doajArticles.xsd');

class DOAJPlugin extends ImportExportPlugin {

	/** @var PubObjectCache */
	var $_cache;

	function _getCache() {
		if (!is_a($this->_cache, 'PubObjectCache')) {
			// Instantiate the cache.
			if (!class_exists('PubObjectCache')) { // Bug #7848
				$this->import('classes.PubObjectCache');
			}
			$this->_cache = new PubObjectCache();
		}
		return $this->_cache;
	}

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * @see PKPPlugin::getTemplatePath()
	 */
	function getTemplatePath() {
		return parent::getTemplatePath().'templates/';
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'DOAJPlugin';
	}

	/**
	 * Get the display name for this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.importexport.doaj.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.importexport.doaj.description');
	}

	/**
	 * Display the plugin
	 * @param $args array
	 *
	 * This supports the following actions:
	 * - unregistered, issues, articles: lists with exportable objects
	 * - markRegistered: mark a single object (article, issue) as registered
	 * - export: export a single object (article, issue)
	 */
	function display($args, $request) {
		$templateMgr = TemplateManager::getManager();
		parent::display($args, $request);
		$journal = $request->getJournal();

		switch (array_shift($args)) {
			case 'unregistered':
				return $this->_displayArticleList($templateMgr, $journal, true);
				break;
			case 'issues':
				return $this->_displayIssueList($templateMgr, $journal);
				break;
			case 'articles':
				return $this->_displayArticleList($templateMgr, $journal);
				break;
			case 'process':
				return $this->_process($request, $journal);
				break;
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	/**
	 * Export a journal's content
	 * @param $journal object
	 * @param $selectedObjects array
	 * @param $outputFile string
	 */
	function _exportJournal($journal, $selectedObjects, $outputFile = null) {
		$this->import('classes.DOAJExportDom');
		$doc = XMLCustomWriter::createDocument();

		$journalNode = DOAJExportDom::generateJournalDom($doc, $journal, $selectedObjects);
		$journalNode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$journalNode->setAttribute('xsi:noNamespaceSchemaLocation', DOAJ_XSD_URL);
		XMLCustomWriter::appendChild($doc, $journalNode);

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'wb'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"journal-" . $journal->getId() . ".xml\"");
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	/**
	 * Label articles (on article or issue level) with a 'doaj::registered' flag
	 * @param $request PKPRequest
	 * @param $selectedObjects array
	 */
	function _markRegistered($request, $selectedObjects) {

		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$this->registerDaoHook('ArticleDAO');

		// check for articles
		$selectedArticles = $selectedObjects[DOAJ_EXPORT_ARTICLES];
		if (is_array($selectedArticles) && !empty($selectedArticles)) {

			foreach($selectedArticles as $articleId) {
				$article = $articleDao->getArticle($articleId);
				$article->setData('doaj::registered', 1);
				$articleDao->updateArticle($article);
			}
		}

		// check for issues
		$selectedIssues = $selectedObjects[DOAJ_EXPORT_ISSUES];
		if (is_array($selectedIssues) && !empty($selectedIssues)) {

			foreach($selectedIssues as $issueId) {
				$articles = $this->_retrieveArticlesByIssueId($issueId);
				foreach($articles as $article) {
					$article->setData('doaj::registered', 1);
					$articleDao->updateArticle($article);
				}
			}
		}

		// show message & redirect
		$this->_sendNotification(
			$request,
			'plugins.importexport.doaj.markRegistered.success',
			NOTIFICATION_TYPE_SUCCESS
		);
		
		$action = '';
		switch($request->getUserVar('target')) {
			case('article'):
				$action = 'articles';
				break;
			case('issue'):
				$action = 'issues';
				break;
			default: assert(false);
		}
		$request->redirect(null, null, null, array('plugin', $this->getName(), $action));
	}

	/**
	 * Display a list of issues for export.
	 * @param $templateMgr TemplateManager
	 * @param $journal Journal
	 */
	function _displayIssueList($templateMgr, $journal) {
		$this->setBreadcrumbs(array(), true);

		// Retrieve all published issues.
		AppLocale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR));
		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issueIterator = $issueDao->getPublishedIssues($journal->getId(), Handler::getRangeInfo('issues'));

		// check whether all articles of an issue are doaj::registered or not
		$issues = array();
		while ($issue = $issueIterator->next()) {
			$issueId = $issue->getId();
			$articles = $this->_retrieveArticlesByIssueId($issueId);
			$allArticlesRegistered = true;

			foreach($articles as $article) {
				if (!$article->getData('doaj::registered')) {
					$allArticlesRegistered = false;
					break;
				}
			}

			$issue->setData('doaj::registered', $allArticlesRegistered);
			$issues[] = $issue;
			unset($issue);
		}
		unset($issueIterator);

		// Instantiate issue iterator.
		import('lib.pkp.classes.core.ArrayItemIterator');
		$rangeInfo = Handler::getRangeInfo('articles');
		$iterator = new ArrayItemIterator($issues, $rangeInfo->getPage(), $rangeInfo->getCount());

		// Prepare and display the issue template.
		$templateMgr->assign_by_ref('issues', $iterator);
		$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
	}

	/**
	 * Display a list of articles for export.
	 * @param $templateMgr TemplateManager
	 * @param $journal Journal
	 * @param $unregistered boolean
	 */
	function _displayArticleList($templateMgr, $journal, $unregistered = false) {
		$this->setBreadcrumbs(array(), true);

		if ($unregistered == false) {
			// Retrieve all published articles.
			$articles = $this->_getAllPublishedArticles($journal);
		} else {
			// Retrieve array elements without index "doaj::registered"
			$articles = array_filter($this->_getAllPublishedArticles($journal), create_function('$article', 'return !$article["article"]->getData("doaj::registered");'));
		}
		
		// Paginate articles.
		$totalArticles = count($articles);
		$rangeInfo = Handler::getRangeInfo('articles');
		if ($rangeInfo->isValid()) {
			// Instantiate article iterator.
			import('lib.pkp.classes.core.VirtualArrayIterator');
			$iterator = new VirtualArrayIterator($articles, $totalArticles, $rangeInfo->getPage(), $rangeInfo->getCount());

			// Prepare and display the article template.
			$templateMgr->assign_by_ref('articles', $iterator);
			$templateMgr->display($this->getTemplatePath() . 'articles.tpl');
		}
	}

	/**
	 * Retrieve all published articles.
	 * @param $journal Journal
	 * @return array
	 */
	function _getAllPublishedArticles($journal) {
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$articleIterator = $publishedArticleDao->getPublishedArticlesByJournalId($journal->getId());

		// Return articles from published issues only.
		$articles = array();
		while ($article = $articleIterator->next()) {
			$articles[] = $this->_prepareArticleData($article, $journal);
			unset($article);
		}
		unset($articleIterator);

		return $articles;
	}

	/**
	 * Return the issue of an article.
	 *
	 * The issue will be cached if it is not yet cached.
	 *
	 * @param $article Article
	 * @param $journal Journal
	 *
	 * @return Issue
	 */
	function _getArticleIssue($article, $journal) {
		$issueId = $article->getIssueId();

		// Retrieve issue if not yet cached.
		$cache = $this->_getCache();
		if (!$cache->isCached('issues', $issueId)) {
			$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issue = $issueDao->getIssueById($issueId, $journal->getId(), true);
			assert(is_a($issue, 'Issue'));
			$nullVar = null;
			$cache->add($issue, $nullVar);
			unset($issue);
		}

		return $cache->get('issues', $issueId);
	}

	/**
	 * Retrieve all articles for the given issue
	 * and commit them to the cache.
	 * @param $issue Issue
	 * @return array
	 */
	function _retrieveArticlesByIssueId($issueId) {
		$articlesByIssue = array();
		$cache = $this->_getCache();

		if (!$cache->isCached('articlesByIssue', $issueId)) {
			$articleDao = DAORegistry::getDAO('PublishedArticleDAO');
			$articles = $articleDao->getPublishedArticles($issueId);
			if (!empty($articles)) {
				foreach ($articles as $article) {
					$cache->add($article, $nullVar);
					unset($article);
				}
				$cache->markComplete('articlesByIssue', $issueId);
				$articlesByIssue = $cache->get('articlesByIssue', $issueId);
			}
		}
		return $articlesByIssue;
	}

	/**
	 * Identify the issue of the given article.
	 * @param $article PublishedArticle
	 * @param $journal Journal
	 * @return array|null Return prepared article data or
	 *  null if the article is not from a published issue.
	 */
	function _prepareArticleData($article, $journal) {
		$nullVar = null;

		// Add the article to the cache.
		$cache = $this->_getCache();
		$cache->add($article, $nullVar);

		// Retrieve the issue.
		$issue = $this->_getArticleIssue($article, $journal);

		if ($issue->getPublished()) {
			$articleData = array(
				'article' => $article,
				'issue' => $issue
			);
			return $articleData;
		} else {
			return $nullVar;
		}
	}

	/**
	 * Return the object types supported by this plug-in.
	 * @return array An array with object names and the
	 *  corresponding export types.
	 */
	function _getAllObjectTypes() {
		return array(
			'issue' => DOAJ_EXPORT_ISSUES,
			'article' => DOAJ_EXPORT_ARTICLES,
		);
	}

	/**
	 * Process a request.
	 * @param $request PKPRequest
	 * @param $journal Journal
	 */
	function _process($request, $journal) {
		$objectTypes = $this->_getAllObjectTypes();
		$target = $request->getUserVar('target');
		$selectedIds = array();
		$action = '';
		
		switch($target) {
			case('article'):
				$action = 'articles';
				$selectedIds = (array) $request->getUserVar('articleId');
				break;
			case('issue'):
				$action = 'issues';
				$selectedIds = (array) $request->getUserVar('issueId');
				break;
			default: assert(false);
		}
		
		if (empty($selectedIds)) {
			$request->redirect(null, null, null, array('plugin', $this->getName(), $action));
		}

		$selectedObjects = array($objectTypes[$target] => $selectedIds);

		if ($request->getUserVar('export')) {
			return $this->_exportJournal($journal, $selectedObjects);
		}
		if ($request->getUserVar('markRegistered')) {
			$this->_markRegistered($request, $selectedObjects);
		}
		return false;
	}

	/**
	 * Register the hook that adds an
	 * additional field name to objects.
	 * @param $daoName string
	 */
	function registerDaoHook($daoName) {
		HookRegistry::register(strtolower_codesafe($daoName) . '::getAdditionalFieldNames', array($this, '_getAdditionalFieldNames'));
	}

	/**
	 * Hook callback that returns the "daoj:registered" flag
	 * @param $hookName string
	 * @param $args array
	 */
	function _getAdditionalFieldNames($hookName, $args) {
		assert(count($args) == 2);
		$returner =& $args[1];
		assert(is_array($returner));
		$returner[] = 'doaj::registered';
	}

	/**
	 * Add a notification.
	 * @param $request Request
	 * @param $message string An i18n key.
	 * @param $notificationType integer One of the NOTIFICATION_TYPE_* constants.
	 * @param $param string An additional parameter for the message.
	 */
	function _sendNotification($request, $message, $notificationType, $param = null) {
		static $notificationManager = null;

		if (is_null($notificationManager)) {
			import('classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
		}

		if (!is_null($param)) {
			$params = array('param' => $param);
		} else {
			$params = null;
		}

		$user = $request->getUser();
		$notificationManager->createTrivialNotification(
			$user->getId(),
			$notificationType,
			array('contents' => __($message, $params))
		);
	}
}
?>