<?php

/**
 * @file controllers/grid/admin/journal/JournalGridHandler.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalGridHandler
 * @ingroup controllers_grid_admin_journal
 *
 * @brief Handle journal grid requests.
 */

import('lib.pkp.controllers.grid.admin.context.ContextGridHandler');

import('controllers.grid.admin.journal.JournalGridRow');
import('controllers.grid.admin.journal.form.JournalSiteSettingsForm');

class JournalGridHandler extends ContextGridHandler {
	/**
	 * Constructor
	 */
	function JournalGridHandler() {
		parent::ContextGridHandler();
		$this->addRoleAssignment(ROLE_ID_SITE_ADMIN, array(
			'showThumbnailUploadForm',
			'uploadFile', 'saveFile',
			'fetchFile'
		));
	}


	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @see PKPHandler::initialize()
	 */
	function initialize($request) {
		// Load user-related translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_ADMIN,
			LOCALE_COMPONENT_APP_MANAGER,
			LOCALE_COMPONENT_APP_COMMON
		);

		parent::initialize($request);

		// Basic grid configuration.
		$this->setTitle('journal.journals');
	}


	//
	// Implement methods from GridHandler.
	//
	/**
	 * @see GridHandler::getRowInstance()
	 * @return UserGridRow
	 */
	function getRowInstance() {
		return new JournalGridRow();
	}

	/**
	 * @see GridHandler::loadData()
	 * @param $request PKPRequest
	 * @return array Grid data.
	 */
	function loadData($request) {
		// Get all journals.
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getAll();

		return $journals->toAssociativeArray();
	}

	/**
	 * @see lib/pkp/classes/controllers/grid/GridHandler::setDataElementSequence()
	 */
	function setDataElementSequence($request, $rowId, &$journal, $newSequence) {
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		$journal->setSequence($newSequence);
		$journalDao->updateObject($journal);
	}


	//
	// Public grid actions.
	//
	/**
	 * Edit an existing journal.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editContext($args, $request) {

		// Identify the journal Id.
		$journalId = $request->getUserVar('rowId');

		// Form handling.
		$settingsForm = new JournalSiteSettingsForm(!isset($journalId) || empty($journalId) ? null : $journalId);
		$settingsForm->initData();
		$json = new JSONMessage(true, $settingsForm->fetch($args, $request));

		return $json->getString();
	}

	/**
	 * Update an existing journal.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateContext($args, $request) {
		// Identify the journal Id.
		$journalId = $request->getUserVar('contextId');

		// Form handling.
		$settingsForm = new JournalSiteSettingsForm($journalId);
		$settingsForm->readInputData();

		if ($settingsForm->validate()) {
			PluginRegistry::loadCategory('blocks');

			$settingsForm->execute($request);

			// Create the notification.
			$notificationMgr = new NotificationManager();
			$user = $request->getUser();
			$notificationMgr->createTrivialNotification($user->getId());

			return DAO::getDataChangedEvent($journalId);
		}

		$json = new JSONMessage(false);
		return $json->getString();
	}

	/**
	 * Delete a journal.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function deleteContext($args, $request) {
		// Identify the current context.
		$context = $request->getContext();

		// Identify the journal Id.
		$journalId = $request->getUserVar('rowId');
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journal = $journalDao->getById($journalId);

		if ($journal) {
			$journalDao->deleteById($journalId);

			// Delete journal file tree
			// FIXME move this somewhere better.
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager($journalId);
			$journalPath = Config::getVar('files', 'files_dir') . '/journals/' . $journalId;
			$fileManager->rmtree($journalPath);

			import('classes.file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$publicFileManager->rmtree($publicFileManager->getJournalFilesPath($journalId));

			return DAO::getDataChangedEvent($journalId);
		}

		$json = new JSONMessage(false);
		return $json->getString();
	}

	/**
	 * Show the upload thumbnail image form.
	 * @param $request Request
	 * @param $args array
	 * @return string JSON message
	 */
	function showThumbnailUploadForm($args, $request) {
		import('lib.pkp.controllers.tab.settings.appearance.form.NewContextImageFileForm');
		$fileUploadForm = new NewContextImageFileForm('journalThumbnail');
		$fileUploadForm->initData($request);

		$json = new JSONMessage(true, $fileUploadForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Upload a new file.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string
	 */
	function uploadFile($args, $request) {
		import('lib.pkp.controllers.tab.settings.appearance.form.NewContextImageFileForm');
		$fileUploadForm = new NewContextImageFileForm('journalThumbnail');
		$json = new JSONMessage();

		$temporaryFileId = $fileUploadForm->uploadFile($request);

		if ($temporaryFileId !== false) {
			$json->setAdditionalAttributes(array(
				'temporaryFileId' => $temporaryFileId
			));
		} else {
			$json->setStatus(false);
			$json->setContent(__('common.uploadFailed'));
		}

		return $json->getString();
	}

	/**
	 * Save an uploaded file.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string
	 */
	function saveFile($args, $request) {
		import('lib.pkp.controllers.tab.settings.appearance.form.NewContextImageFileForm');
		$fileUploadForm = new NewContextImageFileForm('journalThumbnail');
		$fileUploadForm->readInputData();

		if ($fileUploadForm->validate()) {
			if ($fileUploadForm->execute($request)) {
				// Generate a JSON message with an event
				return DAO::getDataChangedEvent('journalThumbnail');
			}
		}
		$json = new JSONMessage(false, __('common.invalidFileType'));
		return $json->getString();
	}

	/**
	 * Fetch a file that has been uploaded.
	 *
	 * @param $args array
	 * @param $request Request
	 * @return string
	 */
	function fetchFile($args, $request) {
		// Try to fetch the file.
		$journalId = $request->getUserVar('rowId');
		$settingsForm = new JournalSiteSettingsForm($journalId);
		$settingsForm->initData($request);

		$renderedElement = $settingsForm->renderFileView($request);

		$json = new JSONMessage();
		if ($renderedElement == false) {
			$json->setAdditionalAttributes(array('noData' => 'journalThumbnail'));
		} else {
			$json->setElementId('journalThumbnail');
			$json->setContent($renderedElement);
		}
		return $json->getString();
	}



	//
	// Private helper methods.
	//
	/**
	 * Get the "add context" locale key
	 * @return string
	 */
	protected function _getAddContextKey() {
		return 'admin.journals.create';
	}

	/**
	 * Get the context name locale key
	 * @return string
	 */
	protected function _getContextNameKey() {
		return 'manager.setup.journalTitle';
	}
}

?>
