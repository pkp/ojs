<?php

/**
 * @file classes/workflow/EditorDecisionActionsManager.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditorDecisionActionsManager
 * @ingroup classes_workflow
 *
 * @brief Wrapper class for create and assign editor decisions actions to template manager.
 */

// Submission stage decision actions.
define('SUBMISSION_EDITOR_DECISION_EXTERNAL_REVIEW', 8);

// Submission and review stages decision actions.
define('SUBMISSION_EDITOR_DECISION_ACCEPT', 1);
define('SUBMISSION_EDITOR_DECISION_DECLINE', 4);
define('SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE', 9);

// Review stage decisions actions.
define('SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS', 2);
define('SUBMISSION_EDITOR_DECISION_RESUBMIT', 3);

// Review stage recommendation actions.
define('SUBMISSION_EDITOR_RECOMMEND_ACCEPT', 11);
define('SUBMISSION_EDITOR_RECOMMEND_DECLINE', 14);
define('SUBMISSION_EDITOR_RECOMMEND_PENDING_REVISIONS', 12);
define('SUBMISSION_EDITOR_RECOMMEND_RESUBMIT', 13);

// Editorial stage decision actions.
define('SUBMISSION_EDITOR_DECISION_SEND_TO_PRODUCTION', 7);

class EditorDecisionActionsManager {

	/**
	 * Get decision actions labels.
	 * @param $decisions
	 * @return array
	 */
	static function getActionLabels($decisions) {
		$allDecisionsData =
			self::_submissionStageDecisions() +
			self::_externalReviewStageDecisions() +
			self::_editorialStageDecisions();

		$actionLabels = array();
		foreach($decisions as $decision) {
			if ($allDecisionsData[$decision]['title']) {
				$actionLabels[$decision] = $allDecisionsData[$decision]['title'];
			} else {
				assert(false);
			}
		}

		return $actionLabels;
	}

	/**
	 * Check for editor decisions in the review round.
	 * @param $reviewRound ReviewRound
	 * @param $decisions array
	 * @return boolean
	 */
	static function getEditorTakenActionInReviewRound($reviewRound, $decisions = array()) {
		$editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
		$editorDecisions = $editDecisionDao->getEditorDecisions($reviewRound->getSubmissionId(), $reviewRound->getStageId(), $reviewRound->getRound());

		if (empty($decisions)) {
			$decisions = array_keys(self::_externalReviewStageDecisions());
		}
		$takenDecision = false;
		foreach ($editorDecisions as $decision) {
			if (in_array($decision['decision'], $decisions)) {
				$takenDecision = true;
				break;
			}
		}

		return $takenDecision;
	}

	/**
	 * Get the available decisions by stage ID.
	 * @param $stageId int WORKFLOW_STAGE_ID_...
	 */
	static function getStageDecisions($stageId) {
		switch ($stageId) {
			case WORKFLOW_STAGE_ID_SUBMISSION:
				return self::_submissionStageDecisions();
			case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
				return self::_externalReviewStageDecisions();
			case WORKFLOW_STAGE_ID_EDITING:
				return self::_editorialStageDecisions();
			default:
				assert(false);
		}
	}

	/**
	 * Get an associative array matching editor recommendation codes with locale strings.
	 * (Includes default '' => "Choose One" string.)
	 * @param $stageId integer
	 * @return array recommendation => localeString
	 */
	static function getRecommendationOptions($stageId) {
		static $recommendationOptions = array(
			'' => 'common.chooseOne',
			SUBMISSION_EDITOR_RECOMMEND_PENDING_REVISIONS => 'editor.submission.decision.requestRevisions',
			SUBMISSION_EDITOR_RECOMMEND_RESUBMIT => 'editor.submission.decision.resubmit',
			SUBMISSION_EDITOR_RECOMMEND_ACCEPT => 'editor.submission.decision.accept',
			SUBMISSION_EDITOR_RECOMMEND_DECLINE => 'editor.submission.decision.decline',
		);
		return $recommendationOptions;
	}

	//
	// Private helper methods.
	//
	/**
	 * Define and return editor decisions for the submission stage.
	 * @return array
	 */
	static function _submissionStageDecisions() {
		static $decisions = array(
			SUBMISSION_EDITOR_DECISION_EXTERNAL_REVIEW => array(
				'operation' => 'externalReview',
				'name' => 'externalReview',
				'title' => 'editor.submission.decision.sendExternalReview',
				'toStage' => 'editor.review',
			),
			SUBMISSION_EDITOR_DECISION_ACCEPT => array(
				'name' => 'accept',
				'operation' => 'promote',
				'title' => 'editor.submission.decision.skipReview',
				'toStage' => 'submission.copyediting',
			),
			SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE => array(
				'name' => 'decline',
				'operation' => 'sendReviews',
				'title' => 'editor.submission.decision.decline',
			),
		);

		return $decisions;
	}

	/**
	 * Define and return editor decisions for the review stage.
	 * @return array
	 */
	static function _externalReviewStageDecisions() {
		static $decisions = array(
			SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => array(
				'operation' => 'sendReviewsInReview',
				'name' => 'requestRevisions',
				'title' => 'editor.submission.decision.requestRevisions',
			),
			SUBMISSION_EDITOR_DECISION_RESUBMIT => array(
				'name' => 'resubmit',
				'title' => 'editor.submission.decision.resubmit',
			),
			SUBMISSION_EDITOR_DECISION_ACCEPT => array(
				'operation' => 'promoteInReview',
				'name' => 'accept',
				'title' => 'editor.submission.decision.accept',
				'toStage' => 'submission.copyediting',
			),
			SUBMISSION_EDITOR_DECISION_DECLINE => array(
				'operation' => 'sendReviewsInReview',
				'name' => 'decline',
				'title' => 'editor.submission.decision.decline',
			),
		);

		return $decisions;
	}

	/**
	 * Define and return editor decisions for the editorial stage.
	 * @return array
	 */
	static function _editorialStageDecisions() {
		static $decisions = array(
			SUBMISSION_EDITOR_DECISION_SEND_TO_PRODUCTION => array(
				'operation' => 'promote',
				'name' => 'sendToProduction',
				'title' => 'editor.submission.decision.sendToProduction',
				'toStage' => 'submission.production',
			),
		);

		return $decisions;
	}

	/**
	 * Get the stage-level notification type constants.
	 * @return array
	 */
	static function getStageNotifications() {
		static $notifications = array(
			NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_SUBMISSION,
			NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EXTERNAL_REVIEW,
			NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EDITING,
			NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_PRODUCTION
		);
		return $notifications;
	}
}

?>
