<?php

/**
 * @file controllers/grid/eventLog/SubmissionFileEventLogGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFileEventLogGridHandler
 * @ingroup controllers_grid_eventLog
 *
 * @brief Grid handler presenting the submission file event log grid.
 */

// import grid base classes
import('lib.pkp.controllers.grid.eventLog.SubmissionEventLogGridHandler');

class SubmissionFileEventLogGridHandler extends SubmissionEventLogGridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Getters/Setters
	//
	/**
	 * Get the submission file associated with this grid.
	 * @return Submission
	 */
	function getSubmissionFile() {
		return $this->_submissionFile;
	}

	/**
	 * Set the submission file
	 * @param $submissionFile SubmissionFile
	 */
	function setSubmissionFile($submissionFile) {
		$this->_submissionFile = $submissionFile;
	}


	//
	// Overridden methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.SubmissionFileAccessPolicy');
		$this->addPolicy(new SubmissionFileAccessPolicy($request, $args, $roleAssignments, SUBMISSION_FILE_ACCESS_READ));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Configure the grid
	 * @param $request PKPRequest
	 */
	function initialize($request) {
		parent::initialize($request);

		// Retrieve the authorized monograph.
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$submissionFile = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION_FILE);
		$this->setSubmissionFile($submissionFile);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * Get the arguments that will identify the data in the grid
	 * In this case, the monograph.
	 * @return array
	 */
	function getRequestArgs() {
		$submissionFile = $this->getSubmissionFile();

		return array(
			'submissionId' => $submissionFile->getSubmissionId(),
			'fileId' => $submissionFile->getFileId(),
			'revision' => $submissionFile->getRevision(),
		);
	}

	/**
	 * @copydoc GridHandler::loadData
	 */
	protected function loadData($request, $filter = null) {
		$submissionFile = $this->getSubmissionFile();
		$submissionFileEventLogDao = DAORegistry::getDAO('SubmissionFileEventLogDAO');
		$eventLogEntries = $submissionFileEventLogDao->getByFileId(
			$submissionFile->getFileId()
		);
		$eventLogEntries = $eventLogEntries->toArray();

		if ($filter['allEvents']) {
			// Also include events from past versions
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
			while (true) {
				$submissionFile = $submissionFileDao->getRevision($submissionFile->getSourceFileId(), $submissionFile->getSourceRevision());
				if (!$submissionFile) break;

				$iterator = $submissionFileEventLogDao->getByFileId($submissionFile->getFileId());
				$eventLogEntries += $iterator->toArray();
			}
		}

		return $eventLogEntries;
	}

	/**
	 * @copydoc GridHandler::getFilterForm()
	 * @return string Filter template.
	 */
	protected function getFilterForm() {
		// If the user only has an author role, do not permit access
		// to earlier stages.
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		if (array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT), $userRoles)) {
			return 'controllers/grid/eventLog/eventLogGridFilter.tpl';
		}
		return parent::getFilterForm();
	}

	/**
	 * @copydoc GridHandler::getFilterSelectionData()
	 */
	function getFilterSelectionData($request) {
		return array('allEvents' => $request->getUserVar('allEvents') ? true : false);
	}
}

?>
