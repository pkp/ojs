<?php

/**
 * @file classes/plugins/PubObjectsExportPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
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
	 * Constructor
	 */
	function PubObjectsExportPlugin() {
		parent::ImportExportPlugin();
	}

	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
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
		}
		return parent::manage($args, $request);
	}

	/**
	 * @copydoc Plugin::getTemplatePath($inCore)
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
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
				));
				break;
			case 'exportSubmissions':
			case 'exportIssues':
			case 'exportRepresentations':
				$selectedSubmissions = (array) $request->getUserVar('selectedSubmissions');
				$selectedIssues = (array) $request->getUserVar('selectedIssues');
				$selectedRepresentations = (array) $request->getUserVar('selectedRepresentations');
				$tab = (string) $request->getUserVar('tab');

				if (empty($selectedSubmissions) && empty($selectedIssues) && empty($selectedRepresentations)) {
					fatalError(__('plugins.importexport.common.error.noObjectsSelected'));
				}
				if (!empty($selectedSubmissions)) {
					$objects = $this->_getPublishedArticles($selectedSubmissions, $context);
					$filter = $this->getSubmissionFilter();
					$objectsFileNamePart = 'articles';
				} elseif (!empty($selectedIssues)) {
					$objects = $this->_getPublishedIssues($selectedIssues, $context);
					$filter = $this->getIssueFilter();
					$objectsFileNamePart = 'issues';
				} elseif (!empty($selectedRepresentations)) {
					$objects = $this->_getArticleGalleys($selectedRepresentations, $context);
					$filter = $this->getRepresentationFilter();
					$objectsFileNamePart = 'galleys';
				}

				// Execute export action
				$this->executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart);
		}
	}

	/**
	 * Execute export action.
	 * @param $request Request
	 * @param $objects array Array of objects to be exported
	 * @param $filter string Filter to use
	 * @param $tab string Tab to return to
	 * @param $objectsFileNamePart string Export file name part for this kind of objects
	 */
	function executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart) {
		$context = $request->getContext();
		$path = array('plugin', $this->getName());
		if ($request->getUserVar(EXPORT_ACTION_EXPORT)) {
			assert($filter != null);
			// Get the XML
			$exportXml = $this->exportXML($objects, $filter, $context);
			header('Content-type: application/xml');
			echo $exportXml;
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
	 * Get the plugin ID used as plugin settings prefix.
	 * @return string
	 */
	abstract function getPluginSettingsPrefix();

	/**
	 * Return the name of the plugin's deployment class.
	 * @return string
	 */
	abstract function getExportDeploymentClassName();

	/**
	 * Get the XML for selected objects.
	 * @param $objects mixed Array of or single published article, issue or galley
	 * @param $filter string
	 * @param $context Context
	 * @return string XML document.
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
	 * Display XML validation errors.
	 * @param $errors array
	 * @param $xml string
	 */
	function displayXMLValidationErrors($errors, $xml) {
		echo '<h2>' . __('plugins.importexport.common.validationErrors') .'</h2>';

		foreach ($errors as $error) {
			switch ($error->level) {
				case LIBXML_ERR_ERROR:
				case LIBXML_ERR_FATAL:
					echo '<p>' .trim($error->message) .'</p>';
			}
		}
		libxml_clear_errors();
		echo '<h3>' . __('plugins.importexport.common.invalidXML') .'</h3>';
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
		$exportPath = Config::getVar('files', 'files_dir') . '/' . $this->getPluginSettingsPrefix();
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
	 * Return the whole export file name.
	 * @param $objectsFileNamePart string Part different for each object type.
	 * @param $context Context
	 * @return string
	 */
	function getExportFileName($objectsFileNamePart, $context) {
		return $this->getExportPath() . date('Ymd-His') .'-' . $objectsFileNamePart .'-' . $context->getId() . '.xml';
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
			$object->setData($this->getDepositStatusSettingName(), EXPORT_STATUS_MARKEDREGISTERED);
			$this->updateObject($object);
		}
	}

	/**
	 * Update the given object.
	 * @param $object Issue|PublishedArticle|ArticleGAlley
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
	 * Get article galleys from gallley IDs.
	 * @param $galleyIds array
	 * @param $context Context
	 * @return array
	 */
	function _getArticleGalleys($galleyIds, $context) {
		$galleys = array();
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		foreach ($galleyIds as $galleyId) {
			$articleGalley = $articleGalleyDao->getById($galleyId, null, $context->getId());
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

}

?>
