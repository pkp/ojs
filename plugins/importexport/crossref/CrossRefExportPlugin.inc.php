<?php

/**
 * @file plugins/importexport/crossref/CrossRefExportPlugin.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossRefExportPlugin
 * @ingroup plugins_importexport_crossref
 *
 * @brief CrossRef export/registration plugin.
 */


if (!class_exists('DOIExportPlugin')) { // Bug #7848
	import('plugins.importexport.crossref.classes.DOIExportPlugin');
}

define('CROSSREF_STATUS_SUBMITTED', 'submitted');
define('CROSSREF_STATUS_COMPLETED', 'completed');
define('CROSSREF_STATUS_FAILED', 'failed');
define('CROSSREF_STATUS_REGISTERED', 'found');
define('CROSSREF_STATUS_MARKEDREGISTERED', 'markedRegistered');
define('CROSSREF_STATUS_NOT_DEPOSITED', 'notDeposited');

// DataCite API
define('CROSSREF_API_DEPOSIT_OK', 303);
define('CROSSREF_API_RESPONSE_OK', 200);
define('CROSSREF_API_URL', 'https://api.crossref.org/deposits');

//TESTING
//define('CROSSREF_API_URL', 'https://api.crossref.org/deposits?test=true');

define('CROSSREF_SEARCH_API', 'http://search.crossref.org/dois');

define('CROSSREF_WORKS_API', 'http://api.crossref.org/works/');

// The name of the settings used to save the registered DOI and the URL with the deposit status.
define('CROSSREF_DEPOSIT_STATUS', 'depositStatus');

class CrossRefExportPlugin extends DOIExportPlugin {

	//
	// Constructor
	//
	function CrossRefExportPlugin() {
		parent::DOIExportPlugin();
	}


	//
	// Implement template methods from ImportExportPlugin
	//
	/**
	 * @see ImportExportPlugin::getName()
	 */
	function getName() {
		return 'CrossRefExportPlugin';
	}

	/**
	 * @see ImportExportPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.crossref.displayName');
	}

	/**
	 * @see ImportExportPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.crossref.description');
	}

	/**
	 * @see LazyLoadPlugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed')) return false;

		if ($success) {
			HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'callbackParseCronTab'));
		}
		return $success;
	}


	//
	// Implement template methods from DOIExportPlugin
	//
	/**
	 * @see DOIExportPlugin::getPluginId()
	 */
	function getPluginId() {
		return 'crossref';
	}

	/**
	 * @see DOIExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'CrossRefSettingsForm';
	}

	/**
	 * @see DOIExportPlugin::getAllObjectTypes()
	 */
	function getAllObjectTypes() {
		return array(
			'issue' => DOI_EXPORT_ISSUES,
			'article' => DOI_EXPORT_ARTICLES
		);
	}

	/**
	 * Process a DOI activity request.
	 * @param $request PKPRequest
	 * @param $journal Journal
	 */
	function process(&$request, &$journal) {
		if ($request->getUserVar('checkStatus')) {
			// Update status is awailable only for articles
			$articleIds = (array) $request->getUserVar('articleId');
			$articles =& $this->_getObjectsFromIds(DOI_EXPORT_ARTICLES, $articleIds, $journal->getId(), $errors);
			foreach ($articles as $article) {
				$this->updateDepositStatus($request, $journal, $article);
			}
			$request->redirect(
				null, null, null,
				array('plugin', $this->getName(), 'articles'),
				null
			);
		} else {
			parent::process($request, $journal);
		}
	}

