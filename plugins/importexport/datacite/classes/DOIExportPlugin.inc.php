<?php

/**
 * @file plugins/importexport/.../classes/DOIExportPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOIExportPlugin
 * @ingroup plugins_importexport_..._classes
 *
 * @brief Base class for DOI export/registration plugins.
 */


import('classes.plugins.ImportExportPlugin');

// Export types.
define('DOI_EXPORT_ISSUES', 0x01);
define('DOI_EXPORT_ARTICLES', 0x02);
define('DOI_EXPORT_GALLEYS', 0x03);
define('DOI_EXPORT_SUPPFILES', 0x04);

// Current registration state.
define('DOI_OBJECT_NEEDS_UPDATE', 0x01);
define('DOI_OBJECT_REGISTERED', 0x02);

// Export file types.
define('DOI_EXPORT_FILE_XML', 0x01);
define('DOI_EXPORT_FILE_TAR', 0x02);

// Configuration errors.
define('DOI_EXPORT_CONFIGERROR_DOIPREFIX', 0x01);
define('DOI_EXPORT_CONFIGERROR_SETTINGS', 0x02);

// The name of the setting used to save the registered DOI.
define('DOI_EXPORT_REGDOI', 'registeredDoi');

class DOIExportPlugin extends ImportExportPlugin {

	//
	// Protected Properties
	//
	/** @var PubObjectCache */
	var $_cache;

