<?php

/**
 * @file controllers/grid/files/proof/ManageProofFilesGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManageProofFilesGridHandler
 * @ingroup controllers_grid_files_proof
 *
 * @brief Handle the editor's proof files selection grid (selects which files to include)
 */

import('lib.pkp.controllers.grid.files.SelectableSubmissionFileListCategoryGridHandler');

class ManageProofFilesGridHandler extends SelectableSubmissionFileListCategoryGridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		import('lib.pkp.controllers.grid.files.SubmissionFilesCategoryGridDataProvider');
		parent::__construct(
			new SubmissionFilesCategoryGridDataProvider(SUBMISSION_FILE_PROOF),
			WORKFLOW_STAGE_ID_PRODUCTION
		);

		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
			array(
				'fetchGrid', 'fetchCategory', 'fetchRow',
				'addFile', 'downloadFile', 'deleteFile',
				'updateProofFiles',
			)
		);

		// Set the grid title.
		$this->setTitle('submission.pageProofs');
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));

		import('lib.pkp.classes.security.authorization.internal.RepresentationRequiredPolicy');
		$this->addPolicy(new RepresentationRequiredPolicy($request, $args));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Get the grid request parameters.
	 * @return array
	 */
	function getRequestArgs() {
		$representation = $this->getAuthorizedContextObject(ASSOC_TYPE_REPRESENTATION);
		return array_merge(
			parent::getRequestArgs(),
			array('representationId' => $representation->getId())
		);
	}

	//
	// Public handler methods
	//
	/**
	 * Save 'manage proof files' form
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateProofFiles($args, $request) {
		$submission = $this->getSubmission();
		$representation = $this->getAuthorizedContextObject(ASSOC_TYPE_REPRESENTATION);

		import('lib.pkp.controllers.grid.files.proof.form.ManageProofFilesForm');
		$manageProofFilesForm = new ManageProofFilesForm($submission->getId(), $representation->getId());
		$manageProofFilesForm->readInputData();

		if ($manageProofFilesForm->validate()) {
			$manageProofFilesForm->execute(
				$args, $request,
				$this->getGridCategoryDataElements($request, $this->getStageId()),
				SUBMISSION_FILE_PROOF
			);

			// Let the calling grid reload itself
			return DAO::getDataChangedEvent();
		} else {
			return new JSONMessage(false);
		}
	}
}

?>
