<?php

/**
 * @file plugins/importexport/.../classes/DoiExportPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
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

		$op = strtolower(array_shift($args));

		// Check whether we deal with a single object or
		// multiple objects.
		$multiSelect = (substr($op, -1) == 's');

		// Check whether we serve an exportation request.
		if (substr($op, 0, 6) == 'export') {
			// Check whether the "register" button was clicked.
			if ($request->isPost() && !is_null($request->getUserVar('register'))) {
				// Fix the operation name so that we can check
				// operations in a single switch statement.
				$action = 'register';
			} else {
				$action = 'export';
			}
		// Check whether we serve a registration request.
		} elseif (substr($op, 0, 8) == 'register') {
			$action = 'register';
		// Check whether we serve a reset request (which is allowed for single targets only).
		} elseif (!$multiSelect && substr($op, 0, 5) == 'reset') {
			$action = 'reset';
		// By default we assume a display request.
		} else {
			$action = 'display';
		}

		// Identify the operation's target. The index is the default.
		// NB: Order is relevant! The word "galley" contains "all" and
		// "index" must be last so that it is selected if nothing else
		// matches.
		$objectTypes = $this->getAllObjectTypes();
		$targets = array_keys($objectTypes);
		if ($action != 'reset') $targets[] = 'all';
		$targets[] = 'index';
		foreach($targets as $target) {
			if (strpos($op, strtolower($target)) !== false) break;
		}
		if ($target == 'index') $action = 'display';

		// Dispatch the action.
		switch($action) {
			case 'export':
			case 'register':
				// Find the objects to be exported (registered).
				if ($target == 'all') {
					$exportSpec = array();
					foreach ($objectTypes as $objectName => $exportType) {
						$objectIds = $request->getUserVar($objectName . 'Id');
						if (!empty($objectIds)) {
							$exportSpec[$exportType] = $objectIds;
						}
					}
				} else {
					assert(isset($objectTypes[$target]));
					if ($multiSelect) {
						$exportSpec = array($objectTypes[$target] => $request->getUserVar($target . 'Id'));
					} else {
						$exportSpec = array($objectTypes[$target] => array_shift($args));
					}
				}

				if ($action == 'export') {
					// Export selected objects.
					$result = $this->exportObjects($request, $exportSpec, $journal);
				} else {
					// Register selected objects.
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
						if ($result === true) {
							$listAction = $target . ($target == 'all' ? '' : 's');
							$request->redirect(
								null, null, null,
								array('plugin', $this->getName(), $listAction),
								($this->isTestMode($request) ? array('testMode' => 1) : null)
							);
						}
					}
				}
				break;

			case 'reset':
				// Reset the selected target object to "unregistered" state.
				$result = $this->resetRegistration($objectTypes[$target], array_shift($args), $journal);

				// Redisplay the changed object list.
				if ($result === true) {
					$request->redirect(
						null, null, null,
						array('plugin', $this->getName(), $target.'s'),
						($this->isTestMode($request) ? array('testMode' => 1) : null)
					);
				}
				break;

			default: // Display.
				$templateMgr =& TemplateManager::getManager();

				// Test mode.
				$templateMgr->assign('testMode', $this->isTestMode($request));

				// Export without account.
				$username = $this->getSetting($journal->getId(), 'username');
				$templateMgr->assign('hasCredentials', !empty($username));

				switch ($target) {
					case 'issue':
						$this->_displayIssueList($templateMgr, $journal);
						break;

					case 'article':
						$this->_displayArticleList($templateMgr, $journal);
						break;

					case 'galley':
						$this->_displayGalleyList($templateMgr, $journal);
						break;

					case 'suppFile':
						$this->displaySuppFileList($templateMgr, $journal);
						break;

					case 'all':
						$this->displayAllUnregisteredObjects($templateMgr, $journal);
						break;

					default:
						$this->_displayPluginHomePage($templateMgr, $journal);
				}
				$result = true;
		}

		// Redirect to the index page.
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

		// Add additional locale file.
		AppLocale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));

		// Command.
		$command = strtolower(array_shift($args));
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
			$objectType = strtolower(array_shift($args));

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
		set_time_limit(0);

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
	function markRegistered(&$request, &$object, $testPrefix) {
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
		HookRegistry::register(strtolower($daoName) . '::getAdditionalFieldNames', array(&$this, 'getAdditionalFieldNames'));
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
		if (isset($pubIdPlugins['DoiPubIdPlugin'])) {
			$doiPrefix = $pubIdPlugins['DoiPubIdPlugin']->getSetting($journal->getId(), 'doiPrefix');
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
	 * Display a list of issues for export.
	 * @param $templateMgr TemplateManager
	 * @param $journal Journal
	 */
	function _displayIssueList(&$templateMgr, &$journal) {
		$this->setBreadcrumbs(array(), true);

		// Retrieve all published issues.
		AppLocale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR));
		$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$this->registerDaoHook('IssueDAO');
		$issues =& $issueDao->getPublishedIssues($journal->getId(), Handler::getRangeInfo('issues'));

		// Prepare and display the issue template.
		$templateMgr->assign_by_ref('issues', $issues);
		$templateMgr->display($this->getTemplatePath() . 'issues.tpl');
	}

	/**
	 * Display a list of articles for export.
	 * @param $templateMgr TemplateManager
	 * @param $journal Journal
	 */
	function _displayArticleList(&$templateMgr, &$journal) {
		$this->setBreadcrumbs(array(), true);

		// Retrieve all published articles.
		$this->registerDaoHook('PublishedArticleDAO');
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
		$articles = $this->getAllPublishedArticles($journal);

		// Retrieve galley data.
		$this->registerDaoHook('ArticleGalleyDAO');
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
		$articles = $publishedArticleDao->getPublishedArticlesBySetting($this->getPluginId(). '::' . DOI_EXPORT_REGDOI, null, $journal->getId());

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
		$galleys = $galleyDao->getGalleysBySetting($this->getPluginId(). '::' . DOI_EXPORT_REGDOI, null, null, $journal->getId());

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
			if ($objects === false) {
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
