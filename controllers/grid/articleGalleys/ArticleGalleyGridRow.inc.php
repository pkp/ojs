<?php

/**
 * @file controllers/grid/articleGalleys/ArticleGalleyGridRow.inc.php
 *
 * Copyright (c) 2016-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyGridRow
 * @ingroup controllers_grid_articleGalleys
 *
 * @brief Representation of an article galley grid row.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class ArticleGalleyGridRow extends GridRow {
	/** @var Submission **/
	var $_submission;

	/** @var boolean */
	var $_isEditable;

	/**
	 * Constructor
	 * @param $submission Submission
	 * @param $isEditable boolean
	 */
	function __construct($submission, $isEditable) {
		$this->_submission = $submission;
		$this->_isEditable = $isEditable;

		parent::__construct();
	}

	//
	// Overridden methods from GridRow
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request, $template = null) {
		// Do the default initialization
		parent::initialize($request, $template);

		// Is this a new row or an existing row?
		$rowId = $this->getId();
		if (!empty($rowId) && is_numeric($rowId)) {
			// Only add row actions if this is an existing row
			$router = $request->getRouter();
			$actionArgs = $this->getRequestArgs();
			$actionArgs['representationId'] = $rowId;

			if ($this->_isEditable) {
				// Add row-level actions
				import('lib.pkp.classes.linkAction.request.AjaxModal');
				$this->addAction(new LinkAction(
					'editGalley',
					new AjaxModal(
						$router->url($request, null, null, 'editGalley', null, $actionArgs),
						__('submission.layout.editGalley'),
						'modal_edit'
					),
					__('grid.action.edit'),
					'edit'
				));

				$galley = $this->getData();
				if ($galley->getRemoteUrl() == '') {
					import('lib.pkp.controllers.api.file.linkAction.AddFileLinkAction');
					import('lib.pkp.classes.submission.SubmissionFile'); // Constants
					$this->addAction(new AddFileLinkAction(
						$request, $this->getSubmission()->getId(), WORKFLOW_STAGE_ID_PRODUCTION,
						array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT),
						SUBMISSION_FILE_PROOF, ASSOC_TYPE_REPRESENTATION, $rowId,
						null, $galley->getFileId()
					));
				}

				import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
				$this->addAction(new LinkAction(
					'deleteGalley',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('common.confirmDelete'),
						__('grid.action.delete'),
						$router->url($request, null, null, 'deleteGalley', null, $actionArgs), 'modal_delete'),
					__('grid.action.delete'),
					'delete'
				));
			}
		}
	}

	/**
	 * Get the submission for this row (already authorized)
	 * @return Submission
	 */
	function getSubmission() {
		return $this->_submission;
	}

	/**
	 * Get the base arguments that will identify the data in the grid.
	 * @return array
	 */
	function getRequestArgs() {
		return array(
			'submissionId' => $this->getSubmission()->getId(),
			'submissionVersion' => $this->getSubmission()->getSubmissionVersion(),
		);
	}
}


