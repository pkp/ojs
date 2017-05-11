<?php

/**
 * @file plugins/importexport/users/PKPUserImportExportPlugin.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UserImportExportPlugin
 * @ingroup plugins_importexport_users
 *
 * @brief User XML import/export plugin
 */

import('lib.pkp.classes.plugins.ImportExportPlugin');

abstract class PKPUserImportExportPlugin extends ImportExportPlugin {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @param $path string
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		$this->import('PKPUserImportExportDeployment');
		return $success;
	}

	/**
	 * @copydoc Plugin::getTemplatePath($inCore)
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'UserImportExportPlugin';
	}

	/**
	 * Get the display name.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.importexport.users.displayName');
	}

	/**
	 * Get the display description.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.importexport.users.description');
	}

	/**
	 * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix() {
		return 'users';
	}

	/**
	 * Display the plugin.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function display($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();

		parent::display($args, $request);

		$templateMgr->assign('plugin', $this);

		switch (array_shift($args)) {
			case 'index':
			case '':
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
				break;
			case 'uploadImportXML':
				$user = $request->getUser();
				import('lib.pkp.classes.file.TemporaryFileManager');
				$temporaryFileManager = new TemporaryFileManager();
				$temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());
				if ($temporaryFile) {
					$json = new JSONMessage(true);
					$json->setAdditionalAttributes(array(
						'temporaryFileId' => $temporaryFile->getId()
					));
				} else {
					$json = new JSONMessage(false, __('common.uploadFailed'));
				}

				return $json->getString();
			case 'importBounce':
				$json = new JSONMessage(true);
				$json->setEvent('addTab', array(
					'title' => __('plugins.importexport.users.results'),
					'url' => $request->url(null, null, null, array('plugin', $this->getName(), 'import'), array('temporaryFileId' => $request->getUserVar('temporaryFileId'))),
				));
				return $json->getString();
			case 'import':
				$temporaryFileId = $request->getUserVar('temporaryFileId');
				$temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO');
				$user = $request->getUser();
				$temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());
				if (!$temporaryFile) {
					$json = new JSONMessage(true, __('plugins.importexport.users.uploadFile'));
					return $json->getString();
				}
				$temporaryFilePath = $temporaryFile->getFilePath();
				libxml_use_internal_errors(true);
				$users = $this->importUsers(file_get_contents($temporaryFilePath), $context, $user);
				$validationErrors = array_filter(libxml_get_errors(), create_function('$a', 'return $a->level == LIBXML_ERR_ERROR ||  $a->level == LIBXML_ERR_FATAL;'));
				$templateMgr->assign('validationErrors', $validationErrors);
				libxml_clear_errors();
				$templateMgr->assign('users', $users);
				$json = new JSONMessage(true, $templateMgr->fetch($this->getTemplatePath() . 'results.tpl'));
				return $json->getString();
			case 'export':
				$exportXml = $this->exportUsers(
					(array) $request->getUserVar('selectedUsers'),
					$request->getContext(),
					$request->getUser()
				);
				import('lib.pkp.classes.file.FileManager');
				$fileManager = new FileManager();
				$exportFileName = $this->getExportFileName($this->getExportPath(), 'users', $context, '.xml');
				$fileManager->writeFile($exportFileName, $exportXml);
				$fileManager->downloadFile($exportFileName);
				$fileManager->deleteFile($exportFileName);
				break;
			case 'exportAllUsers':
				$exportXml = $this->exportAllUsers(
					$request->getContext(),
					$request->getUser()
				);
				import('lib.pkp.classes.file.TemporaryFileManager');
				$fileManager = new TemporaryFileManager();
				$exportFileName = $this->getExportFileName($this->getExportPath(), 'users', $context, '.xml');
				$fileManager->writeFile($exportFileName, $exportXml);
				$fileManager->downloadFile($exportFileName);
				$fileManager->deleteFile($exportFileName);
				break;
			default:
				$dispatcher = $request->getDispatcher();
				$dispatcher->handle404();
		}
	}

	/**
	 * Get the XML for all of users.
	 * @param $context Context
	 * @param $user User
	 * @return string XML contents representing the supplied user IDs.
	 */
	function exportAllUsers($context, $user) {
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$users = $userGroupDao->getUsersByContextId($context->getId());
		return $this->exportUsers($users->toArray(), $context, $user);
	}

	/**
	 * Get the XML for a set of users.
	 * @param $ids array mixed Array of users or user IDs
	 * @param $context Context
	 * @param $user User
	 * @return string XML contents representing the supplied user IDs.
	 */
	function exportUsers($ids, $context, $user) {
		$userDao = DAORegistry::getDAO('UserDAO');
		$xml = '';
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$userExportFilters = $filterDao->getObjectsByGroup('user=>user-xml');
		assert(count($userExportFilters) == 1); // Assert only a single serialization filter
		$exportFilter = array_shift($userExportFilters);
		$exportFilter->setDeployment(new PKPUserImportExportDeployment($context, $user));
		$users = array();
		foreach ($ids as $id) {
			if (is_a($id, 'User')) {
				$users[] = $id;
			} else {
				$user = $userDao->getById($id, $context->getId());
				if ($user) $users[] = $user;
			}
		}
		$userXml = $exportFilter->execute($users);
		if ($userXml) $xml = $userXml->saveXml();
		else fatalError('Could not convert users.');
		return $xml;
	}

	/**
	 * Get the XML for a set of users.
	 * @param $importXml string XML contents to import
	 * @param $context Context
	 * @param $user User
	 * @return array Set of imported users
	 */
	function importUsers($importXml, $context, $user) {
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$userImportFilters = $filterDao->getObjectsByGroup('user-xml=>user');
		assert(count($userImportFilters) == 1); // Assert only a single unserialization filter
		$importFilter = array_shift($userImportFilters);
		$importFilter->setDeployment(new PKPUserImportExportDeployment($context, $user));

		return $importFilter->execute($importXml);
	}
}

?>
