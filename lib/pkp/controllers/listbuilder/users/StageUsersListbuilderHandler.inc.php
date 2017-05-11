<?php

/**
 * @file controllers/listbuilder/users/StageUsersListbuilderHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StageUsersListbuilderHandler
 * @ingroup listbuilder
 *
 * @brief Class for adding participants to a stage.
 */

import('lib.pkp.controllers.listbuilder.users.UsersListbuilderHandler');

class StageUsersListbuilderHandler extends UsersListbuilderHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR),
			array('fetch', 'fetchRow', 'fetchOptions')
		);
	}

	//
	// Getters/Setters
	//
	/**
	 * Get the authorized submission.
	 * @return Submission
	 */
	function getSubmission() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
	}

	/**
	 * Get the stage ID.
	 * @return int WORKFLOW_STAGE_...
	 */
	function getStageId() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
	}

	//
	// Overridden parent class functions
	//
	/**
	 * @copydoc GridDataProvider::getRequestArgs()
	 */
	function getRequestArgs() {
		$submission = $this->getSubmission();
		return array(
			'submissionId' => $submission->getId(),
			'stageId' => $this->getStageId()
		);
	}

	//
	// Implement protected template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments, 'submissionId'));
		return parent::authorize($request, $args, $roleAssignments);
	}

	//
	// Implement methods from ListbuilderHandler
	//
	/**
	 * @copydoc ListbuilderHandler::getOptions
	 */
	function getOptions() {
		// Initialize the object to return
		$items = array(
			array()
		);

		$userStageAssignmentDao = DAORegistry::getDAO('UserStageAssignmentDAO');
		$submission = $this->getSubmission();

		// FIXME: add stage id?
		$users = $userStageAssignmentDao->getUsersBySubmissionAndStageId($submission->getId());
		while ($user = $users->next()) {
			$items[0][$user->getId()] = $user->getFullName() . ' <' . $user->getEmail() . '>';
		}
		return $items;
	}

	/**
	 * @copydoc GridHandler::loadData($request, $filter)
	 */
	protected function loadData($request) {
		$userStageAssignmentDao = DAORegistry::getDAO('UserStageAssignmentDAO');
		$submission = $this->getSubmission();

		// A list of user IDs may be specified via request parameter; validate them.
		$users = $userStageAssignmentDao->getUsersBySubmissionAndStageId($submission->getId());
		$selectedUserIds = (array) $request->getUserVar('userIds');
		$items = array();
		while ($user = $users->next()) {
			if (in_array($user->getId(), $selectedUserIds)) $items[$user->getId()] = $user;
		}
		return $items;
	}
}

?>