	/**
	 * Display a list of issues for export.
	 * @param $templateMgr TemplateManager
	 * @param $journal Journal
	 */
	function displayIssueList(&$templateMgr, &$journal) {
		$this->setBreadcrumbs(array(), true);

		// Retrieve all published issues.
		AppLocale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR));
		$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$this->registerDaoHook('IssueDAO');
		$issueIterator =& $issueDao->getPublishedIssues($journal->getId(), Handler::getRangeInfo('issues'));

		// Get issues that should be excluded i.e. that have no objects eligible to export/register.
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$excludes = array();
		$allExcluded = true;
		$numArticles = array();
		while ($issue =& $issueIterator->next()) {
			$excludes[$issue->getId()] = true;
			$issueArticles =& $publishedArticleDao->getPublishedArticles($issue->getId());
			$issueArticlesNo = 0;
			$allArticlesRegistered[$issue->getId()] = true;
			foreach ($issueArticles as $issueArticle) {
				$articleRegistered = $issueArticle->getData($this->getPluginId().'::registeredDoi');
				$errors = array();
				if ($this->canBeExported($issueArticle, $errors)) {
					$excludes[$issue->getId()] = false;
					$allExcluded = false;
					$issueArticlesNo++;
				}
				if ($allArticlesRegistered[$issue->getId()] && !isset($articleRegistered)) {
					$allArticlesRegistered[$issue->getId()] = false;
				}
			}
			$numArticles[$issue->getId()] = $issueArticlesNo;
			unset($issue);
		}
		unset($issueIterator);

		// Prepare and display the issue template.
		// Get the issue iterator from the DB for the template again.
		$issueIterator =& $issueDao->getPublishedIssues($journal->getId(), Handler::getRangeInfo('issues'));
		$templateMgr->assign_by_ref('issues', $issueIterator);
		$templateMgr->assign('allExcluded', $allExcluded);
		$templateMgr->assign('excludes', $excludes);
		$templateMgr->assign('numArticles', $numArticles);
		$templateMgr->assign('allArticlesRegistered', $allArticlesRegistered);
		$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
	}

	/**
	 * @copydoc DOIExportPlugin::displayArticleList
	 */
	function displayArticleList(&$templateMgr, &$journal) {
		// Prepare information specific to this plug-in.
		$this->setBreadcrumbs(array(), true);

		$filter = $templateMgr->get_template_vars('filter');
		// Retrieve all published articles.
		$this->registerDaoHook('PublishedArticleDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		$articles = array();
		if ($filter) {
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
			if ($filter == CROSSREF_STATUS_NOT_DEPOSITED) {
				$allArticles = $publishedArticleDao->getBySetting($this->getDepositStatusSettingName(), null, $journal->getId());
			} else {
				$allArticles = $publishedArticleDao->getBySetting($this->getDepositStatusSettingName(), $filter, $journal->getId());
			}
		} else {
			$allArticles = $this->getAllPublishedArticles($journal);
		}

		// Retrieve article data.
		$articleData = array();
		$errors = array();
		foreach($allArticles as $article) {
			if ($this->canBeExported($article, $errors)) {
				$preparedArticle = $this->_prepareArticleData($article, $journal);
				if (is_array($preparedArticle)) {
					$articleData[] = $preparedArticle;
					$articles[] = $article;
				}
			}
			unset($article, $preparedArticle);
		}
		unset($articles);

		// Paginate articles.
		$totalArticles = count($articleData);
		$rangeInfo = Handler::getRangeInfo('articles');
		if ($rangeInfo->isValid()) {
			$articleData = array_slice($articleData, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
		}
		// Instantiate article iterator.
		import('lib.pkp.classes.core.VirtualArrayIterator');
		$iterator = new VirtualArrayIterator($articleData, $totalArticles, $rangeInfo->getPage(), $rangeInfo->getCount());

		// Prepare and display the article template.
		$templateMgr->assign_by_ref('articles', $iterator);
		$templateMgr->assign('depositStatusSettingName', $this->getDepositStatusSettingName());
		$templateMgr->assign('depositStatusUrlSettingName', $this->getDepositStatusUrlSettingName());
		$templateMgr->assign('statusMapping', $this->getStatusMapping());
		$templateMgr->assign('isEditor', Validation::isEditor($journal->getId()));
		$templateMgr->display($this->getTemplatePath() . 'articles.tpl');
	}

	/**
	 * The selected issue can be exported if it contains an article that has a DOI.
	 * The selected article can be exported if it has a DOI.
	 * @param $foundObject Issue|PublishedArticle
	 * @param $errors array
	 * @return array|boolean
	*/
	function canBeExported($foundObject, &$errors) {
		if (is_a($foundObject, 'Issue')) {
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
			$issueArticles =& $publishedArticleDao->getPublishedArticles($foundObject->getId());
			foreach ($issueArticles as $issueArticle) {
				// if just one article can be exported, than the issue can be exported
				if (parent::canBeExported($issueArticle, $errors)) {
					return true;
				}
			}
		}
		if (is_a($foundObject, 'PublishedArticle')) {
			return parent::canBeExported($foundObject, $errors);
		}
		return false;
	}

	/**
	 * @see DOIExportPlugin::generateExportFiles()
	 */
	function generateExportFiles(&$request, $exportType, &$objects, $targetPath, &$journal, &$errors) {
		// Additional locale file.
		AppLocale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR));

		$this->import('classes.CrossRefExportDom');
		$dom = new CrossRefExportDom($request, $this, $journal, $this->getCache());
		$doc =& $dom->generate($objects);
		if ($doc === false) {
			$errors =& $dom->getErrors();
			return false;
		}

		// Write the result to the target file.
		$exportFileName = $this->getTargetFileName($targetPath, $exportType);
		file_put_contents($exportFileName, XMLCustomWriter::getXML($doc));
		$generatedFiles = array($exportFileName => &$objects);
		return $generatedFiles;
	}

	/**
	 * @see DOIExportPlugin::processMarkRegistered()
	 */
	function processMarkRegistered(&$request, $exportType, &$objects, &$journal) {
		$articleDao =& DAORegistry::getDAO('ArticleDAO');  /* @var $articleDao ArticleDAO */
		$this->import('classes.CrossRefExportDom');
		$dom = new CrossRefExportDom($request, $this, $journal, $this->getCache());
		$statusUpdatePossible = $this->getSetting($journal->getId(), 'username') && $this->getSetting($journal->getId(), 'password');
		foreach($objects as $object) {
			if (is_a($object, 'Issue')) {
				$articlesByIssue =& $dom->retrieveArticlesByIssue($object);
				foreach ($articlesByIssue as $article) {
					if ($article->getPubId('doi')) {
						$articleDao->updateSetting($article->getId(), $this->getDepositStatusSettingName(), CROSSREF_STATUS_MARKEDREGISTERED, 'string');
						$this->markRegistered($request, $article);
					}
				}
			} else {
				if ($object->getPubId('doi')) {
					$articleDao->updateSetting($object->getId(), $this->getDepositStatusSettingName(), CROSSREF_STATUS_MARKEDREGISTERED, 'string');
					$this->markRegistered($request, $object);
				}
			}
		}
	}

	/**
	 * @see DOIExportPlugin::registerDoi()
	 */
	function registerDoi(&$request, &$journal, &$objects, $filename) {
		$curlCh = curl_init();
		if ($httpProxyHost = Config::getVar('proxy', 'http_host')) {
			curl_setopt($curlCh, CURLOPT_PROXY, $httpProxyHost);
			curl_setopt($curlCh, CURLOPT_PROXYPORT, Config::getVar('proxy', 'http_port', '80'));
			if ($username = Config::getVar('proxy', 'username')) {
				curl_setopt($curlCh, CURLOPT_PROXYUSERPWD, $username . ':' . Config::getVar('proxy', 'password'));
			}
		}
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);
		curl_setopt($curlCh, CURLOPT_HEADER, 1);
		curl_setopt($curlCh, CURLOPT_BINARYTRANSFER, true);

		$username = $this->getSetting($journal->getId(), 'username');
		$password = $this->getSetting($journal->getId(), 'password');

		curl_setopt($curlCh, CURLOPT_URL, CROSSREF_API_URL);
		curl_setopt($curlCh, CURLOPT_USERPWD, "$username:$password");

		// Transmit XML data.
		assert(is_readable($filename));
		$fh = fopen($filename, 'rb');

		$httpheaders = array();
		$httpheaders[] = 'Content-Type: application/vnd.crossref.deposit+xml';
		$httpheaders[] = 'Content-Length: ' . filesize($filename);

		curl_setopt($curlCh, CURLOPT_HTTPHEADER, $httpheaders);
		curl_setopt($curlCh, CURLOPT_INFILE, $fh);
		curl_setopt($curlCh, CURLOPT_INFILESIZE, filesize($filename));

		$response = curl_exec($curlCh);
		if ($response === false) {
			$result = array(array('plugins.importexport.crossref.register.error.mdsError', 'No response from server.'));
		} elseif ( $status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE) != CROSSREF_API_DEPOSIT_OK ) {
			$result = array(array('plugins.importexport.crossref.register.error.mdsError', "$status - $response"));
		} else {
			// Deposit was received
			$result = true;
			$articleDao =& DAORegistry::getDAO('ArticleDAO');  /* @var $articleDao ArticleDAO */
			foreach ($objects as $article) {
				// its possible that issues, galleys, or other things are being registered
				// but we're only going to be going back to check in on articles
				if (is_a($article, 'Article')) {
					// update the status and save the URL of the last deposit
					// (note: the registration could be done outside the system, so it is better to always update the URL together with the status)
					$this->updateDepositStatus($request, $journal, $article);
				}
			}
		}

		curl_close($curlCh);
		return $result;
	}

	/**
	 * This method checks the CrossRef APIs, if deposits and registration have been successful
	 * @param $request Request
	 * @param $journal Journal The journal associated with the deposit
	 * @param $article Article The article getting deposited
	 */
	function updateDepositStatus(&$request, &$journal, $article) {
		$articleDao =& DAORegistry::getDAO('ArticleDAO');  /* @var $articleDao ArticleDAO */
		import('lib.pkp.classes.core.JSONManager');
		$jsonManager = new JSONManager();

		// Prepare HTTP session.
		$curlCh = curl_init();
		if ($httpProxyHost = Config::getVar('proxy', 'http_host')) {
			curl_setopt($curlCh, CURLOPT_PROXY, $httpProxyHost);
			curl_setopt($curlCh, CURLOPT_PROXYPORT, Config::getVar('proxy', 'http_port', '80'));
			if ($username = Config::getVar('proxy', 'username')) {
				curl_setopt($curlCh, CURLOPT_PROXYUSERPWD, $username . ':' . Config::getVar('proxy', 'password'));
			}
		}
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);

		$username = $this->getSetting($journal->getId(), 'username');
		$password = $this->getSetting($journal->getId(), 'password');
		curl_setopt($curlCh, CURLOPT_USERPWD, "$username:$password");

		$doi = urlencode($article->getPubId('doi'));
		$params = 'filter=doi:' . $doi ;
		curl_setopt(
			$curlCh,
			CURLOPT_URL,
			CROSSREF_API_URL . (strpos(CROSSREF_API_URL,'?')===false?'?':'&') . $params
		);

		// try to fetch from the new API
		$response = curl_exec($curlCh);

		// try the new API with the filter completed (should only return successes)
		if ( $response && curl_getinfo($curlCh, CURLINFO_HTTP_CODE) == CROSSREF_API_RESPONSE_OK ) {
			$response = $jsonManager->decode($response);
			$pastDeposits = array();
			foreach ($response->message->items as $item) {
				$pastDeposits[strtotime($item->{'submitted-at'})] = array('status' => $item->status, 'batch-id' => $item->{'batch-id'});
			}

			// if there have been past attempts, save the most recent one's status for display to user
			if (count($pastDeposits) > 0) {
				$lastDeposit = $pastDeposits[max(array_keys($pastDeposits))];
				$lastStatus = $lastDeposit['status'];
				$lastBatchId = $lastDeposit['batch-id'];
				// If batch-id changed
				if ($article->getData($this->getDepositStatusUrlSettingName()) != '/deposits/'.$lastBatchId) {
					// Update the depositStausUrl
					$articleDao->updateSetting($article->getId(), $this->getDepositStatusUrlSettingName(), '/deposits/'.$lastBatchId, 'string');
				}
				if ($lastStatus == CROSSREF_STATUS_COMPLETED) {
					// check if the DOI is active (there is a delay between a deposit completing successfully and a DOI being 'ready').
					curl_setopt(
						$curlCh,
						CURLOPT_URL,
						CROSSREF_WORKS_API . $doi
					);
					$response = curl_exec($curlCh);
					if ($response && curl_getinfo($curlCh, CURLINFO_HTTP_CODE) == CROSSREF_API_RESPONSE_OK) {
						// set the status, because we will need to check it for the automatic registration
						$article->setData($this->getDepositStatusSettingName(), CROSSREF_STATUS_REGISTERED);
						// Update the status to registered
						$articleDao->updateSetting($article->getId(), $this->getDepositStatusSettingName(), CROSSREF_STATUS_REGISTERED, 'string');
						$this->markRegistered($request, $article);
						return true;
					}
				}
				// If status changed
				if ($article->getData($this->getDepositStatusSettingName()) != $lastStatus) {
					// set the status, because we will need to check it for the automatic registration
					$article->setData($this->getDepositStatusSettingName(), $lastStatus);
					// Update the last deposit status
					$articleDao->updateSetting($article->getId(), $this->getDepositStatusSettingName(), $lastStatus, 'string');
				}
				if ($article->getData($this->getPluginId() . '::' . DOI_EXPORT_REGDOI)) {
					// apparently there was a new registreation i.e. update
					// remove the setting defining the article as registered, for the article to be considered for automatic status updates
					$articleDao->updateSetting($article->getId(), $this->getPluginId() . '::' . DOI_EXPORT_REGDOI, null, 'string');
				}
			}
		}

		curl_close($curlCh);

		return false;
	}

	/**
	 * @see AcronPlugin::parseCronTab()
	 */
	function callbackParseCronTab($hookName, $args) {
		$taskFilesPath =& $args[0];
		$taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';

		return false;
	}

	function getDepositStatusSettingName() {
		return $this->getPluginId() . '::' . CROSSREF_DEPOSIT_STATUS;
	}

	function getDepositStatusUrlSettingName() {
		return $this->getPluginId() . '::' . CROSSREF_DEPOSIT_STATUS . 'Url';
	}

	/**
	 * Get status mapping for the status display.
	 * @return array (internal status => string text to be displayed)
	 */
	function getStatusMapping() {
		return array(
				CROSSREF_STATUS_SUBMITTED => __('plugins.importexport.crossref.status.submitted'),
				CROSSREF_STATUS_COMPLETED => __('plugins.importexport.crossref.status.completed'),
				CROSSREF_STATUS_FAILED => __('plugins.importexport.crossref.status.failed'),
				CROSSREF_STATUS_REGISTERED => __('plugins.importexport.crossref.status.registered'),
				CROSSREF_STATUS_MARKEDREGISTERED => __('plugins.importexport.crossref.status.markedRegistered')
		);
	}

}

?>
