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
	 * Get status names for the filter search option.
	 * @param $detailedView boolean Detailed status view e.g. with a link to an agency page,
	 * that contains more information about the DOI status
	 * @return array (string status => string text)
	 */
	function getStatusNames() {
		return array(
			DOI_EXPORT_STATUS_ANY => __('plugins.importexport.common.status.any'),
			DOI_EXPORT_STATUS_NOT_DEPOSITED => __('plugins.importexport.common.status.notDeposited'),
			DOI_EXPORT_STATUS_MARKEDREGISTERED => __('plugins.importexport.common.status.markedRegistered'),
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
	 * Get action names for the action drop-down.
	 * @return array (string action => string text)
	 */
	function getActionNames() {
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
	 * @param $request Request
	 * @param $objects array Array of Issue|PublishedArticle
	 */
	function markRegistered($request, $objects) {
		foreach ($objects as $object) {
			$object->setData($this->getDepositStatusSettingName(), DOI_EXPORT_STATUS_MARKEDREGISTERED);
			$this->saveRegisteredDoi($request, $object);
		}
	}

	/**
	 * Saving object's DOI to the object's
	 * "registeredDoi" setting.
	 * We prefix the setting with the plugin's
	 * id so that we do not get name clashes
	 * when several DOI registration plug-ins
	 * are active at the same time.
	 * @param $request Request
	 * @param $object Issue|PublishedArticle
	 * @param $testPrefix string
	 */
	function saveRegisteredDoi($request, $object, $testPrefix = '10.1234') {
		$registeredDoi = $object->getStoredPubId('doi');
		assert(!empty($registeredDoi));
		if ($this->isTestMode($request)) {
			$registeredDoi = String::regexp_replace('#^[^/]+/#', $testPrefix . '/', $registeredDoi);
		}
		$object->setData($this->getPluginSettingsPreffix() . '::' . DOI_EXPORT_REGISTERED_DOI, $registeredDoi);
		$this->updateObject($object);
	}

	/**
	 * Update the given object.
	 * @param $object Issue|PublishedArticle
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
	 * @param $object Issue|PublishedArticle
	 * @return array (DAO, Method)
	 */
	function getDaoMethod($object) {
		$configurations = array(
				'Issue' => array('IssueDAO', 'updateObject'),
				'Article' => array('ArticleDAO', 'updateObject'),
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
	}

	/**
	 * Check whether we are in test mode.
	 * @param $request Request
	 * @return boolean
	 */
	function isTestMode($request) {
		return ($request->getUserVar('testMode') == '1');
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
	 * @param $context Journal
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
	 * @param $context Journal
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

?>
