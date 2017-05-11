<?php

/**
 * @file controllers/grid/submissions/SubmissionsListGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionsListGridCellProvider
 * @ingroup controllers_grid_submissions
 *
 * @brief Class for a cell provider that can retrieve labels from submissions
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

class SubmissionsListGridCellProvider extends DataObjectGridCellProvider {

	/** @var Array */
	var $_authorizedRoles;

	/** @var User */
	var $user;

	/**
	 * Constructor
	 */
	function __construct($user, $authorizedRoles = null) {
		if ($authorizedRoles) {
			$this->_authorizedRoles = $authorizedRoles;
		}
		$this->user = $user;
		parent::__construct();
	}


	//
	// Getters and setters.
	//
	/**
	 * Get the user authorized roles.
	 * @return array
	 */
	function getAuthorizedRoles() {
		return $this->_authorizedRoles;
	}


	//
	// Public functions.
	//
	/**
	 * Gathers the state of a given cell given a $row/$column combination
	 * @param $row GridRow
	 * @param $column GridColumn
	 */
	function getCellState($row, $column) {
		return '';
	}


	/**
	 * Get cell actions associated with this row/column combination
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array an array of LinkAction instances
	 */
	function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		$submission = $row->getData();
		$user = $request->getUser();
		switch ($column->getId()) {
			case 'editor':
				$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO'); /* @var $stageAssignmentDao StageAssignmentDAO */
				$editorAssignments = $stageAssignmentDao->getEditorsAssignedToStage($submission->getId(), $submission->getStageId());
				$assignment = current($editorAssignments);
				if (!$assignment) return array();
				$userDao = DAORegistry::getDAO('UserDAO'); /* @var $userDao UserDAO */
				$editor = $userDao->getById($assignment->getUserId());

				import('lib.pkp.classes.linkAction.request.NullAction');
				return array(new LinkAction('editor', new NullAction(), $editor->getInitials(), null, $editor->getFullName()));
			case 'stage':
				$stageId = $submission->getStageId();
				$stage = null;

				if ($submission->getSubmissionProgress() > 0) {
					// Submission process not completed.
					$stage = __('submissions.incomplete');
				}
				switch ($submission->getStatus()) {
					case STATUS_DECLINED:
						$stage = __('submission.status.declined');
						break;
					case STATUS_PUBLISHED:
						$stage = __('submission.status.published');
						break;
				}

				if (!$stage) $stage = __(WorkflowStageDAO::getTranslationKeyFromId($stageId));

				import('lib.pkp.classes.linkAction.request.RedirectAction');
				$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
				$reviewAssignment = $reviewAssignmentDao->getLastReviewRoundReviewAssignmentByReviewer($submission->getId(), $user->getId());
				if (is_a($reviewAssignment, 'ReviewAssignment') && ($reviewAssignment->getStageId() == $stageId)) {
					return array(new LinkAction(
						'itemWorkflow',
						new RedirectAction(
							$request->getDispatcher()->url(
								$request, ROUTE_PAGE,
								null,
								'reviewer', 'submission',
								$submission->getId()
							)
						),
						$stage
					));
				}
				return array(new LinkAction(
					'itemWorkflow',
					new RedirectAction(
						SubmissionsListGridCellProvider::getUrlByUserRoles($request, $submission)
					),
					$stage
				));
		}
		return parent::getCellActions($request, $row, $column, $position);
	}

	//
	// Template methods from GridCellProvider
	//
	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$submission = $row->getData();
		$columnId = $column->getId();
		assert(is_a($submission, 'DataObject') && !empty($columnId));

		switch ($columnId) {
			case 'id':
				return array('label' => $submission->getId());
			case 'title':
				$this->_titleColumn = $column;
				$title = $submission->getLocalizedTitle();
				if ( empty($title) ) $title = __('common.untitled');

				// Ensure we aren't exposing the author name to reviewers
				$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
				$reviewAssignment = $reviewAssignmentDao->getLastReviewRoundReviewAssignmentByReviewer($submission->getId(), $this->user->getId());
				if (!$reviewAssignment || $reviewAssignment->getReviewMethod() != SUBMISSION_REVIEW_METHOD_DOUBLEBLIND) {
					$authorsInTitle = $submission->getShortAuthorString();
					$title = $authorsInTitle . '; ' . $title;
				}

				return array('label' => $title);
			case 'dateAssigned':
				assert(is_a($submission, 'ReviewerSubmission'));
				$dateAssigned = strftime(Config::getVar('general', 'date_format_short'), strtotime($submission->getDateAssigned()));
				if ( empty($dateAssigned) ) $dateAssigned = '--';
				return array('label' => $dateAssigned);
			case 'dateDue':
				$dateDue = strftime(Config::getVar('general', 'date_format_short'), strtotime($submission->getDateDue()));
				if ( empty($dateDue) ) $dateDue = '--';
				return array('label' => $dateDue);
			case 'stage':
			case 'editor':
				return array('label' => '');
		}
	}


	//
	// Public static methods
	//
	/**
	 * Static method that returns the correct access URL for a submission
	 * between 'authordashboard', 'workflow', and 'submission', based on
	 * users roles.
	 * @param $request Request
	 * @param $submission Submission
	 * @param $userId an optional user id
	 * @param $stageName string An optional suggested stage name
	 * @return string|null URL; null if the user does not exist
	 */
	static function getUrlByUserRoles($request, $submission, $userId = null, $stageName = null) {
		// Get the current user, relying on current session if appropriate
		if ($userId == null) {
			$user = $request->getUser();
		} else {
			$userDao = DAORegistry::getDAO('UserDAO');
			$user = $userDao->getById($userId);
		}
		if ($user == null) return null;

		// Get the submission's context, relying on the current session if appropriate
		if (!($context = $request->getContext()) || $context->getId() != $submission->getContextId()) {
			$contextDao = Application::getContextDAO();
			$context = $contextDao->getById($submission->getContextId());
		} 
		$dispatcher = $request->getDispatcher();

		// Incomplete submissions get sent back to the wizard.
		if ($submission->getSubmissionProgress()>0) {
			return $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'submission', 'wizard', $submission->getSubmissionProgress(), array('submissionId' => $submission->getId()));
		}

		// If user is enrolled with a context manager user group, let
		// them access the workflow pages.
		$roleDao = DAORegistry::getDAO('RoleDAO');
		if ($roleDao->userHasRole($context->getId(), $user->getId(), ROLE_ID_MANAGER)) {
			return $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'workflow', $stageName?$stageName:'access', $submission->getId());
		}

		// If user has only author role user groups stage assignments,
		// then add an author dashboard link action.
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');

		$authorUserGroupIds = $userGroupDao->getUserGroupIdsByRoleId(ROLE_ID_AUTHOR);
		$stageAssignmentsFactory = $stageAssignmentDao->getBySubmissionAndStageId($submission->getId(), null, null, $user->getId());

		// Check if the user should be considered as author.
		$authorDashboard = false;
		while ($stageAssignment = $stageAssignmentsFactory->next()) {
			if (!in_array($stageAssignment->getUserGroupId(), $authorUserGroupIds)) {
				$authorDashboard = false;
				break;
			} else $authorDashboard = true;
		}
		if ($authorDashboard) return $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'authorDashboard', 'submission', $submission->getId());

		// Check if the user should be considered as reviewer.
		$reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignment = $reviewAssignmentDao->getLastReviewRoundReviewAssignmentByReviewer($submission->getId(), $request->getUser()->getId());
		if ($reviewAssignment) return $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'reviewer', 'submission', $submission->getId());

		return $dispatcher->url($request, ROUTE_PAGE, $context->getPath(), 'workflow', $stageName?$stageName:'access', $submission->getId());
	}
}

?>
