<?php

/**
 * @file plugins/importexport/.../classes/DoiExportPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DoiExportPlugin
 * @ingroup plugins_importexport_..._classes
 *
 * @brief Base class for DOI export/registration plugins.
 */


import('classes.plugins.ImportExportPlugin');

// Export types.
define('DOI_EXPORT_ISSUES', 0x01);
define('DOI_EXPORT_ARTICLES', 0x02);
define('DOI_EXPORT_GALLEYS', 0x03);

// Current registration state.
define('DOI_OBJECT_NEEDS_UPDATE', 0x01);
define('DOI_OBJECT_REGISTERED', 0x02);

// Export file types.
define('DOI_EXPORT_FILE_XML', 0x01);
define('DOI_EXPORT_FILE_TAR', 0x02);

// Configuration errors.
define('DOI_EXPORT_CONFIGERROR_DOIPREFIX', 0x01);
define('DOI_EXPORT_CONFIGERROR_SETTINGS', 0x02);

class DoiExportPlugin extends ImportExportPlugin {

	//
	// Protected Properties
	//
	/** @var PubObjectCache */
	var $_cache;

	function &getCache() {
		if (!is_a($this->_cache, 'PubObjectCache')) {
			// Instantiate the cache.
			$this->import('classes.PubObjectCache');
			$this->_cache = new PubObjectCache();
		}
		return $this->_cache;
	}


	//
	// Private Properties
	//
	/** @var boolean */
	var $_checkedForTar = false;


