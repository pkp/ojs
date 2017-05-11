<?php

/**
 * @file controllers/grid/users/reviewer/ReviewerGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerGridCellProvider
 * @ingroup controllers_grid_users_reviewer
 *
 * @brief Base class for a cell provider that can retrieve labels for reviewer grid rows
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

import('lib.pkp.classes.linkAction.request.AjaxModal');
import('lib.pkp.classes.linkAction.request.AjaxAction');

class ReviewerGridCellProvider extends DataObjectGridCellProvider {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Template methods from GridCellProvider
	//
	/**
	 * Gathers the state of a given cell given a $row/$column combination
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return string
	 */
	function getCellState($row, $column) {
		$reviewAssignment = $row->getData();
		$columnId = $column->getId();
		assert(is_a($reviewAssignment, 'DataObject') && !empty($columnId));
		switch ($columnId) {
			case 'name':
				return '';
			case 'considered':
			case 'actions':

				if ($reviewAssignment->getDeclined()) {
					return 'declined';
				}

				// The review has not been completed.
				if (!$reviewAssignment->getDateCompleted()) {
					if ($reviewAssignment->getDateDue() < Core::getCurrentDate(strtotime('tomorrow'))) {
						return 'overdue';
					} elseif($reviewAssignment->getDateResponseDue() < Core::getCurrentDate(strtotime('tomorrow')) && !$reviewAssignment->getDateConfirmed()) {
						return 'overdue_response';
					} else {
						if (!$reviewAssignment->getDateConfirmed()) {
							return 'waiting';
						} else {
							return 'accepted';
						}
					}
				}

				// The reviewer has been sent an acknowledgement.
				// Completed states can be 'unconsidered' by an editor.
				if ($reviewAssignment->getDateAcknowledged() && !$reviewAssignment->getUnconsidered()) {
					return 'completed';
				}

				if ($reviewAssignment->getUnconsidered() == REVIEW_ASSIGNMENT_UNCONSIDERED) {
					return 'reviewReady';
				}

				// Check if the somebody assigned to this stage has read the review.
				$submissionDao = Application::getSubmissionDAO();
				$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
				$userStageAssignmentDao = DAORegistry::getDAO('UserStageAssignmentDAO');
				$viewsDao = DAORegistry::getDAO('ViewsDAO');

				$submission = $submissionDao->getById($reviewAssignment->getSubmissionId());

				// Get the user groups for this stage
				$userGroups = $userGroupDao->getUserGroupsByStage(
					$submission->getContextId(),
					$reviewAssignment->getStageId()
				);
				while ($userGroup = $userGroups->next()) {
					if (!in_array($userGroup->getRoleId(), array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR))) continue;

					// Get the users assigned to this stage and user group
					$stageUsers = $userStageAssignmentDao->getUsersBySubmissionAndStageId(
						$reviewAssignment->getSubmissionId(),
						$reviewAssignment->getStageId(),
						$userGroup->getId()
					);

					// mark as completed (viewed) if any of the manager/editor users viewed it.
					while ($user = $stageUsers->next()) {
						if ($viewsDao->getLastViewDate(
							ASSOC_TYPE_REVIEW_RESPONSE,
							$reviewAssignment->getId(), $user->getId()
						)) {
							// Some user has read the review.
							return 'read';
						}
					}
				}

				// Nobody has read the review.
				return 'reviewReady';
		}
	}

	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$element = $row->getData();
		$columnId = $column->getId();
		assert(is_a($element, 'DataObject') && !empty($columnId));
		switch ($columnId) {
			case 'name':
				return array('label' => $element->getReviewerFullName());

			case 'considered':
				return array('label' => $this->_getStatusText($this->getCellState($row, $column), $row));

			case 'actions':
				// Only attach actions to this column. See self::getCellActions()
				return array('label' => '');
		}

		return parent::getTemplateVarsFromRowColumn($row, $column);
	}

	/**
	 * Get cell actions associated with this row/column combination
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array an array of LinkAction instances
	 */
	function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		$reviewAssignment = $row->getData();
		$actionArgs = array(
			'submissionId' => $reviewAssignment->getSubmissionId(),
			'reviewAssignmentId' => $reviewAssignment->getId(),
			'stageId' => $reviewAssignment->getStageId()
		);

		$router = $request->getRouter();
		$action = false;
		$submissionDao = Application::getSubmissionDAO();
		$submission = $submissionDao->getById($reviewAssignment->getSubmissionId());

		// Only attach actions to the actions column. The actions and status
		// columns share state values.
		$columnId = $column->getId();
		if ($columnId == 'actions') {
			switch($this->getCellState($row, $column)) {
				case 'overdue':
				case 'overdue_response':
					import('lib.pkp.controllers.api.task.SendReminderLinkAction');
					return array(new SendReminderLinkAction($request, 'editor.review.reminder', $actionArgs));
				case 'read':
					import('lib.pkp.controllers.api.task.SendThankYouLinkAction');
					return array(new SendThankYouLinkAction($request, 'editor.review.thankReviewer', $actionArgs));
				case 'completed':
					import('lib.pkp.controllers.review.linkAction.UnconsiderReviewLinkAction');
					return array(new UnconsiderReviewLinkAction($request, $reviewAssignment, $submission));
				case 'reviewReady':
					$user = $request->getUser();
					import('lib.pkp.controllers.review.linkAction.ReviewNotesLinkAction');
					return array(new ReviewNotesLinkAction($request, $reviewAssignment, $submission, $user, true));
			}
		}
		return parent::getCellActions($request, $row, $column, $position);
	}

	/**
	 * Provide meaningful locale keys for the various grid status states.
	 * @param string $state
	 * @param $row GridRow
	 * @return string
	 */
	function _getStatusText($state, $row) {
		$reviewAssignment = $row->getData();
		switch ($state) {
			case 'waiting':
				return '<span class="state">'.__('editor.review.requestSent').'</span><span class="details">'.__('editor.review.responseDue', array('date' => substr($reviewAssignment->getDateResponseDue(),0,10))).'</span>';
			case 'accepted':
				return '<span class="state">'.__('editor.review.requestAccepted').'</span><span class="details">'.__('editor.review.reviewDue', array('date' => substr($reviewAssignment->getDateDue(),0,10))).'</span>';
			case 'completed':
				return $this->_getStatusWithRecommendation('common.complete', $reviewAssignment);
			case 'overdue':
				return '<span class="state overdue">'.__('common.overdue').'</span><span class="details">'.__('editor.review.reviewDue', array('date' => substr($reviewAssignment->getDateDue(),0,10))).'</span>';
			case 'overdue_response':
				return '<span class="state overdue">'.__('common.overdue').'</span><span class="details">'.__('editor.review.responseDue', array('date' => substr($reviewAssignment->getDateResponseDue(),0,10))).'</span>';
			case 'declined':
				return '<span class="state declined">'.__('common.declined').'</span>';
			case 'reviewReady':
				return $this->_getStatusWithRecommendation('editor.review.reviewSubmitted', $reviewAssignment);
			case 'read':
				return $this->_getStatusWithRecommendation('editor.review.reviewConfirmed', $reviewAssignment);
			default:
				return '';
		}
	}

	/**
	 * Retrieve a formatted HTML string that displays the state of the review
	 * with the review recommendation if one exists. Or return just the state.
	 * Only works with some states.
	 *
	 * @param string $statusKey Locale key for status text
	 * @param ReviewAssignment $reviewAssignment
	 * @return string
	 */
	function _getStatusWithRecommendation($statusKey, $reviewAssignment) {

		if (!$reviewAssignment->getRecommendation()) {
			return __($statusKey);
		}

		return '<span class="state">'.__($statusKey).'</span><span class="details">'.__('submission.recommendation', array('recommendation' => $reviewAssignment->getLocalizedRecommendation())).'</span>';
	}
}

?>
