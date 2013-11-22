<?php

/**
 * @file controllers/grid/users/author/AuthorGridRow.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorGridRow
 * @ingroup controllers_grid_users_author
 *
 * @brief Author grid row definition
 */

import('lib.pkp.controllers.grid.users.author.PKPAuthorGridRow');


class AuthorGridRow extends PKPAuthorGridRow {
	/**
	 * Constructor
	 */
	function AuthorGridRow(&$monograph, $readOnly = false) {
		parent::PKPAuthorGridRow($monograph, $readOnly);
	}

	/**
	 * Determines whether the current user can create user accounts from authors present
	 * in the grid.
	 * @param PKPRequest $request
	 * @return boolean
	 */
	function canAdminister($request) {
		$submission = $this->getSubmission();

		$user = $request->getUser();
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');

		$stageAssignments = $stageAssignmentDao->getBySubmissionAndStageId($submission->getId(), $submission->getStageId(), null, $user->getId());
		while ($stageAssignment = $stageAssignments->next()) {
			$userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
			if (in_array($userGroup->getRoleId(), array(ROLE_ID_MANAGER, ROLE_ID_EDITOR))) {
				return true;
				break;
			}
		}
		return false;
	}
}

?>
