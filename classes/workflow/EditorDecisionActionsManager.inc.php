<?php

/**
 * @file classes/workflow/EditorDecisionActionsManager.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	static function getActionLabels($context, $decisions) {
		$allDecisionsData =
			self::_submissionStageDecisions() +
			self::_externalReviewStageDecisions($context) +
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
	static function getEditorTakenActionInReviewRound($context, $reviewRound, $decisions = array()) {
		$editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
		$editorDecisions = $editDecisionDao->getEditorDecisions($reviewRound->getSubmissionId(), $reviewRound->getStageId(), $reviewRound->getRound());

		if (empty($decisions)) {
			$decisions = array_keys(self::_externalReviewStageDecisions($context));
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
	 * Get the available decisions by stage ID and user making decision permissions,
	 * if the user can make decisions or if it is recommendOnly user.
	 * @param $context Context
	 * @param $stageId int WORKFLOW_STAGE_ID_...
	 * @param $makeDecision boolean If the user can make decisions
	 */
	static function getStageDecisions($context, $stageId, $makeDecision = true) {
		switch ($stageId) {
			case WORKFLOW_STAGE_ID_SUBMISSION:
				return self::_submissionStageDecisions($makeDecision);
			case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
				return self::_externalReviewStageDecisions($context, $makeDecision);
			case WORKFLOW_STAGE_ID_EDITING:
				return self::_editorialStageDecisions($makeDecision);
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
	 * If the user cannot make decisions i.e. if it is a recommendOnly user,
	 * the user can only send the submission to the review stage, and neither
	 * acept nor decline the submission.
	 * @param $makeDecision boolean If the user can make decisions
	 * @return array
	 */
	static function _submissionStageDecisions($makeDecision = true) {
		$decisions = array(
			SUBMISSION_EDITOR_DECISION_EXTERNAL_REVIEW => array(
				'operation' => 'externalReview',
				'name' => 'externalReview',
				'title' => 'editor.submission.decision.sendExternalReview',
				'toStage' => 'editor.review',
			)
		);
		if ($makeDecision) {
			$decisions = $decisions + array(
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
		}
		return $decisions;
	}

	/**
	 * Define and return editor decisions for the review stage.
	 * If the user cannot make decisions i.e. if it is a recommendOnly user,
	 * there will be no decisions options in the review stage.
	 * @param $context Context
	 * @param $makeDecision boolean If the user can make decisions
	 * @return array
	 */
	static function _externalReviewStageDecisions($context, $makeDecision = true) {
		$paymentManager = Application::getPaymentManager($context);
		$decisions = array();
		if ($makeDecision) {
			$decisions = array(
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
					'paymentType' => $paymentManager->publicationEnabled()?PAYMENT_TYPE_PUBLICATION:null,
					'paymentAmount' => $context->getSetting('publicationFee'),
					'paymentCurrency' => $context->getSetting('currency'),
					'requestPaymentText' => __('payment.requestPublicationFee', array('feeAmount' => $context->getSetting('publicationFee') . ' ' . $context->getSetting('currency'))),
					'waivePaymentText' => __('payment.waive'),
				),
				SUBMISSION_EDITOR_DECISION_DECLINE => array(
					'operation' => 'sendReviewsInReview',
					'name' => 'decline',
					'title' => 'editor.submission.decision.decline',
				),
			);
		}
		return $decisions;
	}

	/**
	 * Define and return editor decisions for the editorial stage.
	 * Currently it does not matter if the user cannot make decisions
	 * i.e. if it is a recommendOnly user for this stage.
	 * @param $makeDecision boolean If the user cannot make decisions
	 * @return array
	 */
	static function _editorialStageDecisions($makeDecision = true) {
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