	//
	// Constructor
	//
	function DoiExportPlugin() {
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

	/**
	 * @see PKPPlugin::getLocaleFilename($locale)
	 */
	function getLocaleFilename($locale) {
		$localeFilenames = parent::getLocaleFilename($locale);

		// Add shared locale keys.
		$localeFilenames[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . 'common.xml';

		return $localeFilenames;
	}


	//
	// Implement template methods from ImportExportPlugin
	//
	/**
	 * @see ImportExportPlugin::getManagementVerbs()
	 */
	function getManagementVerbs() {
		$verbs = parent::getManagementVerbs();
		$verbs[] = array('settings', __('plugins.importexport.common.settings'));
		return $verbs;
	}

	/**
	 * @see ImportExportPlugin::display()
	 */
	function display(&$args, &$request) {
		parent::display($args, $request);

		// Retrieve journal from the request context.
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);

		$result = true;
		$op = array_shift($args);
		switch ($op) {
			case 'all':
				$this->displayAllUnregisteredObjects($journal);
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
				$result = $this->exportObjects($request, array(DOI_EXPORT_ISSUES => $request->getUserVar('issueId')), $journal);
				break;

			case 'exportIssue':
				$result = $this->exportObjects($request, array(DOI_EXPORT_ISSUES => array_shift($args)), $journal);
				break;

			case 'exportArticles':
				$result = $this->exportObjects($request, array(DOI_EXPORT_ARTICLES => $request->getUserVar('articleId')), $journal);
				break;

			case 'exportArticle':
				$result = $this->exportObjects($request, array(DOI_EXPORT_ARTICLES => array_shift($args)),$journal);
				break;

			case 'exportGalleys':
				$result = $this->exportObjects($request, array(DOI_EXPORT_GALLEYS => $request->getUserVar('galleyId')), $journal);
				break;

			case 'exportGalley':
				$result = $this->exportObjects($request, array(DOI_EXPORT_GALLEYS => array_shift($args)), $journal);
				break;

			case 'exportAll':
				$objectIdSpec = $this->getAllObjectIds();
				$exportSpec = array();
				foreach ($objectIdSpec as $exportType => $idVar) {
					$exportSpec[$exportType] = $request->getUserVar($idVar);
				}
				$this->exportObjects($request, $exportSpec, $journal);
				break;

			default:
				$result = $this->handlePluginSpecificOps($op, $args, $request, $journal, $result);
		}

		if ($result !== true) {
			if (is_array($result) && !empty($result)) {
				foreach($result as $error) {
					assert(is_array($error) && count($error) >= 1);
					$this->_sendNotification(
						$request,
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
					$result[] = array('plugins.importexport.common.export.error.unknownJournal', $journalPath);
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
					$result = $this->exportObjects($request, array(DOI_EXPORT_ISSUES => $args), $journal, $xmlFile);
					break;

				case 'articles':
					$result = $this->exportObjects($request, array(DOI_EXPORT_ARTICLES => $args), $journal, $xmlFile);
					break;

				case 'galleys':
					$result = $this->exportObjects($request, array(DOI_EXPORT_GALLEYS => $args), $journal, $xmlFile);
					break;

				default:
					$result = $this->handlePluginSpecificCliOps($request, $objectType, $args, $journal, $xmlFile, $result);
			}
		}

		if ($result !== true) {
			$this->_usage($scriptName, $result);
		}
	}

	/**
	 * @see ImportExportPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$request) {
		parent::manage($verb, $args, $message, $messageParams, $request);

		switch ($verb) {
			case 'settings':
				$router =& $request->getRouter();
				$journal =& $router->getContext($request);

				$form =& $this->_instantiateSettingsForm($journal);
				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						$request->redirect(null, 'manager', 'importexport', array('plugin', $this->getName()));
					} else {
						$this->setBreadCrumbs(array(), true);
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
	// Protected template methods
	//
	/**
	 * Return the directory below the files dir where
	 * export files should be placed.
	 * @return string
	 */
	function getPluginId() {
		assert(false);
	}

	/**
	 * Return the class name of the plug-in's settings form.
	 * @return string
	 */
	function getSettingsFormClassName() {
		assert(false);
	}

	/**
	 * Return all object ids from the request.
	 * @return array An array with export types and
	 *  the corresponding request parameters.
	 */
	function getAllObjectIds() {
		return array(
			DOI_EXPORT_ISSUES => 'issueId',
			DOI_EXPORT_ARTICLES => 'articleId',
			DOI_EXPORT_GALLEYS => 'galleyId'
		);
	}

	/**
	 * Let the subclass handle web operations specific
	 * to the plug-in implementation.
	 * @param $op string
	 * @param $args array
	 * @param $request Request
	 * @param $journal Journal
	 * @param $previousResult boolean|array
	 * @return boolean|array
	 */
	function handlePluginSpecificOps($op, &$args, &$request, &$journal, $previousResult) {
		// By default display the home page.
		$this->_displayPluginHomePage($journal);
		return $previousResult;
	}

	/**
	 * Let the subclass handle CLI operations specific
	 * to the plug-in implementation.
	 * @param $request Request
	 * @param $objectType string
	 * @param $objectIds array
	 * @param $journal Journal
	 * @param $xmlFile string
	 * @param $previousResult boolean|array
	 * @return boolean|array
	 */
	function handlePluginSpecificCliOps(&$request, $objectType, $objectIds, &$journal, $xmlFile, $previousResult) {
		// By default return an error for unhandled object types.
		if (!is_null($objectType)) {
			$previousResult[] = array('plugins.importexport.common.export.error.unknownObjectType', $objectType);
		}
		return $previousResult;
	}

	/**
	 * Display a list of all yet unregistered objects.
	 *
	 * @param $journal Journal
	 */
	function displayAllUnregisteredObjects(&$journal) {
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
	 * Retrieve all published articles.
	 * @param $journal Journal
	 * @return DAOResultFactory
	 */
	function getAllPublishedArticles(&$journal) {
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		$articles = $publishedArticleDao->getPublishedArticlesByJournalId($journal->getId());
		return $articles;
	}

	/**
	 * Identify published article and issue of the given article file.
	 * @param $articleFile ArticleFile
	 * @param $journal Journal
	 * @return array
	 */
	function &prepareArticleFileData(&$articleFile, &$journal) {
		// Prepare and return article data for the article file.
		$articleData =& $this->_prepareArticleDataByArticleId($articleFile->getArticleId(), $journal);

		// Add the article file to the cache.
		$cache =& $this->getCache();
		$cache->add($articleFile, $articleData['article']);

		return $articleData;
	}

	/**
	 * Export publishing objects.
	 *
	 * @param $request Request
	 * @param $exportSpec array An array with DOI_EXPORT_* constants as keys and
	 *  object ids as values.
	 * @param $journal Journal
	 * @param $outputFile string The final file to export to (if not given then a
	 *  standard file name convention will be used).
	 *
	 * @return boolean|array True for success, false for error condition
	 *  or an array of error messages if the cause of the error is known.
	 */
	function exportObjects(&$request, $exportSpec, &$journal, $outputFile = null) {
		// Initialize local variables.
		$errors = array();

		// If we have more than one object type, then we'll need the
		// tar tool to package the resulting export files.
		if (count($exportSpec) > 1) {
			if (is_array($errors = $this->_checkForTar())) return $errors;
		}

		// Get the target directory.
		$result = $this->_getExportPath();
		if (is_array($result)) return $result;
		$exportPath = $result;

		// Run through the export types and generate the corresponding
		// export files.
		$exportFiles = array();
		foreach($exportSpec as $exportType => $objectIds) {
			// Normalize the object ids into an array.
			if (is_scalar($objectIds)) $objectIds = array($objectIds);

			// Get the target file spec.
			list($targetFileName, $targetFileType) = $this->getTargetFileSpec($exportPath, $exportType, $objectIds);

			// The target file must be in the export directory.
			// This is important as we assume that the export directory
			// is not exposed to symlink attacks.
			if (dirname($targetFileName) . '/' !== $exportPath) {
				$this->cleanTmpfiles($exportPath, $exportFiles);
				return false;
			}

			// Check whether we need the tar tool for this export.
			if (!$this->_checkedForTar && $targetFileType == DOI_EXPORT_FILE_TAR) {
				if (is_array($errors = $this->_checkForTar())) {
					$this->cleanTmpfiles($exportPath, $exportFiles);
					return $errors;
				}
			}

			// Retrieve the objects.
			$objects =& $this->_getObjectsFromIds($exportType, $objectIds, $journal->getId(), $errors);
			if ($objects === false) {
				$this->cleanTmpfiles($exportPath, $exportFiles);
				return $errors;
			}

			// Export the objects to a file.
			$result = $this->generateExportFile($request, $exportType, $objects, $targetFileName, $journal);
			if (is_array($result)) {
				$this->cleanTmpfiles($exportPath, $exportFiles);
				return $result;
			}

			// Remember the target file name and type.
			$exportFiles[] = $targetFileName;
		}

		// If we have more than one export file we package the files
		// up as a single tar before going on.
		assert(count($exportFiles) >= 1);
		if (count($exportFiles) > 1) {
			$finalExportFileName = $exportPath . $this->getPluginId() . '-export.tar.gz';
			$finalExportFileType = DOI_EXPORT_FILE_TAR;
			$this->tarFiles($exportPath, $finalExportFileName, $exportFiles);
			$exportFiles[] = $finalExportFileName;
		} else {
			$finalExportFileName = $targetFileName;
			$finalExportFileType = $targetFileType;
		}

		// Stream the results to the browser...
		if (is_null($outputFile)) {
			header('Content-Type: application/' . ($finalExportFileType == DOI_EXPORT_FILE_TAR ? 'x-gtar' : 'xml'));
			header('Cache-Control: private');
			header('Content-Disposition: attachment; filename="' . basename($finalExportFileName) . '"');
			readfile($finalExportFileName);

		// ...or save them as a file.
		} else {
			$outputFileExtension = ($finalExportFileType == DOI_EXPORT_FILE_TAR ? '.tar.gz' : '.xml');
			if (substr($outputFile, -strlen($outputFileExtension)) != $outputFileExtension) {
				$outputFile .= $outputFileExtension;
			}
			$outputDir = dirname($outputFile);
			if (empty($outputDir)) $outputDir = getcwd();
			if (!is_writable($outputDir) || (file_exists($outputFile) && !is_writable($outputFile))) {
				$errors[] = array('plugins.importexport.common.export.error.outputFileNotWritable', $outputFile);
				return $errors;
			}
			FileManager::copyFile($finalExportFileName, $outputFile);
		}

		// Remove all temporary files.
		$this->cleanTmpfiles($exportPath, $exportFiles);

		return true;
	}

	/**
	 * Returns file name and file type of the target export
	 * file for the given export type and object ids.
	 * @param $exportPath string
	 * @param $exportType integer One of the DOI_EXPORT_* constants.
	 * @param $objectIds integer|array An single id or an array of object ids to export.
	 * @return array An array containing the target file name as first
	 *  entry and one of the DOI_EXPORT_FILE_* constants as second entry.
	 */
	function getTargetFileSpec($exportPath, $exportType, $objectIds) {
		// Normalize object ids.
		if (is_scalar($objectIds)) $objectIds = array($objectIds);

		// Define the prefix of the exported files.
		$targetFileName = $exportPath . date('Ymd-Hi-') . $this->getObjectName($exportType);

		// Define the target file type and the final target file name.
		if ($this->multipleObjectsPerExportFile()) {
			$targetFileName .= 's.xml';
			$targetFileType = DOI_EXPORT_FILE_XML;
		} else {
			if (count($objectIds) > 1) {
				$targetFileName .= 's.tar.gz';
				$targetFileType = DOI_EXPORT_FILE_TAR;
			} else {
				assert(count($objectIds) == 1);
				$targetFileName .= '-' . $objectIds[0] . '.xml';
				$targetFileType = DOI_EXPORT_FILE_XML;
			}
		}
		return array($targetFileName, $targetFileType);
	}

	/**
	 * Get a string representation of the object
	 * being exported by a given export type.
	 * @param $exportType integer One of the DOI_EXPORT_* constants.
	 * @return string
	 */
	function getObjectName($exportType) {
		$objectNames = array(
			DOI_EXPORT_ISSUES => 'issue',
			DOI_EXPORT_ARTICLES => 'article',
			DOI_EXPORT_GALLEYS => 'galley',
		);
		assert(isset($objectNames[$exportType]));
		return $objectNames[$exportType];
	}

	/**
	 * Whether the plug-in's target export format allows
	 * several objects to be exported in a single XML file.
	 * @return boolean
	 */
	function multipleObjectsPerExportFile() {
		assert(false);
	}

	/**
	 * Generate the export data model.
	 * @param $request Request
	 * @param $exportType integer
	 * @param $objects array
	 * @param $targetFilename string
	 * @param $journal Journal
	 * @return boolean|array Either true if successful or
	 *  an array of export errors.
	 */
	function generateExportFile(&$request, $exportType, &$objects, $targetFilename, &$journal) {
		assert(false);
	}

	/**
	 * Create a tar archive.
	 * @param $targetPath string
	 * @param $targetFile string
	 * @param $sourceFiles array
	 */
	function tarFiles($targetPath, $targetFile, $sourceFiles) {
		assert($this->_checkedForTar);

		// GZip compressed result file.
		$tarCommand = Config::getVar('cli', 'tar') . ' -czf ' . escapeshellarg($targetFile);

		// Do not reveal our internal export path by exporting only relative filenames.
		$tarCommand .= ' -C ' . escapeshellarg($targetPath);

		// Do not reveal our webserver user by forcing root as owner.
		$tarCommand .= ' --owner 0 --group 0 --';

		// Add each file individually so that other files in the directory
		// will not be included.
		foreach($sourceFiles as $sourceFile) {
			assert(dirname($sourceFile) . '/' === $targetPath);
			if (dirname($sourceFile) . '/' !== $targetPath) continue;
			$tarCommand .= ' ' . escapeshellarg(basename($sourceFile));
		}

		// Execute the command.
		exec($tarCommand);
	}

	/**
	 * Remove the given temporary files.
	 * @param $tempdir string
	 * @param $tempfiles array
	 */
	function cleanTmpfiles($tempdir, $tempfiles) {
		foreach ($tempfiles as $tempfile) {
			$tempfilePath = dirname($tempfile) . '/';
			assert($tempdir === $tempfilePath);
			if ($tempdir !== $tempfilePath) continue;
			unlink($tempfile);
		}
	}

	/**
	 * Identify DAO and DAO method to extract objects
	 * for a given export type.
	 * @param $exportType One of the DOI_EXPORT_* constants
	 * @return array A list with the DAO name and DAO method name.
	 */
	function getDaoName($exportType) {
		$daoNames = array(
			DOI_EXPORT_ISSUES => array('IssueDAO', 'getIssueById'),
			DOI_EXPORT_ARTICLES => array('PublishedArticleDAO', 'getPublishedArticleByArticleId'),
			DOI_EXPORT_GALLEYS => array('ArticleGalleyDAO', 'getGalley'),
		);
		assert(isset($daoNames[$exportType]));
		return $daoNames[$exportType];
	}

	/**
	 * Return a translation key for the "object not found" error message
	 * for a given export type.
	 * @param $exportType One of the DOI_EXPORT_* constants
	 * @return string A translation key.
	 */
	function getObjectNotFoundErrorKey($exportType) {
		$errorKeys = array(
			DOI_EXPORT_ISSUES => 'plugins.importexport.common.export.error.issueNotFound',
			DOI_EXPORT_ARTICLES => 'plugins.importexport.common.export.error.articleNotFound',
			DOI_EXPORT_GALLEYS => 'plugins.importexport.common.export.error.galleyNotFound'
		);
		assert(isset($errorKeys[$exportType]));
		return $errorKeys[$exportType];
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

		// Check for configuration errors:
		$configurationErrors = array();

		// 1) missing DOI prefix
		$doiPrefix = $journal->getSetting('doiPrefix');
		if (empty($doiPrefix)) {
			$configurationErrors[] = DOI_EXPORT_CONFIGERROR_DOIPREFIX;
		}

		// 2) missing plug-in setting
		$form =& $this->_instantiateSettingsForm($journal);
		foreach($form->getFormFields() as $fieldName => $fieldType) {
			$setting = $this->getSetting($journal->getId(), $fieldName);
			if (empty($setting)) {
				$configurationErrors[] = DOI_EXPORT_CONFIGERROR_SETTINGS;
				break;
			}
		}

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('configurationErrors', $configurationErrors);

		// Prepare and display the index page template.
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

		// Retrieve article data.
		$articleData = array();
		foreach($articleIds as $articleId) {
			$articleData[] =& $this->_prepareArticleDataByArticleId($articleId, $journal);
		}

		// Instantiate article iterator.
		import('lib.pkp.classes.core.VirtualArrayIterator');
		$totalArticles = count($articleIds);
		$iterator = new VirtualArrayIterator($articleData, $totalArticles, $rangeInfo->getPage(), $rangeInfo->getCount());

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
		$articles = $this->getAllPublishedArticles($journal);

		// Retrieve galley data.
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
		$galleyData = array();
		while ($article =& $articles->next()) {
			// Retrieve galleys for the article.
			$galleys =& $galleyDao->getGalleysByArticle($article->getId());
			foreach ($galleys as $galley) {
				$galleyData[] =& $this->_prepareGalleyData($galley, $journal);
				unset($galley);
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
	 * Retrieve all unregistered issues.
	 * @param $journal Journal
	 * @return array
	 */
	function &_getUnregisteredIssues(&$journal) {
		// Retrieve all issues that have not yet been registered.
		$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issues = $issueDao->getIssuesBySetting($this->getPluginId(). '::status', null, $journal->getId());

		// Cache issues.
		$nullVar = null;
		$cache =& $this->getCache();
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
		$articles = $publishedArticleDao->getPublishedArticlesBySetting($this->getPluginId(). '::status', null, $journal->getId());

		// Retrieve issues for articles.
		$articleData = array();
		foreach ($articles as $article) {
			$articleData[] =& $this->_prepareArticleData($article, $journal);
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
		$galleys = $galleyDao->getGalleysBySetting($this->getPluginId(). '::status', null, null, $journal->getId());

		// Retrieve issues, articles and language for galleys.
		$galleyData = array();
		foreach ($galleys as $galley) {
			$galleyData[] =& $this->_prepareGalleyData($galley, $journal);
			unset($galley);
		}
		return $galleyData;
	}

	/**
	 * Identify published article, issue and language of the given galley.
	 * @param $galley ArticleGalley
	 * @param $journal Journal
	 * @return array
	 */
	function &_prepareGalleyData(&$galley, &$journal) {
		// Retrieve article and issue for the galley.
		$galleyData =& $this->prepareArticleFileData($galley, $journal);

		// Add the galley language.
		$languageDao =& DAORegistry::getDAO('LanguageDAO'); /* @var $languageDao LanguageDAO */
		$galleyData['language'] =& $languageDao->getLanguageByCode(substr($galley->getLocale(), 0, 2));

		// Add the galley itself.
		$galleyData['galley'] =& $galley;

		return $galleyData;
	}

	/**
	 * Identify published article and issue for the given article id.
	 * @param $articleId integer
	 * @param $journal Journal
	 * @return array
	 */
	function &_prepareArticleDataByArticleId($articleId, &$journal) {
		// Get the cache.
		$cache =& $this->getCache();

		// Retrieve article if not yet cached.
		$article = null;
		if (!$cache->isCached('articles', $articleId)) {
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
			$article =& $publishedArticleDao->getPublishedArticleByArticleId($articleId, $journal->getId(), true);
			assert(is_a($article, 'PublishedArticle'));
			$cache->add($article, $nullVar = null);
		}
		if (!$article) $article =& $cache->get('articles', $articleId);

		// Prepare and return article data for the article file.
		return $this->_prepareArticleData($article, $journal);
	}

	/**
	 * Identify the issue of the given article.
	 * @param $article PublishedArticle
	 * @param $journal Journal
	 * @return array
	 */
	function &_prepareArticleData(&$article, &$journal) {
		// Get the cache.
		$cache =& $this->getCache();
		$nullVar = null;

		// Add the article to the cache.
		$cache->add($article, $nullVar);

		// Retrieve issue if not yet cached.
		$issueId = $article->getIssueId();
		if (!$cache->isCached('issues', $issueId)) {
			$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issue =& $issueDao->getIssueById($issueId, $journal->getId(), true);
			assert(is_a($issue, 'Issue'));
			$cache->add($issue, $nullVar);
			unset($issue);
		}

		$articleData = array(
			'article' => &$article,
			'issue' => $cache->get('issues', $issueId)
		);
		return $articleData;
	}

	/**
	 * Test whether the tar binary is available.
	 * @return boolean|array Boolean true if available otherwise
	 *  an array with an error message.
	 */
	function _checkForTar() {
		$tarBinary = Config::getVar('cli', 'tar');
		if (empty($tarBinary) || !is_executable($tarBinary)) {
			$result = array(
				array('manager.plugins.tarCommandNotFound')
			);
		} else {
			$result = true;
		}
		$this->_checkedForTar = true;
		return $result;
	}

	/**
	 * Return the plug-ins export directory.
	 *
	 * This will create the directory if it doesn't exist yet.
	 *
	 * @return string|array The export directory name or an array with
	 *  errors if something went wrong.
	 */
	function _getExportPath() {
		$exportPath = Config::getVar('files', 'files_dir') . '/' . $this->getPluginId();
		if (!file_exists($exportPath)) {
			FileManager::mkdir($exportPath);
		}
		if (!is_writable($exportPath)) {
			$errors = array(
				array('plugins.importexport.common.export.error.outputFileNotWritable', $exportPath)
			);
			return $errors;
		}
		return realpath($exportPath) . '/';
	}

	/**
	 * Retrieve the objects corresponding to the given ids.
	 * @param $exportType integer One of the DOI_EXPORT_* constants.
	 * @param $objectIds integer|array
	 * @param $journalId integer
	 * @param $errors array
	 * @return array|boolean
	 */
	function &_getObjectsFromIds($exportType, $objectIds, $journalId, &$errors) {
		$falseVar = false;
		if (empty($objectIds)) return $falseVar;
		if (!is_array($objectIds)) $objectIds = array($objectIds);

		// Instantiate the correct DAO.
		list($daoName, $daoMethod) = $this->getDaoName($exportType);
		$dao =& DAORegistry::getDAO($daoName);
		$daoMethod = array($dao, $daoMethod);

		$objects = array();
		foreach ($objectIds as $objectId) {
			// Retrieve the objects from the DAO.
			$daoMethodArgs = array($objectId);
			if ($exportType != DOI_EXPORT_GALLEYS) {
				$daoMethodArgs[] = $journalId;
			}
			$foundObjects =& call_user_func_array($daoMethod, $daoMethodArgs);
			if (!$foundObjects || empty($foundObjects)) {
				$objectNotFoundKey = $this->getObjectNotFoundErrorKey($exportType);
				$errors[] = array($objectNotFoundKey, $objectId);
				return $falseVar;
			}

			// Add the objects to our result array.
			if (!is_array($foundObjects)) $foundObjects = array($foundObjects);
			foreach ($foundObjects as $foundObject) {
				// Only export objects that have a DOI assigned.
				// NB: This may generate DOIs for the selected
				// objects on the fly.
				if (!is_null($foundObject->getPubId('doi'))) $objects[] =& $foundObject;
				unset($foundObject);
			}
			unset($foundObjects);
		}

		return $objects;
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
			echo __('plugins.importexport.common.cliError') . "\n";
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
			'plugins.importexport.' . $this->getPluginId() . '.cliUsage',
			array(
				'scriptName' => $scriptName,
				'pluginName' => $this->getName()
			)
		) . "\n";
	}

	/**
	 * Instantiate the settings form.
	 * @param $journal Journal
	 * @return DoiExportSettingsForm
	 */
	function &_instantiateSettingsForm(&$journal) {
		$settingsFormClassName = $this->getSettingsFormClassName();
		$this->import('classes.form.' . $settingsFormClassName);
		$settingsForm = new $settingsFormClassName($this, $journal->getId());
		assert(is_a($settingsForm, 'DoiExportSettingsForm'));
		return $settingsForm;
	}

	/**
	 * Add a notification.
	 * @param $request Request
	 * @param $message string An i18n key.
	 * @param $notificationType integer One of the NOTIFICATION_TYPE_* constants.
	 * @param $param string An additional parameter for the message.
	 */
	function _sendNotification(&$request, $message, $notificationType, $param = null) {
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

		$user =& $request->getUser();
		$notificationManager->createTrivialNotification(
			$user->getId(),
			$notificationType,
			array('contents' => __($message, $params))
		);
	}
}

?>
