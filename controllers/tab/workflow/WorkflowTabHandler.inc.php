<?php

/**
 * @file controllers/tab/workflow/WorkflowTabHandler.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
	 * Constructor
	 */
	function WorkflowTabHandler() {
		parent::PKPWorkflowTabHandler();
	}

	/**
	 * Get all production notification options to be used in the production stage tab.
	 * @param $submissionId int
	 * @return array
	 */
	protected function getProductionNotificationOptions($submissionId) {
		return array(
			NOTIFICATION_LEVEL_NORMAL => array(
				NOTIFICATION_TYPE_VISIT_CATALOG => array(ASSOC_TYPE_SUBMISSION, $submissionId)
			),
			NOTIFICATION_LEVEL_TRIVIAL => array()
		);
	}
}

?>
