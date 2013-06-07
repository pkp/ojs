<?php

/**
 * @file controllers/grid/files/proof/GalleyFilesGridHandler.inc.php
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

		$this->setEmptyCategoryRowText('grid.noAuditors');
	}

	//
	// Implement template methods from PKPHandler
	//
	/**
	 * Configure the grid
	 * @param PKPRequest $request
	 */
	function initialize($request) {
		$galley =& $this->getGalley();
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
	 * @see SignoffFilesGridHandler::getRowInstance()
	 */
	function getRowInstance() {
		$row = parent::getRowInstance();
		$row->setRequestArgs($this->getRequestArgs());
		return $row;
	}

	/**
	 * @see GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		return array_merge(
			parent::getRequestArgs(),
			array('articleGalleyId' => $this->getAssocId())
		);
	}
}

?>
