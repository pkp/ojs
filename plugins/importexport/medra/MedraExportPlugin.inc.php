<?php

/**
 * @file plugins/importexport/medra/MedraExportPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MedraExportPlugin
 * @ingroup plugins_importexport_medra
 *
 * @brief mEDRA Onix for DOI (O4DOI) export/registration plugin
 */


import('classes.plugins.ImportExportPlugin');

// O4DOI schemas.
define('O4DOI_ISSUE_AS_WORK', 0x01);
define('O4DOI_ISSUE_AS_MANIFESTATION', 0x02);
define('O4DOI_ARTICLE_AS_WORK', 0x03);
define('O4DOI_ARTICLE_AS_MANIFESTATION', 0x04);

// Export types.
define('MEDRA_EXPORT_ISSUES', 0x01);
define('MEDRA_EXPORT_ARTICLES', 0x02);
define('MEDRA_EXPORT_GALLEYS', 0x03);

// Current registration state.
define('MEDRA_OJBECT_NEEDS_UPDATE', 0x01);
define('MEDRA_OBJECT_REGISTERED', 0x02);

class MedraExportPlugin extends ImportExportPlugin {

	//
	// Properties
	//
	/** @var O4DOIObjectCache */
	var $_cache;

	function &_getCache() {
		if (!is_a($this->_cache, 'O4DOIObjectCache')) {
			// Instantiate the cache.
			$this->import('classes.O4DOIObjectCache');
			$this->_cache = new O4DOIObjectCache();
		}
		return $this->_cache;
	}

	//
	// Constructor
	//
	function MedraExportPlugin() {
		parent::ImportExportPlugin();
	}


	//
	// Implement template methods from PKPPlugin
	//
	/**
	 * @see PKPPlugin::register()
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
	 * @see PKPPlugin::getInstallSitePluginSettingsFile()
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}


	//
	// Implement template methods from ImportExportPlugin
	//
	/**
	 * @see ImportExportPlugin::getName()
	 */
	function getName() {
		return 'MedraExportPlugin';
	}

