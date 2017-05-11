<?php

/**
 * @file classes/submission/form/PKPSubmissionSubmitStep4Form.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPSubmissionSubmitStep4Form
 * @ingroup submission_form
 *
 * @brief Form for Step 4 of author submission: confirm & complete
 */

import('lib.pkp.classes.submission.form.SubmissionSubmitForm');

class PKPSubmissionSubmitStep4Form extends SubmissionSubmitForm {

	/**
	 * Constructor.
	 * @param $context Context
	 * @param $submission Submission
	 */
	function __construct($context, $submission) {
		parent::__construct($context, $submission, 4);
	}

	/**
	 * Save changes to submission.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return int the submission ID
	 */
	function execute($args, $request) {
		$submissionDao = Application::getSubmissionDAO();

		// Set other submission data.
		if ($this->submission->getSubmissionProgress() <= $this->step) {
			$this->submission->setDateSubmitted(Core::getCurrentDate());
			$this->submission->stampStatusModified();
			$this->submission->setSubmissionProgress(0);
		}

		parent::execute($this->submission);

		// Save the submission.
		$submissionDao->updateObject($this->submission);

		// Assign the default stage participants.
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');

		// Manager and assistant roles -- for each assigned to this
		//  stage in setup, iff there is only one user for the group,
		//  automatically assign the user to the stage.
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		$submissionStageGroups = $userGroupDao->getUserGroupsByStage($this->submission->getContextId(), WORKFLOW_STAGE_ID_SUBMISSION);
		$managerFound = false;
		while ($userGroup = $submissionStageGroups->next()) {
			// Only handle manager and assistant roles
			if (!in_array($userGroup->getRoleId(), array(ROLE_ID_MANAGER, ROLE_ID_ASSISTANT))) continue;

			$users = $userGroupDao->getUsersById($userGroup->getId(), $this->submission->getContextId());
			if($users->getCount() == 1) {
				$user = $users->next();
				$stageAssignmentDao->build($this->submission->getId(), $userGroup->getId(), $user->getId());
				if ($userGroup->getRoleId() == ROLE_ID_MANAGER) $managerFound = true;
			}
		}

		// Author roles
		// Assign only the submitter in whatever ROLE_ID_AUTHOR capacity they were assigned previously
		$user = $request->getUser();
		$submitterAssignments = $stageAssignmentDao->getBySubmissionAndStageId($this->submission->getId(), null, null, $user->getId());
		while ($assignment = $submitterAssignments->next()) {
			$userGroup = $userGroupDao->getById($assignment->getUserGroupId());
			if ($userGroup->getRoleId() == ROLE_ID_AUTHOR) {
				$stageAssignmentDao->build($this->submission->getId(), $userGroup->getId(), $assignment->getUserId());
				// Only assign them once, since otherwise we'll one assignment for each previous stage.
				// And as long as they are assigned once, they will get access to their submission.
				break;
			}
		}

		$notificationManager = new NotificationManager();

		// Assign sub editors for that section
		$submissionSubEditorFound = false;
		$subEditorsDao = DAORegistry::getDAO('SubEditorsDAO');
		$subEditors = $subEditorsDao->getBySectionId($this->submission->getSectionId(), $this->submission->getContextId());
		foreach ($subEditors as $subEditor) {
			$userGroups = $userGroupDao->getByUserId($subEditor->getId(), $this->submission->getContextId());
			while ($userGroup = $userGroups->next()) {
				if ($userGroup->getRoleId() != ROLE_ID_SUB_EDITOR) continue;
				$stageAssignmentDao->build($this->submission->getId(), $userGroup->getId(), $subEditor->getId());
				// If we assign a stage assignment in the Submission stage to a sub editor, make note.
				if ($userGroupDao->userGroupAssignedToStage($userGroup->getId(), WORKFLOW_STAGE_ID_SUBMISSION)) {
					$submissionSubEditorFound = true;
				}
			}
		}

		// Update assignment notifications
		import('classes.workflow.EditorDecisionActionsManager');
		$notificationManager->updateNotification(
			$request,
			EditorDecisionActionsManager::getStageNotifications(),
			null,
			ASSOC_TYPE_SUBMISSION,
			$this->submission->getId()
		);

		// Send a notification to associated users if an editor needs assigning
		if (!$managerFound && !$submissionSubEditorFound) {
			$roleDao = DAORegistry::getDAO('RoleDAO'); /* @var $roleDao RoleDAO */

			// Get the managers.
			$managers = $roleDao->getUsersByRoleId(ROLE_ID_MANAGER, $this->submission->getContextId());

			$managersArray = $managers->toAssociativeArray();

			$allUserIds = array_keys($managersArray);
			foreach ($allUserIds as $userId) {
				$notificationManager->createNotification(
					$request, $userId, NOTIFICATION_TYPE_SUBMISSION_SUBMITTED,
					$this->submission->getContextId(), ASSOC_TYPE_SUBMISSION, $this->submission->getId()
				);

				// Add TASK notification indicating that a submission is unassigned
				$notificationManager->createNotification(
					$request,
					$userId,
					NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_REQUIRED,
					$this->submission->getContextId(),
					ASSOC_TYPE_SUBMISSION,
					$this->submission->getId(),
					NOTIFICATION_LEVEL_TASK
				);
			}
		}

		$notificationManager->updateNotification(
			$request,
			array(NOTIFICATION_TYPE_APPROVE_SUBMISSION),
			null,
			ASSOC_TYPE_SUBMISSION,
			$this->submission->getId()
		);

		return $this->submissionId;
	}
}

?>
