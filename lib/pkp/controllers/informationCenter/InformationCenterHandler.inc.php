<?php

/**
 * @file controllers/informationCenter/InformationCenterHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InformationCenterHandler
 * @ingroup controllers_informationCenter
 *
 * @brief Parent class for file/submission information center handlers.
 */

import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');
import('classes.log.SubmissionEventLogEntry');

abstract class InformationCenterHandler extends Handler {
	/** @var Submission */
	var $_submission;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT),
			array(
				'viewInformationCenter',
				'viewHistory',
				'viewNotes', 'listNotes', 'saveNote', 'deleteNote',
			)
		);
	}


	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		// Require a submission
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments, 'submissionId'));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Fetch and store away objects
	 * @param $request PKPRequest
	 * @param $args array optional
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Fetch the submission and file to display information about
		$this->_submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
	}


	//
	// Public operations
	//
	/**
	 * Display the main information center modal.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function viewInformationCenter($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		return $templateMgr->fetchJson('controllers/informationCenter/informationCenter.tpl');
	}

	/**
	 * View a list of notes posted on the item.
	 * Subclasses must implement.
	 */
	abstract function viewNotes($args, $request);

	/**
	 * Save a note.
	 * Subclasses must implement.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	abstract function saveNote($args, $request);

	/**
	 * Display the list of existing notes.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function listNotes($args, $request) {
		$this->setupTemplate($request);

		$templateMgr = TemplateManager::getManager($request);
		$noteDao = DAORegistry::getDAO('NoteDAO');
		$user = $request->getUser();
		$templateMgr->assign(array(
			'notes' => $noteDao->getByAssoc($this->_getAssocType(), $this->_getAssocId()),
			'currentUserId' => $user->getId(),
			'notesDeletable' => true,
			'notesListId' => 'notesList'
		));
		$json = new JSONMessage(true, $templateMgr->fetch('controllers/informationCenter/notesList.tpl'));
		$json->setEvent('dataChanged');
		return $json;
	}

	/**
	 * Delete a note.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteNote($args, $request) {
		$this->setupTemplate($request);

		$noteId = (int) $request->getUserVar('noteId');
		$noteDao = DAORegistry::getDAO('NoteDAO');
		$note = $noteDao->getById($noteId);
		if (!$request->checkCSRF() || !$note || $note->getAssocType() != $this->_getAssocType() || $note->getAssocId() != $this->_getAssocId()) fatalError('Invalid note!');
		$noteDao->deleteById($noteId);

		$user = $request->getUser();
		NotificationManager::createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.removedNote')));

		return new JSONMessage(true);
	}

	/**
	 * Get an array representing link parameters that subclasses
	 * need to have passed to their various handlers (i.e. submission ID
	 * to the delete note handler).
	 * @return array
	 */
	function _getLinkParams() {
		return array(
			'submissionId' => $this->_submission->getId(),
		);
	}

	function setupTemplate($request) {
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_PKP_SUBMISSION);

		$linkParams = $this->_getLinkParams();
		$templateMgr = TemplateManager::getManager($request);

		// Preselect tab from keywords 'notes', 'notify', 'history'
		switch ($request->getUserVar('tab')) {
			case 'history':
				$templateMgr->assign('selectedTabIndex', 2);
				break;
			case 'notify':
				$userId = (int) $request->getUserVar('userId');
				if ($userId) {
					$linkParams['userId'] = $userId; // user validated in Listbuilder.
				}
				$templateMgr->assign('selectedTabIndex', 1);
				break;
			// notes is default
			default:
				$templateMgr->assign('selectedTabIndex', 0);
				break;
		}

		$templateMgr->assign('linkParams', $linkParams);
		parent::setupTemplate($request);
	}

	/**
	 * Get the association ID for this information center view
	 * @return int
	 */
	abstract function _getAssocId();

	/**
	 * Get the association type for this information center view
	 * @return int
	 */
	abstract function _getAssocType();
}

?>
