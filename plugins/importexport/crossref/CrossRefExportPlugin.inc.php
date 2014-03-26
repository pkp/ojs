<?php

/**
 * @file plugins/importexport/crossref/CrossRefExportPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
define('CROSSREF_API_RESPONSE_OK', 200);
//define('CROSSREF_API_URL', 'http://doi.crossref.org/servlet/deposit');
define('CROSSREF_API_URL_DEV', 'http://test.crossref.org/servlet/deposit');

// The name of the setting used to save the registered DOI.
define('CROSSREF_DEPOSIT_STATUS', 'depositStatus');

// Test DOI prefix
define('CROSSREF_API_TESTPREFIX', '10.1234');

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
		$templateMgr->display($this->getTemplatePath() . 'all.tpl');
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
						$this->markRegistered($request, $article, CROSSREF_API_TESTPREFIX);
					}
				}
			} else {
				if ($object->getPubId('doi')) {
					$this->markRegistered($request, $object, CROSSREF_API_TESTPREFIX);
				}
			}
		}
	}

	/**
	 * @see DOIExportPlugin::registerDoi()
	 */
	function registerDoi(&$request, &$journal, &$objects, $file) {
		// Prepare HTTP session.
		$curlCh = curl_init ();
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);

		$username = $this->getSetting($journal->getId(), 'username');
		$password = $this->getSetting($journal->getId(), 'password');

		// Transmit XML data.
		assert(is_readable($file));

		curl_setopt($curlCh, CURLOPT_URL, CROSSREF_API_URL_DEV . '?operation=doMDUpload&login_id='.$username.'&login_passwd='.$password);
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, array('fname' => '@/'.realpath($file)));

		$result = true;
		$response = curl_exec($curlCh);
		if ($response === false) {
			$result = array(array('plugins.importexport.common.register.error.mdsError', 'No response from server.'));
		} else {
			$status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
			if ($status != CROSSREF_API_RESPONSE_OK) {
				$result = array(array('plugins.importexport.common.register.error.mdsError', "$status - $response"));
			}
		}

		curl_close($curlCh);

		return $result;
	}

	/**
	 * TODO: this function still needs work
	 */
	function updateDepositStatus(&$request, &$journal, $article) {
		$articleDao =& DAORegistry::getDAO('ArticleDAO');  /* @var $articleDao ArticleDAO */

		// Prepare HTTP session.
		$curlCh = curl_init ();
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);

		$username = $this->getSetting($journal->getId(), 'username');
		$password = $this->getSetting($journal->getId(), 'password');

		// FIXME: use Karl's new API

		curl_setopt($curlCh, CURLOPT_URL, CROSSREF_API_URL_DEV . '?operation=doMDUpload&login_id='.$username.'&login_passwd='.$password);

		$result = true;
		$response = curl_exec($curlCh);
		if ($response === false) {
			$result = array(array('plugins.importexport.common.register.error.mdsError', 'No response from server.'));
		} else {
			$status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
			if ($status != CROSSREF_API_RESPONSE_OK) {
				$result = array(array('plugins.importexport.common.register.error.mdsError', "$status - $response"));
			}
		}

		curl_close($curlCh);

		$articleDao->updateSetting($article->getId(), $this->getDepositStatusSettingName(), $result, 'string');
		// if successful, mark as registered
		if (false) {
			$this->markRegistered($request, $article, CROSSREFBB_API_TESTPREFIX);
		}
	}

	/**
	 * @see AcronPlugin::parseCronTab()
	 */
	function callbackParseCronTab($hookName, $args) {
		$taskFilesPath =& $args[0];
		$taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';
		error_log($this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml');

		return false;
	}

	function getDepositStatusSettingName() {
		return $this->getPluginId() . '::' . CROSSREF_DEPOSIT_STATUS;
	}
}

?>
