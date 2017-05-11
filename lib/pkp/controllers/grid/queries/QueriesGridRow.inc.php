<?php

/**
 * @file controllers/grid/queries/QueriesGridRow.inc.php
 *
 * Copyright (c) 2016-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueriesGridRow
 * @ingroup controllers_grid_queries
 *
 * @brief Base class for query grid row definition
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class QueriesGridRow extends GridRow {
	/** @var Submission **/
	var $_submission;

	/** @var int **/
	var $_stageId;

	/** @var QueriesAccessHelper */

	/**
	 * Constructor
	 * @param $submission Submission
	 * @param $stageId int
	 * @param $queriesAccessHelper QueriesAccessHelper
	 */
	function __construct($submission, $stageId, $queriesAccessHelper) {
		$this->_submission = $submission;
		$this->_stageId = $stageId;
		$this->_queriesAccessHelper = $queriesAccessHelper;

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

		// Retrieve the submission from the request
		$submission = $this->getSubmission();

		// Is this a new row or an existing row?
		$rowId = $this->getId();
		if (!empty($rowId) && is_numeric($rowId)) {
			// Only add row actions if this is an existing row
			$router = $request->getRouter();
			$actionArgs = $this->getRequestArgs();
			$actionArgs['queryId'] = $rowId;

			// Add row-level actions
			if ($this->_queriesAccessHelper->getCanEdit($rowId)) {
				import('lib.pkp.classes.linkAction.request.AjaxModal');
				$this->addAction(
					new LinkAction(
						'editQuery',
						new AjaxModal(
							$router->url($request, null, null, 'editQuery', null, $actionArgs),
							__('grid.action.updateQuery'),
							'modal_edit'
						),
						__('grid.action.edit'),
						'edit'
					)
				);
			}

			if ($this->_queriesAccessHelper->getCanDelete($rowId)) {
				import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
				$this->addAction(
					new LinkAction(
						'deleteQuery',
						new RemoteActionConfirmationModal(
							$request->getSession(),
							__('common.confirmDelete'),
							__('grid.action.delete'),
							$router->url($request, null, null, 'deleteQuery', null, $actionArgs), 'modal_delete'),
						__('grid.action.delete'),
						'delete')
				);
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
	 * Get the stageId
	 * @return int
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get the base arguments that will identify the data in the grid.
	 * @return array
	 */
	function getRequestArgs() {
		$submission = $this->getSubmission();
		return array(
			'submissionId' => $submission->getId(),
			'stageId' => $this->getStageId(),
		);
	}
}

?>
