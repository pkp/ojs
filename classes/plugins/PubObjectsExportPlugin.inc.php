<?php

/**
 * @file classes/plugins/PubObjectsExportPlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubObjectsExportPlugin
 * @ingroup plugins
 *
 * @brief Basis class for XML metadata export plugins
 */

import('lib.pkp.classes.plugins.ImportExportPlugin');

// The statuses.
define('EXPORT_STATUS_ANY', '');
define('EXPORT_STATUS_NOT_DEPOSITED', 'notDeposited');
define('EXPORT_STATUS_MARKEDREGISTERED', 'markedRegistered');
define('EXPORT_STATUS_REGISTERED', 'registered');

// The actions.
define('EXPORT_ACTION_EXPORT', 'export');
define('EXPORT_ACTION_MARKREGISTERED', 'markRegistered');
define('EXPORT_ACTION_DEPOSIT', 'deposit');

// Configuration errors.
define('EXPORT_CONFIG_ERROR_SETTINGS', 0x02);


abstract class PubObjectsExportPlugin extends ImportExportPlugin {
	/** @var PubObjectCache */
	var $_cache;

	/**
	 * Get the plugin cache
	 * @return PubObjectCache
	 */
	function getCache() {
		if (!is_a($this->_cache, 'PubObjectCache')) {
			// Instantiate the cache.
			import('classes.plugins.PubObjectCache');
			$this->_cache = new PubObjectCache();
		}
		return $this->_cache;
	}

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if ($success) {
			$this->addLocaleData();
			HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'callbackParseCronTab'));
		}
		return $success;
	}

	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		$user = $request->getUser();
		$router = $request->getRouter();
		$context = $router->getContext($request);

		$form = $this->_instantiateSettingsForm($context);
		$notificationManager = new NotificationManager();
		switch ($request->getUserVar('verb')) {
			case 'save':
				$form->readInputData();
				if ($form->validate()) {
					$form->execute();
					$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS);
					return new JSONMessage();
				} else {
					return new JSONMessage(true, $form->fetch($request));
				}
			case 'index':
				$form->initData();
				return new JSONMessage(true, $form->fetch($request));
			case 'statusMessage':
				$statusMessage = $this->getStatusMessage($request);
				if ($statusMessage) {
					$templateMgr = TemplateManager::getManager($request);
					$templateMgr->assign(array(
						'statusMessage' => htmlentities($statusMessage),
					));
					return new JSONMessage(true, $templateMgr->fetch($this->getTemplateResource('statusMessage.tpl')));
				}
		}
		return parent::manage($args, $request);
	}

	/**
	 * @copydoc ImportExportPlugin::display()
	 */
	function display($args, $request) {
		parent::display($args, $request);

		$context = $request->getContext();
		switch (array_shift($args)) {
			case 'index':
			case '':
				// Check for configuration errors:
				$configurationErrors = array();
				// missing plugin settings
				$form = $this->_instantiateSettingsForm($context);
				foreach($form->getFormFields() as $fieldName => $fieldType) {
					if ($form->isOptional($fieldName)) continue;
					$pluginSetting = $this->getSetting($context->getId(), $fieldName);
					if (empty($pluginSetting)) {
						$configurationErrors[] = EXPORT_CONFIG_ERROR_SETTINGS;
						break;
					}
				}

				// Add link actions
				$actions = $this->getExportActions($context);
				$actionNames = array_intersect_key($this->getExportActionNames(), array_flip($actions));
				import('lib.pkp.classes.linkAction.request.NullAction');
				$linkActions = array();
				foreach ($actionNames as $action => $actionName) {
					$linkActions[] = new LinkAction($action, new NullAction(), $actionName);
				}
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->assign(array(
					'plugin' => $this,
					'actionNames' => $actionNames,
					'configurationErrors' => $configurationErrors,
				));
				break;
			case 'exportSubmissions':
			case 'exportIssues':
			case 'exportRepresentations':
				$selectedSubmissions = (array) $request->getUserVar('selectedSubmissions');
				$selectedIssues = (array) $request->getUserVar('selectedIssues');
				$selectedRepresentations = (array) $request->getUserVar('selectedRepresentations');
				$tab = (string) $request->getUserVar('tab');
				$noValidation = $request->getUserVar('validation') ? false : true;

				if (empty($selectedSubmissions) && empty($selectedIssues) && empty($selectedRepresentations)) {
					fatalError(__('plugins.importexport.common.error.noObjectsSelected'));
				}
				if (!empty($selectedSubmissions)) {
					$objects = $this->getPublishedSubmissions($selectedSubmissions, $context);
					$filter = $this->getSubmissionFilter();
					$objectsFileNamePart = 'articles';
				} elseif (!empty($selectedIssues)) {
					$objects = $this->getPublishedIssues($selectedIssues, $context);
					$filter = $this->getIssueFilter();
					$objectsFileNamePart = 'issues';
				} elseif (!empty($selectedRepresentations)) {
					$objects = $this->getArticleGalleys($selectedRepresentations);
					$filter = $this->getRepresentationFilter();
					$objectsFileNamePart = 'galleys';
				}

				// Execute export action
				$this->executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart, $noValidation);
		}
	}

	/**
	 * Execute export action.
	 * @param $request Request
	 * @param $objects array Array of objects to be exported
	 * @param $filter string Filter to use
	 * @param $tab string Tab to return to
	 * @param $objectsFileNamePart string Export file name part for this kind of objects
	 * @param $noValidation boolean If set to true no XML validation will be done
	 */
	function executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart, $noValidation = null) {
		$context = $request->getContext();
		$path = array('plugin', $this->getName());
		if ($request->getUserVar(EXPORT_ACTION_EXPORT)) {
			assert($filter != null);
			// Get the XML
			$exportXml = $this->exportXML($objects, $filter, $context, $noValidation);
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			$exportFileName = $this->getExportFileName($this->getExportPath(), $objectsFileNamePart, $context, '.xml');
			$fileManager->writeFile($exportFileName, $exportXml);
			$fileManager->downloadByPath($exportFileName);
			$fileManager->deleteByPath($exportFileName);
		} elseif ($request->getUserVar(EXPORT_ACTION_DEPOSIT)) {
			assert($filter != null);
			// Get the XML
			$exportXml = $this->exportXML($objects, $filter, $context, $noValidation);
			// Write the XML to a file.
			// export file name example: crossref-20160723-160036-articles-1.xml
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			$exportFileName = $this->getExportFileName($this->getExportPath(), $objectsFileNamePart, $context, '.xml');
			$fileManager->writeFile($exportFileName, $exportXml);
			// Deposit the XML file.
			$result = $this->depositXML($objects, $context, $exportFileName);
			// send notifications
			if ($result === true) {
				$this->_sendNotification(
					$request->getUser(),
					$this->getDepositSuccessNotificationMessageKey(),
					NOTIFICATION_TYPE_SUCCESS
				);
			} else {
				if (is_array($result)) {
					foreach($result as $error) {
						assert(is_array($error) && count($error) >= 1);
						$this->_sendNotification(
							$request->getUser(),
							$error[0],
							NOTIFICATION_TYPE_ERROR,
							(isset($error[1]) ? $error[1] : null)
						);
					}
				}
			}
			// Remove all temporary files.
			$fileManager->deleteByPath($exportFileName);
			// redirect back to the right tab
			$request->redirect(null, null, null, $path, null, $tab);
		} elseif ($request->getUserVar(EXPORT_ACTION_MARKREGISTERED)) {
			$this->markRegistered($context, $objects);
			// redirect back to the right tab
			$request->redirect(null, null, null, $path, null, $tab);
		} else {
			$dispatcher = $request->getDispatcher();
			$dispatcher->handle404();
		}
	}

	/**
	 * Get the locale key used in the notification for
	 * the successful deposit.
	 */
	function getDepositSuccessNotificationMessageKey() {
		return 'plugins.importexport.common.register.success';
	}

	/**
	 * Deposit XML document.
	 * This must be implemented in the subclasses, if the action is supported.
	 * @param $objects mixed Array of or single published submission, issue or galley
	 * @param $context Context
	 * @param $filename Export XML filename
	 * @return boolean Whether the XML document has been registered
	 */
	abstract function depositXML($objects, $context, $filename);

	/**
	 * Get detailed message of the object status i.e. failure messages.
	 * Parameters needed have to be in the request object.
	 * @param $request PKPRequest
	 * @return string Preformatted text that will be displayed in a div element in the modal
	 */
	function getStatusMessage($request) {
		return null;
	}

	/**
	 * Get the submission filter.
	 * @return string|null
	 */
	function getSubmissionFilter() {
		return null;
	}

	/**
	 * Get the issue filter.
	 * @return string|null
	 */
	function getIssueFilter() {
		return null;
	}

	/**
	 * Get the representation filter.
	 * @return string|null
	 */
	function getRepresentationFilter() {
		return null;
	}

	/**
	 * Get status names for the filter search option.
	 * @return array (string status => string text)
	 */
	function getStatusNames() {
		return array(
			EXPORT_STATUS_ANY => __('plugins.importexport.common.status.any'),
			EXPORT_STATUS_NOT_DEPOSITED => __('plugins.importexport.common.status.notDeposited'),
			EXPORT_STATUS_MARKEDREGISTERED => __('plugins.importexport.common.status.markedRegistered'),
			EXPORT_STATUS_REGISTERED => __('plugins.importexport.common.status.registered'),
		);
	}

	/**
	 * Get status actions for the display to the user,
	 * i.e. links to a web site with more information about the status.
	 * @param $pubObject
	 * @return array (string status => link)
	 */
	function getStatusActions($pubObject) {
		return array();
	}

	/**
	 * Get actions.
	 * @param $context Context
	 * @return array
	 */
	function getExportActions($context) {
		$actions = array(EXPORT_ACTION_EXPORT, EXPORT_ACTION_MARKREGISTERED);
		if ($this->getSetting($context->getId(), 'username') && $this->getSetting($context->getId(), 'password')) {
			array_unshift($actions, EXPORT_ACTION_DEPOSIT);
		}
		return $actions;
	}

	/**
	 * Get action names.
	 * @return array (string action => string text)
	 */
	function getExportActionNames() {
		return array(
			EXPORT_ACTION_DEPOSIT => __('plugins.importexport.common.action.register'),
			EXPORT_ACTION_EXPORT => __('plugins.importexport.common.action.export'),
			EXPORT_ACTION_MARKREGISTERED => __('plugins.importexport.common.action.markRegistered'),
		);
	}

	/**
	 * Return the name of the plugin's deployment class.
	 * @return string
	 */
	abstract function getExportDeploymentClassName();

	/**
	 * Get the XML for selected objects.
	 * @param $objects mixed Array of or single published submission, issue or galley
	 * @param $filter string
	 * @param $context Context
	 * @return string XML document.
	 * @param $noValidation boolean If set to true no XML validation will be done
	 */
	function exportXML($objects, $filter, $context, $noValidation = null) {
		$xml = '';
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$exportFilters = $filterDao->getObjectsByGroup($filter);
		assert(count($exportFilters) == 1); // Assert only a single serialization filter
		$exportFilter = array_shift($exportFilters);
		$exportDeployment = $this->_instantiateExportDeployment($context);
		$exportFilter->setDeployment($exportDeployment);
		if ($noValidation) $exportFilter->setNoValidation($noValidation);
		libxml_use_internal_errors(true);
		$exportXml = $exportFilter->execute($objects, true);
		$xml = $exportXml->saveXml();
		$errors = array_filter(libxml_get_errors(), function($a) {
			return $a->level == LIBXML_ERR_ERROR || $a->level == LIBXML_ERR_FATAL;
		});
		if (!empty($errors)) {
			$this->displayXMLValidationErrors($errors, $xml);
		}
		return $xml;
	}

	/**
	 * Mark selected submissions or issues as registered.
	 * @param $context Context
	 * @param $objects array Array of published submissions, issues or galleys
	 */
	function markRegistered($context, $objects) {
		foreach ($objects as $object) {
			$object->setData($this->getDepositStatusSettingName(), EXPORT_STATUS_MARKEDREGISTERED);
			$this->updateObject($object);
		}
	}

	/**
	 * Update the given object.
	 * @param $object Issue|Submission|ArticleGalley
	 */
	function updateObject($object) {
		// Register a hook for the required additional
		// object fields. We do this on a temporary
		// basis as the hook adds a performance overhead
		// and the field will "stealthily" survive even
		// when the DAO does not know about it.
		$dao = $object->getDAO();
		$this->registerDaoHook(get_class($dao));
		$dao->updateObject($object);
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
	 * Hook callback that returns the setting's name prefixed with
	 * the plug-in's id to avoid name collisions.
	 * @see DAO::getAdditionalFieldNames()
	 * @param $hookName string
	 * @param $args array
	 */
	function getAdditionalFieldNames($hookName, $args) {
		assert(count($args) == 2);
		$additionalFields =& $args[1];
		assert(is_array($additionalFields));
		$additionalFields[] = $this->getDepositStatusSettingName();
	}

	/**
	 * @copydoc AcronPlugin::parseCronTab()
	 */
	function callbackParseCronTab($hookName, $args) {
		$taskFilesPath =& $args[0];
		$taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';
		return false;
	}

	/**
	 * Retrieve all unregistered articles.
	 * @param $context Context
	 * @return array
	 */
	function getUnregisteredArticles($context) {
		// // Retrieve all published submissions that have not yet been registered.
		// $publishedSubmissionDao = DAORegistry::getDAO('PublishedSubmissionDAO'); /* @var $publishedSubmissionDao PublishedSubmissionDAO */
		// $articles = $publishedSubmissionDao->getExportable(
		// 	$context->getId(),
		// 	null,
		// 	null,
		// 	null,
		// 	null,
		// 	$this->getDepositStatusSettingName(),
		// 	EXPORT_STATUS_NOT_DEPOSITED,
		// 	null
		// );
		// return $articles->toArray();
	}
	/**
	 * Check whether we are in test mode.
	 * @param $context Context
	 * @return boolean
	 */
	function isTestMode($context) {
		return ($this->getSetting($context->getId(), 'testMode') == 1);
	}

	/**
	 * Get deposit status setting name.
	 * @return string
	 */
	function getDepositStatusSettingName() {
		return $this->getPluginSettingsPrefix().'::status';
	}



	/**
	 * @copydoc PKPImportExportPlugin::usage
	 */
	function usage($scriptName) {
		echo __(
			'plugins.importexport.' . $this->getPluginSettingsPrefix() . '.cliUsage',
			array(
				'scriptName' => $scriptName,
				'pluginName' => $this->getName()
			)
		) . "\n";
	}

	/**
	 * @copydoc PKPImportExportPlugin::executeCLI()
	 */
	function executeCLI($scriptName, &$args) {
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);

		$command = array_shift($args);
		if (!in_array($command, array('export', 'register'))) {
			$this->usage($scriptName);
			return;
		}

		$outputFile = $command == 'export' ? array_shift($args) : null;
		$contextPath = array_shift($args);
		$objectType = array_shift($args);

		$contextDao = DAORegistry::getDAO('JournalDAO');
		$context = $contextDao->getByPath($contextPath);
		if (!$context) {
			if ($contextPath != '') {
				echo __('plugins.importexport.common.cliError') . "\n";
				echo __('plugins.importexport.common.error.unknownJournal', array('journalPath' => $contextPath)) . "\n\n";
			}
			$this->usage($scriptName);
			return;
		}

		if ($outputFile) {
			if ($this->isRelativePath($outputFile)) {
				$outputFile = PWD . '/' . $outputFile;
			}
			$outputDir = dirname($outputFile);
			if (!is_writable($outputDir) || (file_exists($outputFile) && !is_writable($outputFile))) {
				echo __('plugins.importexport.common.cliError') . "\n";
				echo __('plugins.importexport.common.export.error.outputFileNotWritable', array('param' => $outputFile)) . "\n\n";
				$this->usage($scriptName);
				return;
			}
		}

		switch ($objectType) {
			case 'articles':
				$objects = $this->getPublishedSubmissions($args, $context);
				$filter = $this->getSubmissionFilter();
				$objectsFileNamePart = 'articles';
				break;
			case 'issues':
				$objects = $this->getPublishedIssues($args, $context);
				$filter = $this->getIssueFilter();
				$objectsFileNamePart = 'issues';
				break;
			case 'galleys':
				$objects = $this->getArticleGalleys($args);
				$filter = $this->getRepresentationFilter();
				$objectsFileNamePart = 'galleys';
				break;
			default:
				$this->usage($scriptName);
				return;

		}
		if (empty($objects)) {
			echo __('plugins.importexport.common.cliError') . "\n";
			echo __('plugins.importexport.common.error.unknownObjects') . "\n\n";
			$this->usage($scriptName);
			return;
		}
		if (!$filter) {
			$this->usage($scriptName);
			return;
		}

		$this->executeCLICommand($scriptName, $command, $context, $outputFile, $objects, $filter, $objectsFileNamePart);
		return;
	}

	/**
	 * Execute the CLI command
	 * @param $scriptName The name of the command-line script (displayed as usage info)
	 * @param $command string (export or register)
	 * @param $context Context
	 * @param $outputFile string Path to the file where the exported XML should be saved
	 * @param $objects array Objects to be exported or registered
	 * @param $filter string Filter to use
	 * @param $objectsFileNamePart string Export file name part for this kind of objects
	 */
	function executeCLICommand($scriptName, $command, $context, $outputFile, $objects, $filter, $objectsFileNamePart) {
		$exportXml = $this->exportXML($objects, $filter, $context);
		if ($command == 'export' && $outputFile) file_put_contents($outputFile, $exportXml);

		if ($command == 'register') {
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			$exportFileName = $this->getExportFileName($this->getExportPath(), $objectsFileNamePart, $context, '.xml');
			$fileManager->writeFile($exportFileName, $exportXml);
			$result = $this->depositXML($objects, $context, $exportFileName);
			if ($result === true) {
				echo __('plugins.importexport.common.register.success') . "\n";
			} else {
				echo __('plugins.importexport.common.cliError') . "\n";
				if (is_array($result)) {
					foreach($result as $error) {
						assert(is_array($error) && count($error) >= 1);
						$errorMessage = __($error[0], array('param' => (isset($error[1]) ? $error[1] : null)));
						echo "*** $errorMessage\n";
					}
					echo "\n";
				} else {
					echo __('plugins.importexport.common.register.error.mdsError', array('param' => ' - ')) . "\n\n";
				}
				$this->usage($scriptName);
			}
			$fileManager->deleteByPath($exportFileName);
		}
	}

	/**
	 * Get published submissions from submission IDs.
	 * @param $submissionIds array
	 * @param $context Context
	 * @return array
	 */
	function getPublishedSubmissions($submissionIds, $context) {
		$submissions = array_map(function($submissionId) {
			return Services::get('submission')->get($submissionId);
		}, $submissionIds);
		return array_filter($submissions, function($submission) {
			return $submission->getData('status') === STATUS_PUBLISHED;
		});
	}

	/**
	 * Get published issues from issue IDs.
	 * @param $issueIds array
	 * @param $context Context
	 * @return array
	 */
	function getPublishedIssues($issueIds, $context) {
		$publishedIssues = array();
		$issueDao = DAORegistry::getDAO('IssueDAO');
		foreach ($issueIds as $issueId) {
			$publishedIssue = $issueDao->getById($issueId, $context->getId());
			if ($publishedIssue) $publishedIssues[] = $publishedIssue;
		}
		return $publishedIssues;
	}

	/**
	 * Get article galleys from gallley IDs.
	 * @param $galleyIds array
	 * @return array
	 */
	function getArticleGalleys($galleyIds) {
		$galleys = array();
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		foreach ($galleyIds as $galleyId) {
			$articleGalley = $articleGalleyDao->getById($galleyId);
			if ($articleGalley) $galleys[] = $articleGalley;
		}
		return $galleys;
	}

	/**
	 * Add a notification.
	 * @param $user User
	 * @param $message string An i18n key.
	 * @param $notificationType integer One of the NOTIFICATION_TYPE_* constants.
	 * @param $param string An additional parameter for the message.
	 */
	function _sendNotification($user, $message, $notificationType, $param = null) {
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
		$notificationManager->createTrivialNotification(
				$user->getId(),
				$notificationType,
				array('contents' => __($message, $params))
				);
	}

	/**
	 * Instantiate the export deployment.
	 * @param $context Context
	 * @return PKPImportExportDeployment
	 */
	function _instantiateExportDeployment($context) {
		$exportDeploymentClassName = $this->getExportDeploymentClassName();
		$this->import($exportDeploymentClassName);
		$exportDeployment = new $exportDeploymentClassName($context, $this);
		return $exportDeployment;
	}

	/**
	 * Instantiate the settings form.
	 * @param $context Context
	 * @return CrossRefSettingsForm
	 */
	function _instantiateSettingsForm($context) {
		$settingsFormClassName = $this->getSettingsFormClassName();
		$this->import('classes.form.' . $settingsFormClassName);
		$settingsForm = new $settingsFormClassName($this, $context->getId());
		return $settingsForm;
	}

}


