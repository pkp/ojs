<?php

/**
 * @file plugins/importexport/crossref/CrossRefExportPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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

// DataCite API
define('CROSSREF_API_DEPOSIT_OK', 303);
define('CROSSREF_API_RESPONSE_OK', 200);
define('CROSSREF_API_URL', 'https://api.crossref.org/deposits');

//TESTING
//define('CROSSREF_API_URL', 'https://api.crossref.org/deposits?test=true');

define('CROSSREF_SEARCH_API', 'http://search.crossref.org/dois');

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

		// Filter only issues that contain an article that have a DOI assigned.
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$issues = array();
		$numArticles = array();
		while ($issue =& $issueIterator->next()) {
			$issueArticles =& $publishedArticleDao->getPublishedArticles($issue->getId());
			$issueArticlesNo = 0;
			$allArticlesRegistered = true;
			foreach ($issueArticles as $issueArticle) {
				$articleRegistered = $issueArticle->getData('crossref::registeredDoi');
				if ($issueArticle->getPubId('doi') && !isset($articleRegistered)) {
					if (!in_array($issue, $issues)) $issues[] = $issue;
					$issueArticlesNo++;
				}
				if ($allArticlesRegistered && !isset($articleRegistered)) {
					$allArticlesRegistered = false;
				}
			}
			$numArticles[$issue->getId()] = $issueArticlesNo;
		}

		// Instantiate issue iterator.
		import('lib.pkp.classes.core.ArrayItemIterator');
		$rangeInfo = Handler::getRangeInfo('articles');
		$iterator = new ArrayItemIterator($issues, $rangeInfo->getPage(), $rangeInfo->getCount());

		// Prepare and display the issue template.
		$templateMgr->assign_by_ref('issues', $iterator);
		$templateMgr->assign('numArticles', $numArticles);
		$templateMgr->assign('allArticlesRegistered', $allArticlesRegistered);
		$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
	}

	/**
	 * @see DOIExportPlugin::displayAllUnregisteredObjects()
	 */
	function displayAllUnregisteredObjects(&$templateMgr, &$journal) {
		// Prepare information specific to this plug-in.
		$this->setBreadcrumbs(array(), true);
		AppLocale::requireComponents(array(LOCALE_COMPONENT_PKP_SUBMISSION));

		// Prepare and display the template.
		$templateMgr->assign_by_ref('articles', $this->_getUnregisteredArticles($journal));
		$templateMgr->assign('depositStatusSettingName', $this->getDepositStatusSettingName());
		$templateMgr->assign('depositStatusUrlSettingName', $this->getDepositStatusUrlSettingName());
		$templateMgr->display($this->getTemplatePath() . 'all.tpl');
	}

	/**
	 * @copydoc DOIExportPlugin::displayArticleList
	 */
	function displayArticleList(&$templateMgr, &$journal) {
		$templateMgr->assign('depositStatusSettingName', $this->getDepositStatusSettingName());
		$templateMgr->assign('depositStatusUrlSettingName', $this->getDepositStatusUrlSettingName());
		return parent::displayArticleList($templateMgr, $journal);
	}

	/**
	 * The selected issue can be exported if it contains an article that has a DOI,
	 * and the articles containing a DOI also have a date published.
	 * The selected article can be exported if it has a DOI and a date published.
	 * @param $foundObject Issue|PublishedArticle
	 * @param $errors array
	 * @return array|boolean
	*/
	function canBeExported($foundObject, &$errors) {
		if (is_a($foundObject, 'Issue')) {
			$export = false;
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
			$issueArticles =& $publishedArticleDao->getPublishedArticles($foundObject->getId());
			foreach ($issueArticles as $issueArticle) {
				if (!is_null($issueArticle->getPubId('doi'))) {
					$export = true;
					if (is_null($issueArticle->getDatePublished())) {
						$errors[] = array('plugins.importexport.crossref.export.error.articleDatePublishedMissing', $issueArticle->getId());
						return false;
					}
				}
			}
			return $export;
		}
		if (is_a($foundObject, 'PublishedArticle')) {
			if (is_null($foundObject->getDatePublished())) {
				$errors[] = array('plugins.importexport.crossref.export.error.articleDatePublishedMissing', $foundObject->getId());
				return false;
			}
			return parent::canBeExported($foundObject, $errors);
		}
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
		$this->import('classes.CrossRefExportDom');
		$dom = new CrossRefExportDom($request, $this, $journal, $this->getCache());
		foreach($objects as $object) {
			if (is_a($object, 'Issue')) {
				$articlesByIssue =& $dom->retrieveArticlesByIssue($object);
				foreach ($articlesByIssue as $article) {
					if ($article->getPubId('doi')) {
						$this->markRegistered($request, $article);
					}
				}
			} else {
				if ($object->getPubId('doi')) {
					$this->markRegistered($request, $object);
				}
			}
		}
	}

	/**
	 * @see DOIExportPlugin::registerDoi()
	 */
	function registerDoi(&$request, &$journal, &$objects, $filename) {
		$curlCh = curl_init ();
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
			$result = array(array('plugins.importexport.common.register.error.mdsError', 'No response from server.'));
		} elseif ( $status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE) != CROSSREF_API_DEPOSIT_OK ) {
			$result = array(array('plugins.importexport.common.register.error.mdsError', "$status - $response"));
		} else {
			// Deposit was received
			$result = true;
			$depositLocationArray = $this->_http_parse_headers($response);
			$depositLocation = $depositLocationArray['Location'];
			$articleDao =& DAORegistry::getDAO('ArticleDAO');  /* @var $articleDao ArticleDAO */
			foreach ($objects as $article) {
				// its possible that issues, galleys, or other things are being registered
				// but we're only going to be going back to check in on articles
				if (is_a($article, 'Article')) {
					// we only save the URL of the last deposit so it can be checked later on
					$articleDao->updateSetting($article->getId(), $this->getDepositStatusUrlSettingName(), $depositLocation, 'string');

					// update the status of the DOIs
					$this->updateDepositStatus($request, $journal, $article);
				}
			}
		}

		curl_close($curlCh);
		return $result;
	}

	/**
	 * This method checks the CrossRef APIs and checks if deposits have been successful
	 * @param $request Request
	 * @param $journal Journal The journal associated with the deposit
	 * @param $article Article The article getting deposited
	 */
	function updateDepositStatus(&$request, &$journal, $article) {
		$articleDao =& DAORegistry::getDAO('ArticleDAO');  /* @var $articleDao ArticleDAO */
		import('lib.pkp.classes.core.JSONManager');
		$jsonManager = new JSONManager();

		// Prepare HTTP session.
		$curlCh = curl_init ();
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
				$pastDeposits[strtotime($item->{'submitted-at'})] = $item->status;
				if ( $item->status == 'completed' ) {
					$articleDao->updateSetting($article->getId(), $this->getDepositStatusSettingName(), 'completed', 'string');
					$this->markRegistered($request, $article);
					return true;
				}
				if ( $item->status == 'failed' ) {
					$articleDao->updateSetting($article->getId(), $this->getDepositStatusSettingName(), 'failed', 'string');
				}
				elseif ( $item->status == 'submitted' ) {
					$articleDao->updateSetting($article->getId(), $this->getDepositStatusSettingName(), 'submitted', 'string');
				}
			}

			// if there have been past attempts, save the most recent one's status for display to user
			if (count($pastDeposits) > 0) {
				$lastStatus = $pastDeposits[max(array_keys($pastDeposits))];
				$articleDao->updateSetting($article->getId(), $this->getDepositStatusSettingName(), $lastStatus, 'string');
			}
		}

		// now try the old crossref API and just search for the DOI
		curl_setopt(
			$curlCh,
			CURLOPT_URL,
			CROSSREF_SEARCH_API . (strpos(CROSSREF_SEARCH_API,'?')===false?'?':'&') . 'q=' . $doi
		);
		$response = curl_exec($curlCh);
		if ( $response && curl_getinfo($curlCh, CURLINFO_HTTP_CODE) == CROSSREF_API_RESPONSE_OK ) {
			$response = $jsonManager->decode($response);
			if ( count($response) > 0 ) {
				// inventing a new status "found" for when we find it in the search API (as opposed to deposit API)
				$articleDao->updateSetting($article->getId(), $this->getDepositStatusSettingName(), 'found', 'string');
				$this->markRegistered($request, $article);
				return true;
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
	 * Parse HTTP headers into an associative array
	 * Taken from: http://www.php.net/manual/en/function.http-parse-headers.php#112917
	 * @param $raw_headers
	 * @return array
	 */
	function _http_parse_headers ($raw_headers) {
		$headers = array(); // $headers = [];

		foreach (explode("\n", $raw_headers) as $i => $h) {
			$h = explode(':', $h, 2);

			if (isset($h[1])) {
				if(!isset($headers[$h[0]])) {
					$headers[$h[0]] = trim($h[1]);
				} else if(is_array($headers[$h[0]])) {
					$tmp = array_merge($headers[$h[0]],array(trim($h[1])));
					$headers[$h[0]] = $tmp;
				} else {
					$tmp = array_merge(array($headers[$h[0]]),array(trim($h[1])));
					$headers[$h[0]] = $tmp;
				}
			}
		}

		return $headers;
	}

}

?>