	/**
	 * @see ImportExportPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.medra.displayName');
	}

	/**
	 * @see ImportExportPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.medra.description');
	}

	/**
	 * @see ImportExportPlugin::getManagementVerbs()
	 */
	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		$verbs[] = array('settings', __('plugins.importexport.medra.settings'));
		return $verbs;
	}

	/**
	 * @see ImportExportPlugin::display()
	 *
	 * @param $request Request
	 */
	function display(&$args, &$request) {
		parent::display($args);

		// Retrieve journal from the request context.
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);

		$result = true;
		switch (array_shift($args)) {
			case 'all':
				$this->_displayAllUnregisteredObjects($journal);
				break;

			case 'issues':
				$this->_displayIssueList($journal);
				break;

			case 'articles':
				$this->_displayArticleList($journal);
				break;

			case 'galleys':
				$this->_displayGalleyList($journal);
				break;

			case 'exportIssues':
				$result = $this->_exportObjects($request, MEDRA_EXPORT_ISSUES, $request->getUserVar('issueId'), $journal);
				break;

			case 'exportIssue':
				$result = $this->_exportObjects($request, MEDRA_EXPORT_ISSUES, array_shift($args), $journal);
				break;

			case 'exportArticles':
				$result = $this->_exportObjects($request, MEDRA_EXPORT_ARTICLES, $request->getUserVar('articleId'), $journal);
				break;

			case 'exportArticle':
				$result = $this->_exportObjects($request, MEDRA_EXPORT_ARTICLES, array_shift($args),$journal);
				break;

			case 'exportGalleys':
				$result = $this->_exportObjects($request, MEDRA_EXPORT_GALLEYS, $request->getUserVar('galleyId'), $journal);
				break;

			case 'exportGalley':
				$result = $this->_exportObjects($request, MEDRA_EXPORT_GALLEYS, array_shift($args), $journal);
				break;

			case 'exportAll':
				// NB: We assume that the files directory is appropriately secured
				// so that it is not open to symlink attacks. That's reasonable because
				// we're creating lots of files there with well-defined names anyway.
				$exportPath = Config::getVar('files', 'files_dir') . '/medra/';
				if (!file_exists($exportPath)) {
					FileManager::mkdir($exportPath);
				}
				$exportPath .= date('Ymd-Hi-');
				$issueIds = $request->getUserVar('issueId');
				if (!empty($issueIds)) {
					$result = $this->_exportObjects($request, MEDRA_EXPORT_ISSUES, $issueIds, $journal, $exportPath.'issues.xml');
				}
				$articleIds = $request->getUserVar('articleId');
				if ($result === true && !empty($issueIds)) {
					$result = $this->_exportObjects($request, MEDRA_EXPORT_ARTICLES, $articleIds, $journal, $exportPath.'articles.xml');
				}
				$galleyIds = $request->getUserVar('galleyId');
				if ($result === true && !empty($galleyIds)) {
					$result = $this->_exportObjects($request, MEDRA_EXPORT_GALLEYS, $galleyIds, $journal, $exportPath.'galleys.xml');
				}
				if ($result === true) {
					$this->_sendNotification(
						'plugins.importexport.medra.notification.exportSuccessful',
						NOTIFICATION_TYPE_SUCCESS,
						$exportPath . '{issues|articles|galleys}.xml'
					);
					$path = array('plugin', $this->getName(), 'all');
					$request->redirect(null, null, null, $path);
				}
				break;

			default:
				$this->_displayPluginHomePage($journal);
		}

		if ($result !== true) {
			if (is_array($result) && !empty($result)) {
				foreach($result as $error) {
					assert(is_array($error) && count($error) >= 1);
					$this->_sendNotification(
						$error[0],
						NOTIFICATION_TYPE_ERROR,
						(isset($error[1]) ? $error[1] : null)
					);
				}
			}
			$path = array('plugin', $this->getName());
			$request->redirect(null, null, null, $path);
		}
	}

	/**
	 * @see ImportExportPlugin::executeCLI()
	 */
	function executeCLI($scriptName, &$args) {
		$result = array();

		// Command.
		$command = array_shift($args);
		if ($command != 'export') {
			$result = false;
		}

		// Output file.
		if (is_array($result)) {
			$xmlFile = array_shift($args);
			if (empty($xmlFile)) {
				$result = false;
			}
		}

		// Journal.
		if (is_array($result)) {
			$journalPath = array_shift($args);
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journal =& $journalDao->getJournalByPath($journalPath);
			if (!$journal) {
				if ($journalPath != '') {
					$result[] = array('plugins.importexport.medra.export.error.unknownJournal', $journalPath);
				} elseif(empty($result)) {
					$result = false;
				}
			}
		}

		// Exported objects.
		if (is_array($result) && empty($result)) {
			// Retrieve the request.
			$request =& Application::getRequest();

			// Add locale files.
			AppLocale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));

			$objectType = array_shift($args);
			switch ($objectType) {
				case 'issues':
					$result = $this->_exportObjects($request, MEDRA_EXPORT_ISSUES, $args, $journal, $xmlFile);
					break;

				case 'articles':
					$result = $this->_exportObjects($request, MEDRA_EXPORT_ARTICLES, $args, $journal, $xmlFile);
					break;

				case 'galleys':
					$result = $this->_exportObjects($request, MEDRA_EXPORT_GALLEYS, $args, $journal, $xmlFile);
					break;

				default:
					if (!is_null($objectType)) {
						$result[] = array('plugins.importexport.medra.export.error.unknownObjectType', $objectType);
					}
			}
		}

		if ($result !== true) {
			$this->_usage($scriptName, $result);
		}
	}

	/**
	 * @see ImportExportPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$request) {
		parent::manage($verb, $args, $message, $request);

		switch ($verb) {
			case 'settings':
				$router =& $request->getRouter();
				$journal =& $router->getContext($request);

				$this->import('classes.form.MedraSettingsForm');
				$form = new MedraSettingsForm($this, $journal->getId());

				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$request->redirect(null, 'manager', 'importexport', array('plugin', $this->getName()));
					} else {
						$this->setBreadCrumbs(array(), false);
						$form->display();
					}
				} else {
					$this->setBreadCrumbs(array(), true);
					$form->initData();
					$form->display();
				}
				return true;

			default:
				// Unknown management verb.
				assert(false);
		}
		return false;
	}

	//
	// Private helper methods
	//
	/**
	 * Display the plug-in home page.
	 *
	 * @param $journal Journal
	 */
	function _displayPluginHomePage(&$journal) {
		$this->setBreadcrumbs();

		// Prepare and display the index page template.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('journal', $journal);
		$templateMgr->display($this->getTemplatePath() . 'index.tpl');
	}

	/**
	 * Display a list of issues for export.
	 *
	 * @param $journal Journal
	 */
	function _displayIssueList(&$journal) {
		$this->setBreadcrumbs(array(), true);

		// Retrieve all published issues.
		AppLocale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR));
		$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issues =& $issueDao->getPublishedIssues($journal->getId(), Handler::getRangeInfo('issues'));

		// Prepare and display the issue template.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('issues', $issues);
		$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
	}

	/**
	 * Display a list of articles for export.
	 *
	 * @param $journal Journal
	 */
	function _displayArticleList(&$journal) {
		$this->setBreadcrumbs(array(), true);

		// Retrieve all published articles.
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		$articleIds = $publishedArticleDao->getPublishedArticleIdsByJournal($journal->getId());

		// Paginate articles.
		$rangeInfo = Handler::getRangeInfo('articles');
		if ($rangeInfo->isValid()) {
			$articleIds = array_slice($articleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
		}

		// Instantiate article iterator.
		import('lib.pkp.classes.core.VirtualArrayIterator');
		$totalArticles = count($articleIds);
		$iterator = new VirtualArrayIterator(ArticleSearch::formatResults($articleIds), $totalArticles, $rangeInfo->getPage(), $rangeInfo->getCount());

		// Prepare and display the article template.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('articles', $iterator);
		$templateMgr->display($this->getTemplatePath() . 'articles.tpl');
	}

	/**
	 * Display a list of galleys for export.
	 *
	 * @param $journal Journal
	 */
	function _displayGalleyList(&$journal) {
		$this->setBreadcrumbs(array(), true);

		// Retrieve all published articles.
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		$articles = $publishedArticleDao->getPublishedArticlesByJournalId($journal->getId());

		// Retrieve galley data.
		$galleyData = array();
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
		$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$languageDao =& DAORegistry::getDAO('LanguageDAO'); /* @var $languageDao LanguageDAO */
		$nullVar = null;
		$cache =& $this->_getCache();
		while ($article =& $articles->next()) {
			$cache->add($article, $nullVar);

			// Retrieve issue.
			$issueId = $article->getIssueId();
			if (!$cache->isCached('issues', $issueId)) {
				$issue =& $issueDao->getIssueById($issueId, $journal->getId(), true);
				assert(is_a($issue, 'Issue'));
				$cache->add($issue, $nullVar);
				unset($issue);
			}

			// Retrieve galleys.
			$galleys =& $galleyDao->getGalleysByArticle($article->getId());
			foreach ($galleys as $galley) {
				$cache->add($galley, $article);

				// Retrieve galley language.
				$language = $languageDao->getLanguageByCode(substr($galley->getLocale(), 0, 2));

				$galleyData[] = array(
					'language' => &$language,
					'galley' => &$galley,
					'article' => &$article,
					'issue' => $cache->get('issues', $issueId)
				);
				unset($galley, $language);
			}
			unset($article);
		}

		// Paginate galleys.
		$totalGalleys = count($galleyData);
		$rangeInfo = Handler::getRangeInfo('galleys');
		if ($rangeInfo->isValid()) {
			$galleyData = array_slice($galleyData, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
		}

		// Instantiate galley iterator.
		import('lib.pkp.classes.core.VirtualArrayIterator');
		$iterator = new VirtualArrayIterator($galleyData, $totalGalleys, $rangeInfo->getPage(), $rangeInfo->getCount());

		// Prepare and display the galley template.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('galleys', $iterator);
		$templateMgr->display($this->getTemplatePath() . 'galleys.tpl');
	}

	/**
	 * Display a list of all yet unregistered objects.
	 *
	 * @param $journal Journal
	 */
	function _displayAllUnregisteredObjects(&$journal) {
		$this->setBreadcrumbs(array(), true);
		AppLocale::requireComponents(array(LOCALE_COMPONENT_PKP_SUBMISSION));

		// Prepare and display the template.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('issues', $this->_getUnregisteredIssues($journal));
		$templateMgr->assign_by_ref('articles', $this->_getUnregisteredArticles($journal));
		$templateMgr->assign_by_ref('galleys', $this->_getUnregisteredGalleys($journal));
		$templateMgr->display($this->getTemplatePath() . 'all.tpl');
	}

	/**
	 * Retrieve all unregistered issues.
	 * @param $journal Journal
	 * @return array
	 */
	function &_getUnregisteredIssues(&$journal) {
		// Retrieve all issues that have not yet been registered.
		$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issues = $issueDao->getIssuesBySetting('medra::status', null, $journal->getId());

		// Retrieve issues for articles and arrange articles.
		$nullVar = null;
		$cache =& $this->_getCache();
		foreach ($issues as $issue) {
			$cache->add($issue, $nullVar);
			unset($issue);
		}
		return $issues;
	}

	/**
	 * Retrieve all unregistered articles and their corresponding issues.
	 * @param $journal Journal
	 * @return array
	 */
	function &_getUnregisteredArticles(&$journal) {
		// Retrieve all published articles that have not yet been registered.
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		$articles = $publishedArticleDao->getPublishedArticlesBySetting('medra::status', null, $journal->getId());

		// Retrieve issues for articles and arrange articles.
		$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$articleData = array();
		$nullVar = null;
		$cache =& $this->_getCache();
		foreach ($articles as $article) {
			$cache->add($article, $nullVar);
			$issueId = $article->getIssueId();
			if (!$cache->isCached('issues', $issueId)) {
				$issue =& $issueDao->getIssueById($issueId, $journal->getId(), true);
				assert(is_a($issue, 'Issue'));
				$cache->add($issue, $nullVar);
				unset($issue);
			}
			$articleData[] = array(
				'article' => &$article,
				'issue' => $cache->get('issues', $issueId)
			);
			unset($article);
		}
		return $articleData;
	}

	/**
	 * Retrieve all unregistered galleys and their corresponding issues and articles.
	 * @param $journal Journal
	 * @return array
	 */
	function &_getUnregisteredGalleys(&$journal) {
		// Retrieve all galleys that have not yet been registered.
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
		$galleys = $galleyDao->getGalleysBySetting('medra::status', null, null, $journal->getId());

		// Retrieve issues and articles for galleys and arrange galleys.
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$languageDao =& DAORegistry::getDAO('LanguageDAO'); /* @var $languageDao LanguageDAO */
		$galleyData = array();
		$nullVar = null;
		$cache =& $this->_getCache();
		foreach ($galleys as $galley) {
			// Retrieve article if not yet cached.
			$articleId = $galley->getArticleId();
			$article = null;
			if (!$cache->isCached('articles', $articleId)) {
				$article =& $publishedArticleDao->getPublishedArticleByArticleId($articleId, $journal->getId(), true);
				assert(is_a($article, 'PublishedArticle'));
				$cache->add($article, $nullVar);
			}
			if (!$article) $article =& $cache->get('articles', $articleId);

			// Retrieve issue if not yet cached.
			$issueId = $article->getIssueId();
			if (!$cache->isCached('issues', $issueId)) {
				$issue =& $issueDao->getIssueById($issueId, $journal->getId(), true);
				assert(is_a($issue, 'Issue'));
				$cache->add($issue, $nullVar);
				unset($issue);
			}

			// Retrieve galley language.
			$language = $languageDao->getLanguageByCode(substr($galley->getLocale(), 0, 2));

			$cache->add($galley, $article);

			$galleyData[] = array(
				'language' => &$language,
				'galley' => &$galley,
				'article' => &$article,
				'issue' => $cache->get('issues', $issueId)
			);

			unset($article, $galley, $language);
		}
		return $galleyData;
	}

	/**
	 * Export issues or articles to O4DOI.
	 *
	 * @param $request Request
	 * @param $exportType integer One of the MEDRA_EXPORT_* constants.
	 * @param $objectIs array The ids of issues or articles to export.
	 * @param $journal Journal
	 * @param $outputFile string The file to export to.
	 *
	 * @return boolean|array True for success, false for error condition
	 *  or an array of error messages if the cause of the error is known.
	 */
	function _exportObjects(&$request, $exportType, $objectIds, &$journal, $outputFile = null) {
		$errors = array();

		// Retrieve the objects.
		$objects =& $this->_getObjectsFromIds($exportType, $objectIds, $journal->getId(), $errors);
		if ($objects === false) return $errors;

		// Identify the O4DOI schema to export and generate the root node.
		$exportIssuesAs = $this->getSetting($journal->getId(), 'exportIssuesAs');
		$schema = $this->_identifyO4DOISchema($objects[0], $journal, $exportIssuesAs);
		assert(!is_null($schema));

		// Get the journal's publication country which is a plug-in setting.
		$publicationCountry = $this->getSetting($journal->getId(), 'publicationCountry');
		assert(!empty($publicationCountry));

		// Create the XML DOM and document.
		$this->import('classes.O4DOIExportDom');
		$dom = new O4DOIExportDom($request, $schema, $journal, $this->_getCache(), $exportIssuesAs);
		$doc =& $dom->generate($objects, $publicationCountry);
		if ($doc === false) return $dom->getErrors();

		// Stream the results to the browser...
		if (is_null($outputFile)) {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"o4doi.xml\"");
			XMLCustomWriter::printXML($doc);

		// ...or save them as a file.
		} else {
			$outputDir = dirname($outputFile);
			if (empty($outputDir)) $outputDir = getcwd();
			if (!is_writable($outputDir) || ($fileHandle = fopen($outputFile, 'w')) === false) {
				$errors[] = array('plugins.importexport.medra.export.error.outputFileNotWritable', $outputFile);
				return $errors;
			}
			fwrite($fileHandle, XMLCustomWriter::getXML($doc));
			fclose($fileHandle);
		}
		return true;
	}

	/**
	 * Retrieve the objects corresponding to the given ids.
	 * @param $exportType integer One of the MEDRA_EXPORT_* constants.
	 * @param $objectIds integer|array
	 * @param $journalId integer
	 * @param $errors array
	 * @return array|boolean
	 */
	function &_getObjectsFromIds($exportType, $objectIds, $journalId, &$errors) {
		$falseVar = false;
		if (empty($objectIds)) return $falseVar;
		if (!is_array($objectIds)) $objectIds = array($objectIds);

		// Find the correct DAO.
		$daoNames = array(
			MEDRA_EXPORT_ISSUES => array('IssueDAO', 'getIssueById'),
			MEDRA_EXPORT_ARTICLES => array('PublishedArticleDAO', 'getPublishedArticleByArticleId'),
			MEDRA_EXPORT_GALLEYS => array('ArticleGalleyDAO', 'getGalley')
		);
		assert(isset($daoNames[$exportType]));
		list($daoName, $daoMethod) = $daoNames[$exportType];

		$dao =& DAORegistry::getDAO($daoName);
		$daoMethod = array($dao, $daoMethod);

		$objects = array();
		foreach ($objectIds as $objectId) {
			// Retrieve the objects from the DAO.
			$daoMethodArgs = array($objectId);
			if ($exportType != MEDRA_EXPORT_GALLEYS) {
				$daoMethodArgs[] = $journalId;
			}
			$foundObjects =& call_user_func_array($daoMethod, $daoMethodArgs);
			if (!$foundObjects || empty($foundObjects)) {
				switch($exportType) {
					case MEDRA_EXPORT_ISSUES:
						$errors[] = array('plugins.importexport.medra.export.error.issueNotFound', $objectId);
						break;

					case MEDRA_EXPORT_ARTICLES:
						$errors[] = array('plugins.importexport.medra.export.error.articleNotFound', $objectId);
						break;

					case MEDRA_EXPORT_GALLEYS:
						$errors[] = array('plugins.importexport.medra.export.error.galleyNotFound', $objectId);
						break;

					default:
						assert(false);
				}
				return $falseVar;
			}

			// Add the objects to our result array.
			if (!is_array($foundObjects)) $foundObjects = array($foundObjects);
			foreach ($foundObjects as $foundObject) {
				// Only export objects that have a DOI assigned.
				// NB: This may generate DOIs for the selected
				// objects on the fly.
				if (!is_null($foundObject->getDoi())) $objects[] =& $foundObject;
				unset($foundObject);
			}
			unset($foundObjects);
		}

		return $objects;
	}

	/**
	 * Determine the O4DOI export schema.
	 *
	 * @param $object object
	 * @param $journal Journal
	 * @param $exportIssuesAs Whether issues are exported as work
	 *  or as manifestation. One of the O4DOI_* schema constants.
	 *
	 * @return integer One of the O4DOI_* schema constants.
	 */
	function _identifyO4DOISchema(&$object, &$journal, $exportIssuesAs) {
		if (is_a($object, 'Issue')) {
			assert ($exportIssuesAs === O4DOI_ISSUE_AS_WORK || $exportIssuesAs === O4DOI_ISSUE_AS_MANIFESTATION);
			return $exportIssuesAs;
		}

		if (is_a($object, 'Article')) {
			return O4DOI_ARTICLE_AS_WORK;
		}

		if (is_a($object, 'ArticleGalley')) {
			return O4DOI_ARTICLE_AS_MANIFESTATION;
		}

		return null;
	}

	/**
	 * Display execution errors (if any) and
	 * command-line usage information.
	 *
	 * @param $scriptName string
	 * @param $errors array An optional list of translated error messages.
	 */
	function _usage($scriptName, $errors = null) {
		if (is_array($errors) && !empty($errors)) {
			echo __('plugins.importexport.medra.cliError') . "\n";
			foreach ($errors as $error) {
				assert(is_array($error) && count($error) >=1);
				if (isset($error[1])) {
					$errorMessage = __($error[0], array('param' => $error[1]));
				} else {
					$errorMessage = __($error[0]);
				}
				echo "*** $errorMessage\n";
			}
			echo "\n\n";
		}
		echo __(
			'plugins.importexport.medra.cliUsage',
			array(
				'scriptName' => $scriptName,
				'pluginName' => $this->getName()
			)
		) . "\n";
	}

	/**
	 * Add a notification.
	 * @param $message string An i18n key.
	 * @param $param string An additional parameter for the message.
	 * @param $notificationType integer One of the NOTIFICATION_TYPE_* constants.
	 */
	function _sendNotification($message, $notificationType, $param = null) {
		static $notificationManager = null;

		if (is_null($notificationManager)) {
			import('lib.pkp.classes.notification.NotificationManager');
			$notificationManager = new NotificationManager();
		}

		$notificationManager->createTrivialNotification(
			'notification.notification',
			$message,
			$notificationType,
			$param
		);
	}
}

?>
