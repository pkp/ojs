<?php

/**
 * @file controllers/listbuilder/users/QueryUsersListbuilderHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueryUsersListbuilderHandler
 * @ingroup listbuilder
 *
 * @brief Class for adding participants to a stage.
 */

import('lib.pkp.controllers.listbuilder.users.UsersListbuilderHandler');

class QueryUsersListbuilderHandler extends UsersListbuilderHandler {
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
	 * Get the authorized query.
	 * @return Query
	 */
	function getQuery() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_QUERY);
	}

	/**
	 * Get the authorized query.
	 * @return Representation
	 */
	function getRepresentation() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_REPRESENTATION);
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
		$representation = $this->getRepresentation();
		return array(
			'submissionId' => $submission->getId(),
			'stageId' => $this->getStageId(),
			'queryId' => $this->getQuery()->getId(),
			'representationId' => $representation?$representation->getId():null,
		);
	}

	//
	// Implement protected template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.QueryAccessPolicy');
		$this->addPolicy(new QueryAccessPolicy($request, $args, $roleAssignments, $request->getUserVar('stageId')));

		// If a representation was specified, authorize it.
		if ($request->getUserVar('representationId')) {
			import('lib.pkp.classes.security.authorization.internal.RepresentationRequiredPolicy');
			$this->addPolicy(new RepresentationRequiredPolicy($request, $args));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	//
	// Implement methods from ListbuilderHandler
	//
	/**
	 * @copydoc ListbuilderHandler::getOptions
	 */
	function getOptions() {
		$userStageAssignmentDao = DAORegistry::getDAO('UserStageAssignmentDAO');
		$users = $userStageAssignmentDao->getUsersBySubmissionAndStageId($this->getSubmission()->getId(), $this->getStageId());
		$items = array(array());
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
		$queryDao = DAORegistry::getDAO('QueryDAO');
		$userIds = $queryDao->getParticipantIds($this->getQuery()->getId());
		$items = array();
		while ($user = $users->next()) {
			if (in_array($user->getId(), $userIds)) $items[$user->getId()] = $user;
		}
		return $items;
	}
}

?>
