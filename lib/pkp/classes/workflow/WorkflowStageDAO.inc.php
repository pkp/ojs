<?php

/**
 * @file classes/workflow/WorkflowStageDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowStageDAO
 * @ingroup workflow
 *
 * @brief class for operations involving the workflow stages.
 *
 */

class WorkflowStageDAO extends DAO {

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Convert a stage id into a stage path
	 * @param $stageId integer
	 * @return string|null
	 */
	static function getPathFromId($stageId) {
		static $stageMapping = array(
			WORKFLOW_STAGE_ID_SUBMISSION => WORKFLOW_STAGE_PATH_SUBMISSION,
			WORKFLOW_STAGE_ID_INTERNAL_REVIEW => WORKFLOW_STAGE_PATH_INTERNAL_REVIEW,
			WORKFLOW_STAGE_ID_EXTERNAL_REVIEW => WORKFLOW_STAGE_PATH_EXTERNAL_REVIEW,
			WORKFLOW_STAGE_ID_EDITING => WORKFLOW_STAGE_PATH_EDITING,
			WORKFLOW_STAGE_ID_PRODUCTION => WORKFLOW_STAGE_PATH_PRODUCTION
		);
		if (isset($stageMapping[$stageId])) {
			return $stageMapping[$stageId];
		}
		return null;
	}

	/**
	 * Convert a stage path into a stage id
	 * @param $stagePath string
	 * @return integer|null
	 */
	static function getIdFromPath($stagePath) {
		static $stageMapping = array(
			WORKFLOW_STAGE_PATH_SUBMISSION => WORKFLOW_STAGE_ID_SUBMISSION,
			WORKFLOW_STAGE_PATH_INTERNAL_REVIEW => WORKFLOW_STAGE_ID_INTERNAL_REVIEW,
			WORKFLOW_STAGE_PATH_EXTERNAL_REVIEW => WORKFLOW_STAGE_ID_EXTERNAL_REVIEW,
			WORKFLOW_STAGE_PATH_EDITING => WORKFLOW_STAGE_ID_EDITING,
			WORKFLOW_STAGE_PATH_PRODUCTION => WORKFLOW_STAGE_ID_PRODUCTION
		);
		if (isset($stageMapping[$stagePath])) {
			return $stageMapping[$stagePath];
		}
		return null;
	}

	/**
	 * Convert a stage id into a stage translation key
	 * @param $stageId integer
	 * @return string|null
	 */
	static function getTranslationKeyFromId($stageId) {
		$stageMapping = self::getWorkflowStageTranslationKeys();

		assert(isset($stageMapping[$stageId]));
		return $stageMapping[$stageId];
	}

	/**
	 * Return a mapping of workflow stages and its translation keys.
	 * @return array
	 */
	static function getWorkflowStageTranslationKeys() {
		$applicationStages = Application::getApplicationStages();
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_SUBMISSION);
		static $stageMapping = array(
			WORKFLOW_STAGE_ID_SUBMISSION => 'submission.submission',
			WORKFLOW_STAGE_ID_INTERNAL_REVIEW => 'workflow.review.internalReview',
			WORKFLOW_STAGE_ID_EXTERNAL_REVIEW => 'workflow.review.externalReview',
			WORKFLOW_STAGE_ID_EDITING => 'submission.editorial',
			WORKFLOW_STAGE_ID_PRODUCTION => 'submission.production'
		);

		return array_intersect_key($stageMapping, array_flip($applicationStages));
	}

	/**
	 * Return a mapping of workflow stages, its translation keys and
	 * paths.
	 * @return array
	 */
	static function getWorkflowStageKeysAndPaths() {
		$workflowStages = self::getWorkflowStageTranslationKeys();
		$stageMapping = array();
		foreach ($workflowStages as $stageId => $translationKey) {
			$stageMapping[$stageId] = array(
				'id' => $stageId,
				'translationKey' => $translationKey,
				'path' => self::getPathFromId($stageId)
			);
		}

		return $stageMapping;
	}

	/**
	 * Returns an array containing data for rendering the stage workflow tabs
	 * for a submission.
	 * @param $submission Submission
	 * @param $stagesWithDecisions array
	 * @param $stageNotifications array
	 * @return array
	 */
	function getStageStatusesBySubmission($submission, $stagesWithDecisions, $stageNotifications) {

		$currentStageId = $submission->getStageId();
		$workflowStages = self::getWorkflowStageKeysAndPaths();

		foreach ($workflowStages as $stageId => $stageData) {

			$foundState = false;
			// If we have not found a state, and the current stage being examined is below the current submission stage, and there have been
			// decisions for this stage, but no notifications outstanding, mark it as complete.
			if (!$foundState && $stageId <= $currentStageId && (in_array($stageId, $stagesWithDecisions) || $stageId == WORKFLOW_STAGE_ID_PRODUCTION) && !$stageNotifications[$stageId]) {
				$workflowStages[$currentStageId]['statusKey'] = 'submission.complete';
			}

			// If this is an old stage with no notifications, this was a skiped/not initiated stage.
			if (!$foundState && $stageId < $currentStageId && !$stageNotifications[$stageId]) {
				$foundState = true;
				// Those are stages not initiated, that were skipped, like review stages.
			}

			// Finally, if this stage has outstanding notifications, or has no decision yet, mark it as initiated.
			if (!$foundState && $stageId <= $currentStageId && ( !in_array($stageId, $stagesWithDecisions) || $stageNotifications[$stageId])) {
				$workflowStages[$currentStageId]['statusKey'] = 'submission.initiated';
				$foundState = true;
			}
		}

		return $workflowStages;

	}
}
?>
