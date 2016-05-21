<?php

/**
 * @file controllers/grid/queries/ArticleGalleyGridCellProvider.inc.php
 *
 * Copyright (c) 2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyGridCellProvider
 * @ingroup controllers_grid_users_author
 *
 * @brief Base class for a cell provider that can retrieve labels for queries.
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

class ArticleGalleyGridCellProvider extends DataObjectGridCellProvider {

	/** @var Submission **/
	var $_submission;

	/**
	 * Constructor
	 * @param $submission Submission
	 * @param $stageId int
	 * @param $queriesAccessHelper QueriesAccessHelper
	 */
	function ArticleGalleyGridCellProvider($submission) {
		parent::DataObjectGridCellProvider();
		$this->_submission = $submission;
	}

	//
	// Template methods from GridCellProvider
	//
	/**
	 * Extracts variables for a given column from a data element
	 * so that they may be assigned to template before rendering.
	 * @param $row GridRow
	 * @param $column GridColumn
	 * @return array
	 */
	function getTemplateVarsFromRowColumn($row, $column) {
		$element = $row->getData();
		$columnId = $column->getId();
		assert(is_a($element, 'DataObject') && !empty($columnId));

		switch ($columnId) {
			case 'label':
				return array(
					'label' => ($element->getRemoteUrl()=='' && $element->getFileId())?'':$element->getLabel()
				);
				break;
			default: assert(false);
		}
		return parent::getTemplateVarsFromRowColumn($row, $column);
	}

	/**
	 * Get request arguments.
	 * @param $row GridRow
	 * @return array
	 */
	function getRequestArgs($row) {
		return array(
			'submissionId' => $this->_submission->getId(),
		);
	}

	/**
	 * @copydoc GridCellProvider::getCellActions()
	 */
	function getCellActions($request, $row, $column) {
		switch ($column->getId()) {
			case 'label':
				$element = $row->getData();
				if ($element->getRemoteUrl() != '' || !$element->getFileId()) break;

				$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
				import('lib.pkp.classes.submission.SubmissionFile');
				$submissionFiles = $submissionFileDao->getLatestRevisionsByAssocId(
					ASSOC_TYPE_REPRESENTATION, $element->getId(),
					$this->_submission->getId(),
					SUBMISSION_FILE_PROOF
				);
				import('lib.pkp.controllers.api.file.linkAction.DownloadFileLinkAction');
				$actions = array();
				foreach ($submissionFiles as $submissionFile) {
					$actions[] = new DownloadFileLinkAction($request, $submissionFile, $request->getUserVar('stageId'), $element->getLabel());
				}
				return $actions;
		}
		return parent::getCellActions($request, $row, $column);
	}
}

?>
