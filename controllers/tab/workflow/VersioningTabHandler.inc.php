<?php

/**
 * @file controllers/tab/workflow/VersioningTabHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class VersioningTabHandler
 * @ingroup controllers_tab_workflow
 *
 * @brief Handle AJAX operations for version tabs on production stages workflow pages.
 */

import('classes.handler.Handler');

// Import the base class.
import('lib.pkp.classes.controllers.tab.workflow.PKPVersioningTabHandler');

class VersioningTabHandler extends PKPVersioningTabHandler {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT),
			array('versioning', 'newVersion')
		);
	}

	//
	// Extended methods from Handler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {

		$stageId = (int) $request->getUserVar('stageId'); // This is validated in WorkflowStageAccessPolicy.

		import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy');
		$this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * create new submission version
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	//function newVersion($args, $request){
	//  return parent::newVersion($args, $request);
	//}

	/**
	 * Handle version info (tab content).
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function versioning($args, $request) {

		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);

		// Retrieve the authorized submission, stage id and submission version.
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);

		$publishedSubmissionDao = Application::getPublishedSubmissionDAO(); /** @var $publishedSubmissionDao PublishedArticleDAO */

		// Create schedule for publication link action.
		$dispatcher = $request->getDispatcher();
		import('lib.pkp.classes.linkAction.request.AjaxModal');

		if ($submission->getSubmissionVersion() != $submission->getCurrentSubmissionVersion()) {
			$linkActionPublicationLabel = 'editor.article.showPublicationVersion';
		} else {
			if ($submission->getSubmissionVersion() == $publishedSubmissionDao->getCurrentPublishedSubmissionVersion($submission->getId())){
				$linkActionPublicationLabel = 'editor.article.schedulePublication';
			} else {
				$linkActionPublicationLabel = 'editor.article.publishVersion';
			}
		}

		if ($submission->getSubmissionVersion() != $submission->getCurrentSubmissionVersion()) {
			$linkActionMetadataLabel = 'editor.article.viewMetadata';
		} else {
			$linkActionMetadataLabel = 'editor.article.editMetadata';
		}

		$schedulePublicationLinkAction = new LinkAction(
			'schedulePublication',
			new AjaxModal(
				$dispatcher->url(
					$request, ROUTE_COMPONENT, null,
					'tab.issueEntry.IssueEntryTabHandler',
					'publicationMetadata', null,
					array('submissionId' => $submission->getId(), 'stageId' => $stageId, 'submissionVersion' => $submission->getSubmissionVersion())
				),
				__($linkActionPublicationLabel)
			),
			__($linkActionPublicationLabel)
		);

		$templateMgr->assign('schedulePublicationLinkAction', $schedulePublicationLinkAction);

		// Create edit metadata link action.
		$dispatcher = $request->getDispatcher();
		import('lib.pkp.classes.linkAction.request.AjaxModal');

		import('controllers.modals.submissionMetadata.linkAction.SubmissionEntryLinkAction');
		$templateMgr->assign(
			'editMetadataLinkAction',
			new SubmissionEntryLinkAction($request, $submission->getId(), $stageId, null, 'information', $submission->getSubmissionVersion())
		);

		$editVersionMetadataLinkAction = new LinkAction(
		  'editMetadata',
		  new AjaxModal(
		    $dispatcher->url(
		      $request, ROUTE_COMPONENT, null,
		      'modals.submissionMetadata.IssueEntryHandler',
		      'fetch', null,
		      array('submissionId' => $submission->getId(), 'stageId' => $stageId, 'submissionVersion' => $submission->getSubmissionVersion())
		    ),
		    __($linkActionMetadataLabel)
		  ),
		  __($linkActionMetadataLabel)
		);
		$templateMgr->assign('editVersionMetadataLinkAction', $editVersionMetadataLinkAction);

		return parent::versioning($args, $request);
	}

}
