<?php

/**
 * @file controllers/tab/workflow/VersioningTabHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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
	 * create new submission revision
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function newVersion($args, $request){
		$submissionId = (int)$request->getUserVar('submissionId');
		$submissionDao = Application::getSubmissionDAO();
		$oldVersion = $submissionDao->getLatestRevisionId($submissionId);

		// get galleys of old version
		import('classes.article.ArticleGalley');
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$articleGalleys = $articleGalleyDao->getBySubmissionId($submissionId, null, $oldVersion);
		$galleys = $articleGalleys->toArray();

		$newVersion = $oldVersion+1;

		// create new galley versions
		foreach($galleys as $galley) {
			// copy file and link copy to galley version
			if($galley->getFile()){
				$context = $request->getContext();
				$oldFile = $galley->getFile();
				$fileStage = $oldFile->getFileStage();
				$newFileId = $this->copyFile($context, $oldFile, $fileStage);
				$articleGalleyDao->addFile($galley->getId(), $newFileId, $newVersion);
			}
		}

		return parent::newVersion($args, $request);
	}

	/**
	 * Handle version info (tab content).
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function versioning($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);

		// Retrieve the authorized submission, stage id and submission revision.
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$submissionRevision = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION_REVISION);

		// Create schedule for publication link action.
		$dispatcher = $request->getDispatcher();
		import('lib.pkp.classes.linkAction.request.AjaxModal');

		if($submissionRevision == 1){
			$linkActionLabel = 'editor.article.schedulePublication';
		}else{
			$linkActionLabel = 'editor.article.publishVersion';
		}

		$schedulePublicationLinkAction = new LinkAction(
			'schedulePublication',
			new AjaxModal(
				$dispatcher->url(
					$request, ROUTE_COMPONENT, null,
					'tab.issueEntry.IssueEntryTabHandler',
					'publicationMetadata', null,
					array('submissionId' => $submission->getId(), 'stageId' => $stageId, 'submissionRevision' => $submissionRevision)
				),
				__('submission.issueEntry.publicationMetadata')
			),
			__($linkActionLabel)
		);

		$templateMgr->assign('schedulePublicationLinkAction', $schedulePublicationLinkAction);

		// Create edit metadata link action.
		$dispatcher = $request->getDispatcher();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$editMetadataLinkAction = new LinkAction(
			'editMetadata',
			new AjaxModal(
				$dispatcher->url(
					$request, ROUTE_COMPONENT, null,
					'modals.submissionMetadata.IssueEntryHandler',
					'fetch', null,
					array('submissionId' => $submission->getId(), 'stageId' => $stageId, 'submissionRevision' => $submissionRevision)
				),
				__('submission.issueEntry.submissionMetadata')
			),
			__('submission.production.editMetadata')
		);
		$templateMgr->assign('editMetadataLinkAction', $editMetadataLinkAction);

		return parent::versioning($args, $request);
	}

}

?>
