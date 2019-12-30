<?php

/**
 * @file controllers/tab/workflow/WorkflowTabHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowTabHandler
 * @ingroup controllers_tab_workflow
 *
 * @brief Handle AJAX operations for workflow tabs.
 */

// Import the base Handler.
import('lib.pkp.controllers.tab.workflow.PKPWorkflowTabHandler');

class WorkflowTabHandler extends PKPWorkflowTabHandler {

	/**
	 * @copydoc PKPWorkflowTabHandler::fetchTab
	 */
	function fetchTab($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		switch ($stageId) {
			case WORKFLOW_STAGE_ID_PRODUCTION:
				$errors = [];
				$context = $request->getContext();

				$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
				$submitterAssignments = $stageAssignmentDao->getBySubmissionAndRoleId($submission->getId(), ROLE_ID_AUTHOR);

				while ($assignment = $submitterAssignments->next()) {
					\HookRegistry::call('Publication::testAuthorValidatePublish', array(&$errors, $assignment->getUserId(), $context->getId(), $submission->getId()));
				}

				if (!empty($errors)){
					foreach ($errors as $error) {
						$authorPublishRequirements .= $error . "<br />\n";
					}
					$templateMgr->assign('authorPublishRequirements', $authorPublishRequirements);
				}
				break;
		}
		return parent::fetchTab($args, $request);
	}

	/**
	 * Get all production notification options to be used in the production stage tab.
	 * @param $submissionId int
	 * @return array
	 */
	protected function getProductionNotificationOptions($submissionId) {
		return array(
			NOTIFICATION_LEVEL_NORMAL => array(
				NOTIFICATION_TYPE_VISIT_CATALOG => array(ASSOC_TYPE_SUBMISSION, $submissionId),
				NOTIFICATION_TYPE_ASSIGN_PRODUCTIONUSER => array(ASSOC_TYPE_SUBMISSION, $submissionId),
				NOTIFICATION_TYPE_AWAITING_REPRESENTATIONS => array(ASSOC_TYPE_SUBMISSION, $submissionId),
			),
			NOTIFICATION_LEVEL_TRIVIAL => array()
		);
	}
}


