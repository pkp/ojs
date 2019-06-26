<?php

/**
 * @file controllers/grid/articleGalleys/ArticleGalleyGridCellProvider.inc.php
 *
 * Copyright (c) 2016-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyGridCellProvider
 * @ingroup controllers_grid_articleGalleys
 *
 * @brief Base class for a cell provider for article galleys.
 */

import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

class ArticleGalleyGridCellProvider extends DataObjectGridCellProvider {

	/** @var Submission **/
	var $_submission;

	/** @var Publication **/
	var $_publication;

	var $_isEditable;

	/**
	 * Constructor
	 * @param $submission Submission
	 */
	function __construct($submission, $publication, $isEditable) {
		parent::__construct();
		$this->_submission = $submission;
		$this->_publication = $publication;
		$this->_isEditable = $isEditable;
	}

	//
	// Template methods from GridCellProvider
	//
	/**
	 * @copydoc GridCellProvider::getTemplateVarsFromRowColumn()
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
			'publicationId' => $this->_publication->getId(),
		);
	}

	/**
	 * @copydoc GridCellProvider::getCellActions()
	 */
	function getCellActions($request, $row, $column, $position = GRID_ACTION_POSITION_DEFAULT) {
		switch ($column->getId()) {
			case 'label':
				$element = $row->getData();
				if ($element->getRemoteUrl() != '' || !$element->getFileId()) break;

				$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
				import('lib.pkp.classes.submission.SubmissionFile');
				$submissionFile = $submissionFileDao->getLatestRevision(
					$element->getFileId(),
					null,
					$element->getSubmissionId()
				);
				import('lib.pkp.controllers.api.file.linkAction.DownloadFileLinkAction');
				return array(new DownloadFileLinkAction($request, $submissionFile, WORKFLOW_STAGE_ID_PRODUCTION, $element->getLabel()));
		}
		return parent::getCellActions($request, $row, $column, $position);
	}
}