	function &getCache() {
		if (!is_a($this->_cache, 'PubObjectCache')) {
			// Instantiate the cache.
			if (!class_exists('PubObjectCache')) { // Bug #7848
				$this->import('classes.PubObjectCache');
			}
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
	function DOIExportPlugin() {
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

		HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'callbackParseCronTab'));

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
	 *
	 * This supports the following actions:
	 * - index: the plug-ins home page
	 * - all, issues, articles, galleys, suppFiles: lists with exportable objects
	 * - exportIssue, exportArticle, exportGalley, exportSuppFile: export a single object
	 * - exportIssues, exportArticles, exportGalleys, exportSuppFiles: export several objects at a time
	 * - registerIssue, registerArticle, registerGalley, registerSuppFile: register a single object
	 * - registerIssues, registerArticles, registerGalleys, registerSuppFiles: register several objects at a time
	 * - resetIssue, resetArticle, resetGalley, resetSuppFile: reset an object to "unregistered" state.
	 */
	function display(&$args, &$request) {
		parent::display($args, $request);
		$templateMgr =& TemplateManager::getManager();

		// Retrieve journal from the request context.
		$journal =& $request->getJournal();

		$op = array_shift($args);

		switch($op) {
			// Show the plugin homepage
			case '':
			case 'index':
				return $this->_displayPluginHomePage($templateMgr, $journal);

			// Display cases: show a list of the specified objects
			case 'all':
			case 'issues':
			case 'articles':
			case 'galleys':
			case 'suppFiles':
				// Test mode.
				$templateMgr->assign('testMode', $this->isTestMode($request)?array('testMode' => 1):array());

				// Export without account.
				$username = $this->getSetting($journal->getId(), 'username');
				$templateMgr->assign('hasCredentials', !empty($username));

				switch ($op) {
					case 'issues':
						return $this->displayIssueList($templateMgr, $journal);
					case 'articles':
						return $this->displayArticleList($templateMgr, $journal);
					case 'galleys':
						return $this->_displayGalleyList($templateMgr, $journal);
					case 'suppFiles':
						return $this->displaySuppFileList($templateMgr, $journal);
					case 'all':
						return $this->displayAllUnregisteredObjects($templateMgr, $journal);
				}

			// Process register/reset/export/mark actions.
			case 'process':
				$this->_process($request, $journal);
				break;

			default:
				fatalError('Invalid command.');
		}
	}

	/**
	 * Process a DOI activity request.
	 * @param $request PKPRequest
	 * @param $journal Journal
	 */
	function _process(&$request, &$journal) {
		$objectTypes = $this->getAllObjectTypes();
		$target = $request->getUserVar('target');
		$result = false;

		// Dispatch the action.
		switch(true) {
			case $request->getUserVar('export'):
			case $request->getUserVar('register'):
			case $request->getUserVar('markRegistered'):
				// Find the objects to be exported (registered).
				if ($target == 'all') {
					$exportSpec = array();
					foreach ($objectTypes as $objectName => $exportType) {
						$objectIds = (array) $request->getUserVar($objectName . 'Id');
						if (!empty($objectIds)) {
							$exportSpec[$exportType] = $objectIds;
						}
					}
				} else {
					assert(isset($objectTypes[$target]));
					$exportSpec = array($objectTypes[$target] => (array) $request->getUserVar($target . 'Id'));
				}

				if ($request->getUserVar('export')) {
					// Export selected objects.
					$result = $this->exportObjects($request, $exportSpec, $journal);
				} elseif ($request->getUserVar('markRegistered')) {
					foreach($exportSpec as $exportType => $objectIds) {
						// Normalize the object id(s) into an array.
						if (is_scalar($objectIds)) $objectIds = array($objectIds);
						// Retrieve the object(s).
						$objects =& $this->_getObjectsFromIds($exportType, $objectIds, $journal->getId(), $errors);
						$this->processMarkRegistered($request, $exportType, $objects, $journal);
					}
					// Redisplay the changed object list.
					$listAction = $target . ($target == 'all' ? '' : 's');
					$request->redirect(
						null, null, null,
						array('plugin', $this->getName(), $listAction),
						($this->isTestMode($request) ? array('testMode' => 1) : null)
					);
					break;
				} else { // Register selected objects.
					assert($request->getUserVar('register'));
					$result = $this->registerObjects($request, $exportSpec, $journal);

					// Provide the user with some visual feedback that
					// registration was successful.
					if ($result === true) {
						$this->_sendNotification(
							$request,
							'plugins.importexport.common.register.success',
							NOTIFICATION_TYPE_SUCCESS
						);

						// Redisplay the changed object list.
						$listAction = $target . ($target == 'all' ? '' : 's');
						$request->redirect(
							null, null, null,
							array('plugin', $this->getName(), $listAction),
							($this->isTestMode($request) ? array('testMode' => 1) : null)
						);
					}
				}
				break;
			case $request->getUserVar('reset'):
				// Reset the selected target object to "unregistered" state.
				$ids = (array) $request->getUserVar($target . 'Id');
				$result = $this->resetRegistration($objectTypes[$target], array_shift($ids), $journal);

				// Redisplay the changed object list.
				if ($result === true) {
					$request->redirect(
						null, null, null,
						array('plugin', $this->getName(), $target.'s'),
						($this->isTestMode($request) ? array('testMode' => 1) : null)
					);
				}
				break;
		}

		// Redirect to the index page.
		if ($result !== true) {
			if (is_array($result)) {
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

		// Add additional locale file.
		AppLocale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));

		// Command.
		$command = strtolower_codesafe(array_shift($args));
		if (!in_array($command, array('export', 'register'))) {
			$result = false;
		}

		if ($command == 'export') {
			// Output file.
			if (is_array($result)) {
				$xmlFile = array_shift($args);
				if (empty($xmlFile)) {
					$result = false;
				}
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

		// Object type.
		if (is_array($result) && empty($result)) {
			$objectType = strtolower_codesafe(array_shift($args));

			// Accept both singular and plural forms.
			if (substr($objectType, -1) == 's') $objectType = substr($objectType, 0, -1);
			if ($objectType == 'suppfile') $objectType = 'suppFile';

			// Check whether the object type exists.
			$objectTypes = $this->getAllObjectTypes();
			if (!in_array($objectType, array_keys($objectTypes))) {
				// Return an error for unhandled object types.
				$result[] = array('plugins.importexport.common.export.error.unknownObjectType', $objectType);
			}
		}

		// Export (or register) objects.
		if (is_array($result) && empty($result)) {
			assert(isset($objectTypes[$objectType]));
			$exportSpec = array($objectTypes[$objectType] => $args);
			$request =& Application::getRequest();
			if ($command == 'export') {
				$result = $this->exportObjects($request, $exportSpec, $journal, $xmlFile);
			} else {
				$result = $this->registerObjects($request, $exportSpec, $journal);
				if ($result === true) {
					echo __('plugins.importexport.common.register.success') . "\n";
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
	function manage($verb, $args, &$message, &$messageParams, &$request) {
		parent::manage($verb, $args, $message, $messageParams, $request);

		switch ($verb) {
			case 'settings':
				$journal =& $request->getJournal();
				$form =& $this->_instantiateSettingsForm($journal);

				// FIXME: JM: duplicate code from _displayPluginHomePage()
				// Check for configuration errors:
				$configurationErrors = array();

				// 1) missing DOI prefix
				$doiPrefix = null;
				$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
				if (isset($pubIdPlugins['DOIPubIdPlugin'])) {
					$doiPrefix = $pubIdPlugins['DOIPubIdPlugin']->getSetting($journal->getId(), 'doiPrefix');
				}
				if (empty($doiPrefix)) {
					$configurationErrors[] = DOI_EXPORT_CONFIGERROR_DOIPREFIX;
				}

				// 2) missing plug-in setting.
				$form =& $this->_instantiateSettingsForm($journal);
				foreach($form->getFormFields() as $fieldName => $fieldType) {
					if ($form->isOptional($fieldName)) continue;

					$setting = $this->getSetting($journal->getId(), $fieldName);
					if (empty($setting)) {
						$configurationErrors[] = DOI_EXPORT_CONFIGERROR_SETTINGS;
						break;
					}
				}

				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign_by_ref('configurationErrors', $configurationErrors);
				// JM end duplicate code

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
	 * Return the object types supported by this plug-in.
	 * @return array An array with object names and the
	 *  corresponding export types.
	 */
	function getAllObjectTypes() {
		return array(
			'issue' => DOI_EXPORT_ISSUES,
			'article' => DOI_EXPORT_ARTICLES,
			'galley' => DOI_EXPORT_GALLEYS
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

		// Filter only issues that have a DOI assigned.
		$issues = array();
		while ($issue =& $issueIterator->next()) {
			if ($issue->getPubId('doi')) {
				$issues[] =& $issue;
			}
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
	 * Display a list of all yet unregistered objects.
	 * @param $templateMgr TemplateManager
	 * @param $journal Journal
	 */
	function displayAllUnregisteredObjects(&$templateMgr, &$journal) {
		$this->setBreadcrumbs(array(), true);
		AppLocale::requireComponents(array(LOCALE_COMPONENT_PKP_SUBMISSION));

		// Prepare and display the template.
		$templateMgr->assign_by_ref('issues', $this->_getUnregisteredIssues($journal));
		$templateMgr->assign_by_ref('articles', $this->_getUnregisteredArticles($journal));
		$templateMgr->assign_by_ref('galleys', $this->_getUnregisteredGalleys($journal));
		$templateMgr->display($this->getTemplatePath() . 'all.tpl');
	}

	/**
	 * Display a list of supplementary files for export.
	 * @param $templateMgr TemplateManager
	 * @param $journal Journal
	 */
	function displaySuppFileList(&$templateMgr, &$journal) {
		fatalError('Not implemented for this plug-in');
	}

	/**
	 * Retrieve all published articles.
	 * @param $journal Journal
	 * @return DAOResultFactory
	 */
	function getAllPublishedArticles(&$journal) {
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		$articleIterator = $publishedArticleDao->getPublishedArticlesByJournalId($journal->getId());

		// Return articles from published issues only.
		$articles = array();
		while ($article = $articleIterator->next()) {
			// Retrieve issue
			$issue = $this->_getArticleIssue($article, $journal);

			// Check whether the issue is published.
			if ($issue->getPublished()) {
				$articles[] = $article;
				unset($article);
			}
		}
		unset($articleIterator);

		return $articles;
	}

	/**
	 * Identify published article and issue of the given article file.
	 * @param $articleFile ArticleFile
	 * @param $journal Journal
	 * @return array|null An array with the article and issue of the given
	 *  article file. Null will be returned if one of these objects cannot
	 *  be identified (e.g. when the article file belongs to an unpublished
	 *  article).
	 */
	function &prepareArticleFileData(&$articleFile, &$journal) {
		// Prepare and return article data for the article file.
		$articleData =& $this->_prepareArticleDataByArticleId($articleFile->getArticleId(), $journal);
		if (!is_array($articleData)) {
			$nullVar = null;
			return $nullVar;
		}

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
	 * @return boolean|array True for success or an array of error messages.
	 */
	function exportObjects(&$request, $exportSpec, &$journal, $outputFile = null) {
		// Initialize local variables.
		$errors = array();

		// If we have more than one object type, then we'll need the
		// tar tool to package the resulting export files. Check this
		// early on to avoid unnecessary export processing.
		if (count($exportSpec) > 1) {
			if (is_array($errors = $this->_checkForTar())) return $errors;
		}

		// Get the target directory.
		$result = $this->_getExportPath();
		if (is_array($result)) return $result;
		$exportPath = $result;

		// Run through the export spec and generate the corresponding
		// export files.
		$exportFiles = $this->_generateExportFilesForObjects($request, $journal, $exportSpec, $exportPath, $errors);
		if ($exportFiles === false) {
			return $errors;
		}

		// Check whether we need the tar tool for this export if
		// we've not checked this before.
		if (count($exportFiles) > 1 && !$this->_checkedForTar) {
			if (is_array($errors = $this->_checkForTar())) {
				$this->cleanTmpfiles($exportPath, array_keys($exportFiles));
				return $errors;
			}
		}

		// If we have more than one export file we package the files
		// up as a single tar before going on.
		assert(count($exportFiles) >= 1);
		if (count($exportFiles) > 1) {
			$finalExportFileName = $exportPath . $this->getPluginId() . '-export.tar.gz';
			$finalExportFileType = DOI_EXPORT_FILE_TAR;
			$this->tarFiles($exportPath, $finalExportFileName, array_keys($exportFiles));
			$exportFiles[$finalExportFileName] = array();
		} else {
			$finalExportFileName = key($exportFiles);
			$finalExportFileType = DOI_EXPORT_FILE_XML;
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
				$this->cleanTmpfiles($exportPath, array_keys($exportFiles));
				$errors[] = array('plugins.importexport.common.export.error.outputFileNotWritable', $outputFile);
				return $errors;
			}
			$fileManager = new FileManager();
			$fileManager->copyFile($finalExportFileName, $outputFile);
		}

		// Remove all temporary files.
		$this->cleanTmpfiles($exportPath, array_keys($exportFiles));

		return true;
	}

	/**
	 * Register publishing objects.
	 *
	 * @param $request Request
	 * @param $exportSpec array An array with DOI_EXPORT_* constants as keys and
	 *  object ids as values.
	 * @param $journal Journal
	 *
	 * @return boolean|array True for success or an array of error messages.
	 */
	function registerObjects(&$request, $exportSpec, &$journal) {
		// Registering can take a long time.
		@set_time_limit(0);

		// Get the target directory.
		$result = $this->_getExportPath();
		if (is_array($result)) return $result;
		$exportPath = $result;

		// Run through the export spec and generate the corresponding
		// export files.
		$errors = array();
		$exportFiles = $this->_generateExportFilesForObjects($request, $journal, $exportSpec, $exportPath, $errors);
		if ($exportFiles === false) {
			return $errors;
		}

		// Register DOIs and their meta-data.
		foreach($exportFiles as $exportFile => $objects) {
			$result = $this->registerDoi($request, $journal, $objects, $exportFile);
			if ($result !== true) {
				$this->cleanTmpfiles($exportPath, array_keys($exportFiles));
				return $result;
			}
		}

		// Remove all temporary files.
		$this->cleanTmpfiles($exportPath, array_keys($exportFiles));

		return true;
	}

	/**
	 * Returns file name and file type of the target export
	 * file for the given export type and object ids.
	 * @param $exportPath string
	 * @param $exportType integer One of the DOI_EXPORT_* constants.
	 * @param $objectId int An optional object id.
	 * @return string The generated file name.
	 */
	function getTargetFileName($exportPath, $exportType, $objectId = null) {
		// Define the prefix of the exported files.
		$targetFileName = $exportPath . date('Ymd-Hi-') . $this->getObjectName($exportType);

		// Define the target file type and the final target file name.
		if (is_null($objectId)) {
			$targetFileName .= 's.xml';
		} else {
			$targetFileName .= '-' . $objectId . '.xml';
		}
		return $targetFileName;
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
	 * The selected object can be exported if it has a DOI.
	 * @param $foundObject Issue|PublishedArticle|ArticleGalley|SuppFile
	 * @param $errors array
	 * @return array|boolean
	*/
	function canBeExported($foundObject, &$errors) {
		return !is_null($foundObject->getPubId('doi'));
	}

	/**
	 * Generate the export data model.
	 * @param $request Request
	 * @param $exportType integer
	 * @param $objects array
	 * @param $targetPath string
	 * @param $journal Journal
	 * @param $errors array Output parameter for error details when
	 *  the function returns false.
	 * @return array|boolean Either an array of generated export
	 *  files together with the contained objects or false if not successful.
	 */
	function generateExportFiles(&$request, $exportType, &$objects, $targetPath, &$journal, &$errors) {
		assert(false);
	}

	/**
	 * Process the marking of the selected objects as registered.
	 * @param $request Request
	 * @param $exportType integer
	 * @param $objects array
	 * @param $journal Journal
	 */
	function processMarkRegistered(&$request, $exportType, &$objects, &$journal) {
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
	 * Register the given DOI.
	 * @param $request Request
	 * @param $journal Journal
	 * @param $objects array
	 * @param $file string
	 */
	function registerDoi(&$request, &$journal, &$objects, $file) {
		fatalError('Not implemented for this plug-in');
	}

	/**
	 * Check whether we are in test mode.
	 * @param $request Request
	 * @return boolean
	 */
	function isTestMode(&$request) {
		return ($request->getUserVar('testMode') == '1');
	}

	/**
	 * Mark an object as "registered"
	 * by saving it's DOI to the object's
	 * "registeredDoi" setting.
	 * We prefix the setting with the plug-in's
	 * id so that we do not get name clashes
	 * when several DOI registration plug-ins
	 * are active at the same time.
	 * @parem $request Request
	 * @param $object Issue|PublishedArticle|ArticleGalley|SuppFile
	 * @parem $testPrefix string
	 */
	function markRegistered(&$request, &$object, $testPrefix = '10.1234') {
		$registeredDoi = $object->getPubId('doi');
		assert(!empty($registeredDoi));
		if ($this->isTestMode($request)) {
			$registeredDoi = String::regexp_replace('#^[^/]+/#', $testPrefix . '/', $registeredDoi);
		}
		$this->saveRegisteredDoi($object, $registeredDoi);
	}

	/**
	 * Reset the given object.
	 *
	 * @param $objectType integer A DOI_EXPORT_* constant.
	 * @param $objectId integer The ID of the object to be reset.
	 * @param $journal Journal
	 *
	 * @return boolean|array An array of error messages if something went
	 *  wrong or boolean 'true' for success.
	 */
	function resetRegistration($objectType, $objectId, &$journal) {
		// Identify the object to be reset.
		$errors = array();
		$objects =& $this->_getObjectsFromIds($objectType, $objectId, $journal->getId(), $errors);
		if ($objects === false || count($objects) != 1) {
			return $errors;
		}

		// Reset the object.
		$this->saveRegisteredDoi($objects[0], '');

		return true;
	}

	/**
	 * Set the object's "registeredDoi" setting.
	 * @param $object Issue|PublishedArticle|ArticleGalley|SuppFile
	 * @parem $registeredDoi string
	 */
	function saveRegisteredDoi(&$object, $registeredDoi) {
		// Identify the dao name and update method for the given object.
		$configurations = array(
			'Issue' => array('IssueDAO', 'updateIssue'),
			'Article' => array('ArticleDAO', 'updateArticle'),
			'ArticleGalley' => array('ArticleGalleyDAO', 'updateGalley'),
			'SuppFile' => array('SuppFileDAO', 'updateSuppFile')
		);
		$foundConfig = false;
		foreach($configurations as $objectType => $configuration) {
			if (is_a($object, $objectType)) {
				$foundConfig = true;
				break;
			}
		}
		assert($foundConfig);
		list($daoName, $daoMethod) = $configuration;

		// Register a hook for the required additional
		// object fields. We do this on a temporary
		// basis as the hook adds a performance overhead
		// and the field will "stealthily" survive even
		// when the DAO does not know about it.
		$this->registerDaoHook($daoName);
		$dao =& DAORegistry::getDAO($daoName);
		$object->setData($this->getPluginId() . '::' . DOI_EXPORT_REGDOI, $registeredDoi);
		$dao->$daoMethod($object);
	}

	/**
	 * Register the hook that adds an
	 * additional field name to objects.
	 * @param $daoName string
	 */
	function registerDaoHook($daoName) {
		HookRegistry::register(strtolower_codesafe($daoName) . '::getAdditionalFieldNames', array(&$this, 'getAdditionalFieldNames'));
	}

	/**
	 * Hook callback that returns the
	 * "registeredDoi" setting's name prefixed with
	 * the plug-in's id to avoid name collisions.
	 * @see DAO::getAdditionalFieldNames()
	 * @param $hookName string
	 * @param $args array
	 */
	function getAdditionalFieldNames($hookName, $args) {
		assert(count($args) == 2);
		$dao =& $args[0];
		$returner =& $args[1];
		assert(is_array($returner));
		$returner[] = $this->getPluginId() . '::' . DOI_EXPORT_REGDOI;
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
	 * @param $templateMgr TemplageManager
	 * @param $journal Journal
	 */
	function _displayPluginHomePage(&$templateMgr, &$journal) {
		$this->setBreadcrumbs();

		// Check for configuration errors:
		$configurationErrors = array();

		// 1) missing DOI prefix
		$doiPrefix = null;
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		if (isset($pubIdPlugins['DOIPubIdPlugin'])) {
			$doiPrefix = $pubIdPlugins['DOIPubIdPlugin']->getSetting($journal->getId(), 'doiPrefix');
		}
		if (empty($doiPrefix)) {
			$configurationErrors[] = DOI_EXPORT_CONFIGERROR_DOIPREFIX;
		}

		// 2) missing plug-in setting.
		$form =& $this->_instantiateSettingsForm($journal);
		foreach($form->getFormFields() as $fieldName => $fieldType) {
			if ($form->isOptional($fieldName)) continue;

			$setting = $this->getSetting($journal->getId(), $fieldName);
			if (empty($setting)) {
				$configurationErrors[] = DOI_EXPORT_CONFIGERROR_SETTINGS;
				break;
			}
		}

		$templateMgr->assign_by_ref('configurationErrors', $configurationErrors);

		// Prepare and display the index page template.
		$templateMgr->assign_by_ref('journal', $journal);
		$templateMgr->display($this->getTemplatePath() . 'index.tpl');
	}

	/**
	 * Display a list of articles for export.
	 * @param $templateMgr TemplateManager
	 * @param $journal Journal
	 */
	function displayArticleList(&$templateMgr, &$journal) {
		$this->setBreadcrumbs(array(), true);

		// Retrieve all published articles.
		$this->registerDaoHook('PublishedArticleDAO');
		$allArticles = $this->getAllPublishedArticles($journal);

		// Filter only articles that have a DOI assigned.
		$articles = array();
		foreach($allArticles as $article) {
			if ($article->getPubId('doi')) {
				$articles[] = $article;
			}
			unset($article);
		}
		unset($allArticles);

		// Paginate articles.
		$totalArticles = count($articles);
		$rangeInfo = Handler::getRangeInfo('articles');
		if ($rangeInfo->isValid()) {
			$articles = array_slice($articles, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
		}

		// Retrieve article data.
		$articleData = array();
		foreach($articles as $article) {
			$preparedArticle = $this->_prepareArticleData($article, $journal);
			// We should always get a prepared article as we've already
			// filtered non-published articles above.
			assert(is_array($preparedArticle));
			$articleData[] = $preparedArticle;
			unset($article, $preparedArticle);
		}
		unset($articles);

		// Instantiate article iterator.
		import('lib.pkp.classes.core.VirtualArrayIterator');
		$iterator = new VirtualArrayIterator($articleData, $totalArticles, $rangeInfo->getPage(), $rangeInfo->getCount());

		// Prepare and display the article template.
		$templateMgr->assign_by_ref('articles', $iterator);
		$templateMgr->display($this->getTemplatePath() . 'articles.tpl');
	}

	/**
	 * Display a list of galleys for export.
	 * @param $templateMgr TemplateManager
	 * @param $journal Journal
	 */
	function _displayGalleyList(&$templateMgr, &$journal) {
		$this->setBreadcrumbs(array(), true);

		// Retrieve all published articles.
		$allArticles = $this->getAllPublishedArticles($journal);

		// Retrieve galley data.
		$this->registerDaoHook('ArticleGalleyDAO');
		$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
		$galleys = array();
		foreach($allArticles as $article) {
			// Retrieve galleys for the article.
			$articleGalleys =& $galleyDao->getGalleysByArticle($article->getId());

			// Filter only galleys that have a DOI assigned.
			foreach ($articleGalleys as $galley) {
				if ($galley->getPubId('doi')) {
					$galleys[] =& $galley;
				}
				unset($galley);
			}
			unset($article, $articleGalleys);
		}
		unset($allArticles);

		// Paginate galleys.
		$totalGalleys = count($galleys);
		$rangeInfo = Handler::getRangeInfo('galleys');
		if ($rangeInfo->isValid()) {
			$galleys = array_slice($galleys, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
		}

		// Retrieve galley data.
		$galleyData = array();
		foreach($galleys as $galley) {
			$preparedGalley =& $this->_prepareGalleyData($galley, $journal);
			// As we select only published articles, we should always
			// get data back here.
			assert(is_array($preparedGalley));
			if (is_array($preparedGalley)) {
				$galleyData[] =& $preparedGalley;
			}
			unset($galley, $preparedGalley);
		}
		unset($galleys);

		// Instantiate galley iterator.
		import('lib.pkp.classes.core.VirtualArrayIterator');
		$iterator = new VirtualArrayIterator($galleyData, $totalGalleys, $rangeInfo->getPage(), $rangeInfo->getCount());

		// Prepare and display the galley template.
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
		$issues = $issueDao->getIssuesBySetting($this->getPluginId(). '::' . DOI_EXPORT_REGDOI, null, $journal->getId());

		// Filter and cache issues.
		$nullVar = null;
		$cache =& $this->getCache();
		$issueData = array();
		foreach ($issues as $issue) {
			$cache->add($issue, $nullVar);
			if ($issue->getPublished()) {
				// Only propose published issues for export.
				$issueData[] =& $issue;
			}
			unset($issue);
		}
		return $issueData;
	}

	/**
	 * Retrieve all unregistered articles and their corresponding issues.
	 * @param $journal Journal
	 * @return array
	 */
	function &_getUnregisteredArticles(&$journal) {
		// Retrieve all published articles that have not yet been registered.
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		$articles = $publishedArticleDao->getBySetting($this->getPluginId(). '::' . DOI_EXPORT_REGDOI, null, $journal->getId());

		// Retrieve issues for articles.
		$articleData = array();
		foreach ($articles as $article) {
			$preparedArticle = $this->_prepareArticleData($article, $journal);
			if (is_array($preparedArticle)) {
				$articleData[] = $preparedArticle;
			}
			unset($article, $preparedArticle);
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
		$galleys = $galleyDao->getGalleysBySetting($this->getPluginId(). '::' . DOI_EXPORT_REGDOI, null, null, $journal->getId());

		// Retrieve issues, articles and language for galleys.
		$galleyData = array();
		foreach ($galleys as $galley) {
			$preparedGalley =& $this->_prepareGalleyData($galley, $journal);
			if (is_array($preparedGalley)) {
				$galleyData[] =& $preparedGalley;
			}
			unset($galley, $preparedGalley);
		}
		return $galleyData;
	}

	/**
	 * Identify published article, issue and language of the given galley.
	 * @param $galley ArticleGalley
	 * @param $journal Journal
	 * @return array|null An array with article, issue and language of
	 *  the given galley. Null will be returned if one of these objects
	 *  cannot be identified for the galley (e.g. when the galley belongs
	 *  to an unpublished article).
	 */
	function &_prepareGalleyData(&$galley, &$journal) {
		// Retrieve article and issue for the galley.
		$galleyData =& $this->prepareArticleFileData($galley, $journal);
		if (!is_array($galleyData)) {
			$nullVar = null;
			return $nullVar;
		}

		// Add the galley language.
		$languageDao =& DAORegistry::getDAO('LanguageDAO'); /* @var $languageDao LanguageDAO */
		$galleyData['language'] =& $languageDao->getLanguageByCode(AppLocale::getIso1FromLocale($galley->getLocale()));

		// Add the galley itself.
		$galleyData['galley'] =& $galley;

		return $galleyData;
	}

	/**
	 * Identify published article and issue for the given article id.
	 * @param $articleId integer
	 * @param $journal Journal
	 * @return array|null An array with the published article and issue of the
	 *  given article ID. If a published article cannot be identified (i.e. if
	 *  the given article ID belongs to an unpublished article) then null will
	 *  be returned.
	 */
	function &_prepareArticleDataByArticleId($articleId, &$journal) {
		// Get the cache.
		$cache =& $this->getCache();

		// Retrieve article if not yet cached.
		$article = null;
		if (!$cache->isCached('articles', $articleId)) {
			$nullVar = null;
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
			$article =& $publishedArticleDao->getPublishedArticleByArticleId($articleId, $journal->getId(), true);
			if (!is_a($article, 'PublishedArticle')) {
				// It seems that the article ID we got does not belong to a
				// published article. This may happen if we try to prepare
				// article data for a galley or supplementary file.
				return $nullVar;
			}
			$cache->add($article, $nullVar);
		}
		if (!$article) $article =& $cache->get('articles', $articleId);
		assert(is_a($article, 'PublishedArticle'));

		// Prepare and return article data for the article file.
		return $this->_prepareArticleData($article, $journal);
	}

	/**
	 * Identify the issue of the given article.
	 * @param $article PublishedArticle
	 * @param $journal Journal
	 * @return array|null Return prepared article data or
	 *  null if the article is not from a published issue.
	 */
	function &_prepareArticleData(&$article, &$journal) {
		$nullVar = null;

		// Add the article to the cache.
		$cache =& $this->getCache();
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
		$cache = $this->getCache();
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
	 * Generate export files for the given export spec.
	 * @param $request Request
	 * @param $journal Journal
	 * @param $exportSpec array
	 * @param $exportPath string
	 * @param $errors array
	 * @return array A list of generated files together with the
	 *  objects contained within the file.
	 */
	function _generateExportFilesForObjects(&$request, &$journal, $exportSpec, $exportPath, &$errors) {
		// Run through the export types and generate the corresponding
		// export files.
		$exportFiles = array();
		foreach($exportSpec as $exportType => $objectIds) {
			// Normalize the object id(s) into an array.
			if (is_scalar($objectIds)) $objectIds = array($objectIds);

			// Retrieve the object(s).
			$objects =& $this->_getObjectsFromIds($exportType, $objectIds, $journal->getId(), $errors);
			if (empty($objects)) {
				$this->cleanTmpfiles($exportPath, $exportFiles);
				return false;
			}

			// Export the object(s) to a file.
			$newFiles = $this->generateExportFiles($request, $exportType, $objects, $exportPath, $journal, $errors);
			if ($newFiles === false) {
				$this->cleanTmpfiles($exportPath, $exportFiles);
				return false;
			}

			// Add the new files to the result array.
			$exportFiles = array_merge($exportFiles, $newFiles);
		}

		return $exportFiles;
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
			$fileManager = new FileManager();
			$fileManager->mkdir($exportPath);
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
			if ($exportType != DOI_EXPORT_GALLEYS && $exportType != DOI_EXPORT_SUPPFILES) {
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
				// Only consider objects that should be exported.
				// NB: This may generate DOIs for the selected
				// objects on the fly.
				if ($this->canBeExported($foundObject, $errors)) $objects[] =& $foundObject;
				else return $falseVar;
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
	 * @return DOIExportSettingsForm
	 */
	function &_instantiateSettingsForm(&$journal) {
		$settingsFormClassName = $this->getSettingsFormClassName();
		$this->import('classes.form.' . $settingsFormClassName);
		$settingsForm = new $settingsFormClassName($this, $journal->getId());
		assert(is_a($settingsForm, 'DOIExportSettingsForm'));
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

	/**
	 * @see AcronPlugin::parseCronTab()
	 */
	function callbackParseCronTab($hookName, $args) {
		return false;
	}
}

?>
