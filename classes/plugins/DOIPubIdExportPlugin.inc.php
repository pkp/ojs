<?php

/**
 * @file classes/plugins/DOIPubIdExportPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOIPubIdExportPlugin
 * @ingroup plugins
 *
 * @brief Basis class for DOI XML metadata export plugins
 */

import('lib.pkp.classes.plugins.ImportExportPlugin');

// The status of the DOI.
define('DOI_EXPORT_STATUS_ANY', '');
define('DOI_EXPORT_STATUS_NOT_DEPOSITED', 'notDeposited');
define('DOI_EXPORT_STATUS_MARKEDREGISTERED', 'markedRegistered');
define('DOI_EXPORT_STATUS_REGISTERED', 'registered');

// The status of the DOI.
define('DOI_EXPORT_ACTION_EXPORT', 'export');
define('DOI_EXPORT_ACTION_MARKREGISTERED', 'markRegistered');
define('DOI_EXPORT_ACTION_DEPOSIT', 'deposit');
define('DOI_EXPORT_ACTION_CHECKSTATUS', 'checkStatus');

// Configuration errors.
define('DOI_EXPORT_CONFIG_ERROR_DOIPREFIX', 0x01);
define('DOI_EXPORT_CONFIG_ERROR_SETTINGS', 0x02);

// The name of the setting used to save the registered DOI.
define('DOI_EXPORT_REGISTERED_DOI', 'registeredDoi');

