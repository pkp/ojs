<?php

/**
 * @file controllers/grid/admin/journal/JournalGridHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalGridHandler
 * @ingroup controllers_grid_admin_journal
 *
 * @brief Handle journal grid requests.
 */

import('lib.pkp.controllers.grid.admin.context.ContextGridHandler');
import('controllers.grid.admin.journal.form.JournalSiteSettingsForm');

class JournalGridHandler extends ContextGridHandler {

	//
	// Public grid actions.
	//
	/**
	 * Edit an existing journal.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editContext($args, $request) {
		// Get the journal ID. (Not the same as the context!)
		$journalId = $request->getUserVar('rowId');

		// Form handling.
		$settingsForm = new JournalSiteSettingsForm(!isset($journalId) || empty($journalId) ? null : $journalId);
		$settingsForm->initData();
		return new JSONMessage(true, $settingsForm->fetch($request));
	}

	/**
	 * Update an existing journal.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateContext($args, $request) {
		// Identify the context Id.
		$contextId = $request->getUserVar('contextId');

		// Form handling.
		$settingsForm = new JournalSiteSettingsForm($contextId);
		$settingsForm->readInputData();

		if (!$settingsForm->validate()) {
			return new JSONMessage(false);
		}

		PluginRegistry::loadCategory('blocks');

		// The context settings form will return a context path in two cases:
		// 1 - if a new context was created;
		// 2 - if a press path of an existing context was edited.
		$newContextPath = $settingsForm->execute();

		// Create the notification.
		$notificationMgr = new NotificationManager();
		$user = $request->getUser();
		$notificationMgr->createTrivialNotification($user->getId());

		// Check for the two cases above.
		if ($newContextPath) {
			$context = $request->getContext();

			if (is_null($contextId)) {
				// CASE 1: new press created.
				// Create notification related to payment method configuration.
				$contextDao = Application::getContextDAO();
				$newContext = $contextDao->getByPath($newContextPath);
				$notificationMgr->createNotification($request, null, NOTIFICATION_TYPE_CONFIGURE_PAYMENT_METHOD,
					$newContext->getId(), ASSOC_TYPE_JOURNAL, $newContext->getId(), NOTIFICATION_LEVEL_NORMAL);

				// redirect and set the parameter to open the press
				// setting wizard modal after redirection.
				return $this->_getRedirectEvent($request, $newContextPath, true);
			} else {
				// CASE 2: check if user is in the context of
				// the press being edited.
				if ($context && $context->getId() == $contextId) {
					return $this->_getRedirectEvent($request, $newContextPath, false);
				}
			}
		}
		return DAO::getDataChangedEvent($contextId);
	}

	/**
	 * Delete a journal.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteContext($args, $request) {
		// Identify the journal Id.
		$journalId = $request->getUserVar('rowId');
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journal = $journalDao->getById($journalId);

		if ($journal && $request->checkCSRF()) {
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

		return new JSONMessage(false);
	}
}

?>
