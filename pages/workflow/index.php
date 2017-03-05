<?php

/**
 * @defgroup pages_workflow Workflow Pages
 */

/**
 * @file pages/workflow/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_workflow
 * @brief Handle requests for workflow functions.
 *
 */

switch ($op) {
	case 'access':
	case 'index':
	case 'submission':
	case 'externalReview':
	case 'editorial':
	case 'production':
	case 'editorDecisionActions':
	case 'submissionHeader':
	case 'submissionProgressBar':
		define('HANDLER_CLASS', 'WorkflowHandler');
		import('pages.workflow.WorkflowHandler');
		break;
}

?>
