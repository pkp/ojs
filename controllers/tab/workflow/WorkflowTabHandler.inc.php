<?php

/**
 * @file controllers/tab/workflow/WorkflowTabHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowTabHandler
 * @ingroup controllers_tab_workflow
 *
 * @brief Handle AJAX operations for workflow tabs.
 * Note: This class is a skeleton because OJS uses all of the stages provided by
 *  pkp-lib and no others.  Other applications may override specific methods.
 */


import('lib.pkp.controllers.tab.workflow.PKPWorkflowTabHandler');

class WorkflowTabHandler extends PKPWorkflowTabHandler {

	/**
	 * Constructor
	 */
	function WorkflowTabHandler() {
		parent::PKPWorkflowTabHandler();
	}

}

?>
