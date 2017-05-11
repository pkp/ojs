<?php

/**
 * @file controllers/informationCenter/SubmissionInformationCenterHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionInformationCenterHandler
 * @ingroup controllers_informationCenter
 *
 * @brief Handle requests to view the information center for a submission.
 */

import('lib.pkp.controllers.informationCenter.InformationCenterHandler');
import('lib.pkp.classes.core.JSONMessage');
import('classes.log.SubmissionEventLogEntry');

class SubmissionInformationCenterHandler extends InformationCenterHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Display the main information center modal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function viewInformationCenter($args, $request) {
		// Get the latest history item to display in the header
		$submissionEventLogDao = DAORegistry::getDAO('SubmissionEventLogDAO');
		$submissionEvents = $submissionEventLogDao->getBySubmissionId($this->_submission->getId());
		$lastEvent = $submissionEvents->next();

		// Assign variables to the template manager and display
		$templateMgr = TemplateManager::getManager($request);
		if(isset($lastEvent)) {
			$templateMgr->assign('lastEvent', $lastEvent);

			// Get the user who posted the last note
			$userDao = DAORegistry::getDAO('UserDAO');
			$user = $userDao->getById($lastEvent->getUserId());
			$templateMgr->assign('lastEventUser', $user);
		}

		return parent::viewInformationCenter($args, $request);
	}

	/**
	 * Display the notes tab.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function viewNotes($args, $request) {
		$this->setupTemplate($request);

		import('lib.pkp.controllers.informationCenter.form.NewSubmissionNoteForm');
		$notesForm = new NewSubmissionNoteForm($this->_submission->getId());
		$notesForm->initData();

		return new JSONMessage(true, $notesForm->fetch($request));
	}

	/**
	 * Save a note.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveNote($args, $request) {
		$this->setupTemplate($request);

		import('lib.pkp.controllers.informationCenter.form.NewSubmissionNoteForm');
		$notesForm = new NewSubmissionNoteForm($this->_submission->getId());
		$notesForm->readInputData();

		if ($notesForm->validate()) {
			$notesForm->execute($request);

			// Save to event log
			import('lib.pkp.classes.log.SubmissionLog');
			SubmissionLog::logEvent($request, $this->_submission, $eventType, 'informationCenter.history.notePosted');
			return new JSONMessage(true);
		} else {
			// Return a JSON string indicating failure
			return new JSONMessage(false);
		}
	}

	/**
	 * Fetch the contents of the event log.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function viewHistory($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$dispatcher = $request->getDispatcher();
		$templateMgr->assign('gridParameters', $this->_getLinkParams());
		return $templateMgr->fetchJson('controllers/informationCenter/submissionHistory.tpl');
	}

	/**
	 * Get the association ID for this information center view
	 * @return int
	 */
	function _getAssocId() {
		return $this->_submission->getId();
	}

	/**
	 * Get the association type for this information center view
	 * @return int
	 */
	function _getAssocType() {
		return ASSOC_TYPE_SUBMISSION;
	}
}

?>
