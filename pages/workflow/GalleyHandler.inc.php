<?php

/**
 * @file pages/workflow/GalleyHandler.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GalleyHandler
 * @ingroup controllers_template_workflow
 *
 * @brief galley sub-page handler
 */

import('classes.handler.Handler');

// import UI base classes
import('lib.pkp.classes.linkAction.LinkAction');
import('lib.pkp.classes.linkAction.request.AjaxModal');

class GalleyHandler extends Handler {
	/**
	 * Constructor
	 */
	function GalleyHandler() {
		parent::Handler();

		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT),
			array('fetchRepresentation')
		);
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		// Get the galley Policy
		import('classes.security.authorization.GalleyRequiredPolicy');
		$galleyPolicy = new GalleyRequiredPolicy($request, $args);

		// Get the workflow stage policy
		import('classes.security.authorization.WorkflowStageAccessPolicy');
		$stagePolicy = new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', WORKFLOW_STAGE_ID_PRODUCTION);

		// Add the Galley policy to the stage policy.
		$stagePolicy->addPolicy($galleyPolicy);

		// Add the augmented policy to the handler.
		$this->addPolicy($stagePolicy);
		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Public operations
	//
	/**
	 * Display the galley template (grid + actions).
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function fetchRepresentation($args, $request) {
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_APP_EDITOR);
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'submission' => $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION),
			'stageId' => $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE),
			'representation' => $this->getAuthorizedContextObject(ASSOC_TYPE_REPRESENTATION)
		));
		return $templateMgr->fetchJson('controllers/tab/workflow/galley.tpl');
	}
}

?>
