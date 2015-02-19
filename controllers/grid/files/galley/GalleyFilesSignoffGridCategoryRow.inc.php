<?php

/**
 * @file controllers/grid/files/signoff/SignoffGridRow.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SignoffGridRow
 * @ingroup controllers_grid_files_signoff
 *
 * @brief A row containing a Signoff as its data.
 */

import('lib.pkp.controllers.grid.files.signoff.SignoffFilesGridCategoryRow');

class GalleyFilesSignoffGridCategoryRow extends SignoffFilesGridCategoryRow {

	var $_galleyId;

	/**
	 * Constructor
	 */
	function GalleyFilesSignoffGridCategoryRow($galleyId, $stageId) {
		parent::SignoffFilesGridCategoryRow($stageId);
		$this->_galleyId = $galleyId;
	}

	//
	// Overridden template methods
	//
	/*
	 * Configure the grid row
	 * @param $request PKPRequest
	 */
	function initialize($request) {
		// Do the default initialization
		parent::initialize($request);

		// Is this a new row or an existing row?
		$fileId = $this->getId();
		if (!empty($fileId) && is_numeric($fileId)) {
			$submissionFile = $this->getData();

			// Add the row actions.
			$actionArgs = array(
				'submissionId' => $submissionFile->getSubmissionId(),
				'fileId' => $submissionFile->getFileId(),
				'articleGalleyId' => $this->_galleyId,
			);

			$router = $request->getRouter();

			$this->addAction(
				new LinkAction(
					'dependentFiles',
					new AjaxModal(
						$router->url($request, null, null, 'dependentFiles', null, $actionArgs),
						__('submission.submit.dependentFiles'),
						'modal_information',
						true
					),
					__('submission.manageDependentFiles'),
					'more_info'
				)
			);
		}
	}
}

?>
