<?php

/**
 * @file controllers/grid/files/galley/GalleyFilesGridHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GalleyFilesGridHandler
 * @ingroup controllers_grid_files_proof
 *
 * @brief Subclass of file editor/auditor grid for proof files.
 */

// import grid signoff files grid base classes
import('controllers.grid.files.signoff.SignoffFilesGridHandler');
import('controllers.grid.files.galley.GalleyFilesSignoffGridCategoryRow');

// Import file class which contains the SUBMISSION_FILE_* constants.
import('lib.pkp.classes.submission.SubmissionFile');

// Import SUBMISSION_EMAIL_* constants.
import('classes.mail.ArticleMailTemplate');

class GalleyFilesGridHandler extends SignoffFilesGridHandler {
	/**
	 * Constructor
	 */
	function GalleyFilesGridHandler() {
		parent::SignoffFilesGridHandler(
			WORKFLOW_STAGE_ID_PRODUCTION,
			SUBMISSION_FILE_PROOF,
			'SIGNOFF_PROOFING',
			SUBMISSION_EMAIL_PROOFREAD_NOTIFY_AUTHOR,
			ASSOC_TYPE_GALLEY
		);

		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_AUTHOR),
			array('dependentFiles')
		);

		$this->setEmptyCategoryRowText('grid.noAuditors');
	}

	function authorize($request, $args, $roleAssignments){

		// If a file ID was specified, authorize it.  dependentFiles requires this.
		// fileId corresponds to the main galley file that these other files depend on.
		if ($request->getUserVar('fileId')) {
			import('classes.security.authorization.SubmissionFileAccessPolicy');
			$this->addPolicy(new SubmissionFileAccessPolicy($request, $args, $roleAssignments, SUBMISSION_FILE_ACCESS_MODIFY));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	//
	// Implement template methods from PKPHandler
	//
	/**
	 * Configure the grid
	 * @param PKPRequest $request
	 */
	function initialize($request) {
		$galley = $this->getGalley();
		$this->setAssocId($galley->getId());

		parent::initialize($request);

		$router = $request->getRouter();

		$this->addAction(
			new LinkAction(
				'viewLibrary',
				new AjaxModal(
					$router->url($request, null, null, 'viewLibrary', null, $this->getRequestArgs()),
					__('grid.action.viewLibrary'),
					'modal_information'
				),
				__('grid.action.viewLibrary'),
				'more_info'
			)
		);

		// Basic grid configuration
		$this->setId('articleGalleyFiles-' . $this->getAssocId());
		$this->setTitle('submission.galleyFiles');
		$this->setInstructions('submission.proofReadingDescription');
	}

	/**
	 * @copydoc SignoffFilesGridHandler::getRowInstance()
	 */
	function getRowInstance() {
		$row = parent::getRowInstance();
		$row->setRequestArgs($this->getRequestArgs());
		return $row;
	}

	/**
	 * @copydoc GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		return array_merge(
			parent::getRequestArgs(),
			array('articleGalleyId' => $this->getAssocId())
		);
	}

	/**
	 * Get the row handler - override the default row handler
	 * @return CopyeditingFilesGridRow
	 */
	function getCategoryRowInstance() {
		$galley = $this->getGalley();
		$row = new GalleyFilesSignoffGridCategoryRow($galley->getId(), $this->getStageId());
		$submission = $this->getSubmission();
		$row->setCellProvider(new SignoffFilesGridCellProvider($submission->getId(), $this->getStageId()));
		$row->addFlag('gridRowStyle', true);
		return $row;
	}

	/**
	 * display the template containing the dependent files grid.
	 * @param array $args
	 * @param PKPRequest $request
	 * @return string
	 */
	function dependentFiles($args, $request) {

		$templateMgr = TemplateManager::getManager($request);
		$submissionFile = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION_FILE);
		if ($submissionFile) {
			$templateMgr->assign('fileId', $submissionFile->getFileId());
			$templateMgr->assign('submissionId', $submissionFile->getSubmissionId());
			return $templateMgr->fetchJson('controllers/grid/files/galley/dependentFiles.tpl');
		}
	}
}

?>