abstract class DOIPubIdExportPlugin extends ImportExportPlugin {
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
	 * Constructor
	 */
	function DOIPubIdExportPlugin() {
		parent::ImportExportPlugin();
	}

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			HookRegistry::register('AcronPlugin::parseCronTab', array($this, 'callbackParseCronTab'));
		}
		$this->addLocaleData();
		return $success;
	}

	/**
	 * @copydoc Plugin::getTemplatePath($inCore)
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
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
				// 1) missing DOI prefix
				$doiPrefix = $exportArticles = $exportIssues = null;
				$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
				if (isset($pubIdPlugins['doipubidplugin'])) {
					$doiPlugin = $pubIdPlugins['doipubidplugin'];
					$doiPrefix = $doiPlugin->getSetting($context->getId(), $doiPlugin->getPrefixFieldName());
					$exportArticles = $doiPlugin->getSetting($context->getId(), 'enableSubmissionDoi');
					$exportIssues = $doiPlugin->getSetting($context->getId(), 'enableIssueDoi');
					$exportRepresentations = $doiPlugin->getSetting($context->getId(), 'enableRepresentationDoi');
				}
				if (empty($doiPrefix)) {
					$configurationErrors[] = DOI_EXPORT_CONFIG_ERROR_DOIPREFIX;
				}

				// 2) missing plugin settings
				$form = $this->_instantiateSettingsForm($context);
				foreach($form->getFormFields() as $fieldName => $fieldType) {
					if ($form->isOptional($fieldName)) continue;
					$pluginSetting = $this->getSetting($context->getId(), $fieldName);
					if (empty($pluginSetting)) {
						$configurationErrors[] = DOI_EXPORT_CONFIG_ERROR_SETTINGS;
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
				$templateMgr->assign('plugin', $this);
				$templateMgr->assign('linkActions', $linkActions);
				$templateMgr->assign('configurationErrors', $configurationErrors);
				$templateMgr->assign('exportArticles', $exportArticles);
				$templateMgr->assign('exportIssues', $exportIssues);
				$templateMgr->assign('exportRepresentations', $exportRepresentations);
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
				break;
			case 'exportSubmissions':
			case 'exportIssues':
				$selectedSubmissions = (array) $request->getUserVar('selectedSubmissions');
				$selectedIssues = (array) $request->getUserVar('selectedIssues');

				if (empty($selectedSubmissions) && empty($selectedIssues)) {
					echo __('plugins.importexport.common.error.noObjectsSelected');
					break;
				}
				if (!empty($selectedSubmissions)) {
					$objects = $this->_getPublishedArticles($selectedSubmissions, $context);
					$filter = $this->getSubmissionFilter();
					$tab = 'exportSubmissions-tab';
				} elseif (!empty($selectedIssues)) {
					$objects = $this->_getPublishedIssues($selectedIssues, $context);
					$filter = $this->getIssueFilter();
					$tab = 'exportIssues-tab';
				}

				// Execute export action
				$this->executeExportAction($request, $objects, $filter, $tab);
		}
	}

	/**
	 * Execute export action.
	 * @param $request Request
	 * @param $objects array Array of objects to be exported
	 * @param $filter string Filter to use
	 * @param $tab string Tab to return to
	 */
	function executeExportAction($request, $objects, $filter, $tab) {
		$context = $request->getContext();
		$path = array('plugin', $this->getName());

		if ($request->getUserVar(DOI_EXPORT_ACTION_EXPORT) || $request->getUserVar(DOI_EXPORT_ACTION_DEPOSIT)) {
			// Get the XML
			$exportXml = $this->exportXML($objects, $filter, $context);

			if ($request->getUserVar(DOI_EXPORT_ACTION_EXPORT)) {
				header('Content-type: application/xml');
				echo $exportXml;
			} else { //deposit
				// Write the XML to a file.
				$exportFileName = $this->getExportPath() . date('Ymd-His') . '.xml';
				file_put_contents($exportFileName, $exportXml);
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
				$this->cleanTmpfile($exportFileName);
				// redirect back to the right tab
				$request->redirect(null, null, null, $path, null, $tab);
			}
		} elseif ($request->getUserVar(DOI_EXPORT_ACTION_MARKREGISTERED)) {
			$this->markRegistered($context, $objects);
			// redirect back to the right tab
			$request->redirect(null, null, null, $path, null, $tab);
		} elseif ($request->getUserVar(DOI_EXPORT_ACTION_CHECKSTATUS)) {
			$this->checkStatus($objects, $context);
			// redirect back to the right tab
			$request->redirect(null, null, null, $path, null, $tab);
		} else {
			$dispatcher = $request->getDispatcher();
			$dispatcher->handle404();
		}

	}

	/**
	 * Get the submission filter.
	 */
	abstract function getSubmissionFilter();

	/**
	 * Get the issue filter.
	 */
	abstract function getIssueFilter();

	/**
	 * Get status names for the filter search option.
	 * @return array (string status => string text)
	 */
	function getStatusNames() {
		return array(
			DOI_EXPORT_STATUS_ANY => __('plugins.importexport.common.status.any'),
			DOI_EXPORT_STATUS_NOT_DEPOSITED => __('plugins.importexport.common.status.notDeposited'),
			DOI_EXPORT_STATUS_MARKEDREGISTERED => __('plugins.importexport.common.status.markedRegistered'),
			DOI_EXPORT_STATUS_REGISTERED => __('plugins.importexport.common.status.registered'),
		);
	}

	/**
	 * Get status actions for the display to the user,
	 * i.e. links to an agency with more information about the DOI status.
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
		return array(
			DOI_EXPORT_ACTION_DEPOSIT,
			DOI_EXPORT_ACTION_CHECKSTATUS,
			DOI_EXPORT_ACTION_EXPORT,
			DOI_EXPORT_ACTION_MARKREGISTERED,
		);
	}

	/**
	 * Get action names.
	 * @return array (string action => string text)
	 */
	function getExportActionNames() {
		return array(
			DOI_EXPORT_ACTION_DEPOSIT => __('plugins.importexport.common.action.register'),
			DOI_EXPORT_ACTION_CHECKSTATUS => __('plugins.importexport.common.action.checkStatus'),
			DOI_EXPORT_ACTION_EXPORT => __('plugins.importexport.common.action.export'),
			DOI_EXPORT_ACTION_MARKREGISTERED => __('plugins.importexport.common.action.markRegistered'),
		);
	}

	/**
	 * Get the plugin ID used as plugin settings prefix.
	 * @return string
	 */
	abstract function getPluginSettingsPreffix();

	/**
	 * Return the class name of the plugin's settings form.
	 * @return string
	 */
	abstract function getSettingsFormClassName();

	/**
	 * Return the name of the plugin's deployment class.
	 * @return string
	 */
	abstract function getExportDeploymentClassName();

	/**
	 * Get pub ID type
	 * @return string
	 */
	function getPubIdType() {
		return 'doi';
	}

	/**
	 * Get pub ID display type
	 * @return string
	 */
	function getPubIdDisplayType() {
		return 'DOI';
	}

	/**
	 * Get the XML for selected objects.
	 * @param $objects array Array of published articles, issues or galleys
	 * @param $filter string
	 * @param $context Context
	 * @return string XML contents representing the supplied DOIs.
	 */
	function exportXML($objects, $filter, $context) {
		$xml = '';
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$exportFilters = $filterDao->getObjectsByGroup($filter);
		assert(count($exportFilters) == 1); // Assert only a single serialization filter
		$exportFilter = array_shift($exportFilters);
		$exportDeployment = $this->_instantiateExportDeployment($context);
		$exportFilter->setDeployment($exportDeployment);
		libxml_use_internal_errors(true);
		$exportXml = $exportFilter->execute($objects, true);
		$xml = $exportXml->saveXml();
		$errors = array_filter(libxml_get_errors(), create_function('$a', 'return $a->level == LIBXML_ERR_ERROR ||  $a->level == LIBXML_ERR_FATAL;'));
		if (!empty($errors)) {
			$this->displayXMLValidationErrors($errors, $xml);
			fatalError(__('plugins.importexport.common.error.validation'));
		}
		return $xml;
	}

	/**
	 * Deposit XML i.e. publication objects DOIs.
	 * This must be implemented in the subclasses, if the action is supported.
	 * @param $objects array Array of published articles, issues or galleys
	 * @param $context Context
	 * @param $filename Export XML filename
	 * @return boolean Weather the DOI has been registered/found
	 */
	abstract function depositXML($objects, $context, $filename);

	/**
	 * Check statuses for selected publication objects.
	 * @param $objects array Array of published articles, issues or galleys
	 * @param $context Context
	 */
	function checkStatus($objects, $context) {
		foreach ($objects as $object) {
			$this->updateDepositStatus($context, $object);
		}
	}

	/**
	 * Update deposit status.
	 * The function must be implemented in the subclasses, if the action is supported.
	 * @param $context Context
	 * @param $object The object getting deposited
	 */
	function updateDepositStatus($context, $object) { }

	/**
	 * Display XML validation errors.
	 * @param $errors array
	 * @param $xml string
	 */
	function displayXMLValidationErrors($errors, $xml) {
		echo '<h2>Validation errors:</h2>';

		foreach ($errors as $error) {
			switch ($error->level) {
				case LIBXML_ERR_ERROR:
				case LIBXML_ERR_FATAL:
					echo '<p>' .trim($error->message) .'</p>';
			}
		}
		libxml_clear_errors();
		echo '<h3>Invalid XML:</h3>';
		echo '<p><pre>' .htmlspecialchars($xml) .'</pre></p>';
	}

	/**
	 * Return the plugin export directory.
	 *
	 * This will create the directory if it doesn't exist yet.
	 *
	 * @return string|array The export directory name or an array with
	 *  errors if something went wrong.
	 */
	function getExportPath() {
		$exportPath = Config::getVar('files', 'files_dir') . '/' . $this->getPluginSettingsPreffix();
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
	 * Remove the given temporary file.
	 * @param $tempfile string
	 */
	function cleanTmpfile($tempfile) {
		if (file_exists($tempfile)) {
			unlink($tempfile);
		}
	}

	/**
	 * Mark selected submissions or issues as registered.
	 * @param $context Context
	 * @param $objects array Array of published articles, issues or galleys
	 */
	function markRegistered($context, $objects) {
		foreach ($objects as $object) {
			$object->setData($this->getDepositStatusSettingName(), DOI_EXPORT_STATUS_MARKEDREGISTERED);
			$this->saveRegisteredDoi($context, $object);
		}
	}

	/**
	 * Saving object's DOI to the object's
	 * "registeredDoi" setting.
	 * We prefix the setting with the plugin's
	 * id so that we do not get name clashes
	 * when several DOI registration plug-ins
	 * are active at the same time.
	 * @param $context Context
	 * @param $object Issue|PublishedArticle|ArticleGalley
	 * @param $testPrefix string
	 */
	function saveRegisteredDoi($context, $object, $testPrefix = '10.1234') {
		$registeredDoi = $object->getStoredPubId('doi');
		assert(!empty($registeredDoi));
		if ($this->isTestMode($context)) {
			$registeredDoi = PKPString::regexp_replace('#^[^/]+/#', $testPrefix . '/', $registeredDoi);
		}
		$object->setData($this->getPluginSettingsPreffix() . '::' . DOI_EXPORT_REGISTERED_DOI, $registeredDoi);
		$this->updateObject($object);
	}

	/**
	 * Update the given object.
	 * @param $object Issue|PublishedArticle|ArticleGAlley
	 */
	function updateObject($object) {
		// Get the dao name and update method for the given object.
		list($daoName, $daoMethod) = $this->getDaoMethod($object);
		// Register a hook for the required additional
		// object fields. We do this on a temporary
		// basis as the hook adds a performance overhead
		// and the field will "stealthily" survive even
		// when the DAO does not know about it.
		$this->registerDaoHook($daoName);
		$dao = DAORegistry::getDAO($daoName);
		$dao->$daoMethod($object);
	}

	/**
	 * Identify the dao name and update method for the given object.
	 * @param $object Issue|PublishedArticle|ArticleGalley
	 * @return array (DAO, Method)
	 */
	function getDaoMethod($object) {
		$configurations = array(
				'Issue' => array('IssueDAO', 'updateObject'),
				'Article' => array('ArticleDAO', 'updateObject'),
				'ArticleGalley' => array('ArticleGalleyDAO', 'updateObject'),
		);
		$foundConfig = false;
		foreach($configurations as $objectType => $configuration) {
			if (is_a($object, $objectType)) {
				$foundConfig = true;
				break;
			}
		}
		assert($foundConfig);
		return $configuration;
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
		$additionalFields =& $args[1];
		assert(is_array($additionalFields));
		$additionalFields[] = $this->getPluginSettingsPreffix() . '::' . DOI_EXPORT_REGISTERED_DOI;
		$additionalFields[] = $this->getDepositStatusSettingName();
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
		// Retrieve all published articles that have not yet been registered.
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO'); /* @var $publishedArticleDao PublishedArticleDAO */
		$articles = $publishedArticleDao->getByPubIdType(
			$this->getPubIdType(),
			$context?$context->getId():null,
			null,
			null,
			null,
			$this->getPluginSettingsPreffix(). '::' . DOI_EXPORT_REGISTERED_DOI,
			null,
			null
		);
		return $articles->toArray();
	}

	/**
	 * Retrieve all unregistered issues.
	 * @param $context Context
	 * @return array
	 */
	function getUnregisteredIssues($context) {
		// Retrieve all issues that have not yet been registered.
		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		$issuesFactory = $issueDao->getByPubIdType(
			$this->getPubIdType(),
			$context?$context->getId():null,
			$this->getPluginSettingsPreffix(). '::' . DOI_EXPORT_REGISTERED_DOI,
			null,
			null
		);
		$issues = $issuesFactory->toArray();
		// Cache issues.
		$cache = $this->getCache();
		foreach ($issues as $issue) {
			$cache->add($issue, null);
			unset($issue);
		}
		return $issues;
	}

	/**
	 * Get deposit status setting name.
	 * @return string
	 */
	function getDepositStatusSettingName() {
		return $this->getPluginSettingsPreffix().'::status';
	}

	/**
	 * Get the locale key used in the notification for
	 * the successful deposit.
	 */
	function getDepositSuccessNotificationMessageKey() {
		return 'plugins.importexport.common.register.success';
	}



	/**
	 * @copydoc PKPImportExportPlugin::usage
	 */
	function usage($scriptName) {
		fatalError('Not implemented');
	}

	/**
	 * @copydoc PKPImportExportPlugin::executeCLI()
	 */
	function executeCLI($scriptName, &$args) {
		fatalError('Not implemented');
	}



	/**
	 * Get published articles from submission IDs.
	 * @param $submissionIds array
	 * @param $context Context
	 * @return array
	 */
	function _getPublishedArticles($submissionIds, $context) {
		$publishedArticles = array();
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		foreach ($submissionIds as $submissionId) {
			$publishedArticle = $publishedArticleDao->getPublishedArticleByArticleId($submissionId, $context->getId());
			if ($publishedArticle) $publishedArticles[] = $publishedArticle;
		}
		return $publishedArticles;
	}

	/**
	 * Get published issues from issue IDs.
	 * @param $issueIds array
	 * @param $context Context
	 * @return array
	 */
	function _getPublishedIssues($issueIds, $context) {
		$publishedIssues = array();
		$issueDao = DAORegistry::getDAO('IssueDAO');
		foreach ($issueIds as $issueId) {
			$publishedIssue = $issueDao->getById($issueId, $context->getId());
			if ($publishedIssue) $publishedIssues[] = $publishedIssue;
		}
		return $publishedIssues;
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


}

?>
