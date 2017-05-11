<?php

/**
 * @file controllers/grid/files/LibraryFileGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LibraryFileGridRow
 * @ingroup controllers_grid_files
 *
 * @brief Handle library file grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

// Link action & modal classes
import('lib.pkp.classes.linkAction.request.AjaxModal');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

class LibraryFileGridRow extends GridRow {
	/** @var int LIBRARY_FILE_TYPE_... */
	var $_fileType;

	/** is the grid row read only **/
	var $_canEdit;

	/** the submission associated with submission library files **/
	var $_submission;

	/**
	 * Constructor
	 */
	function __construct($canEdit = false, $submission = null) {
		$this->_canEdit = $canEdit;
		$this->_submission = $submission;
		parent::__construct();
	}

	//
	// Getters / setters
	//
	/**
	 * Get the file type for this row
	 * @return fileType
	 */
	function getFileType() {
		return $this->_fileType;
	}

	function setFileType($fileType) {
		$this->_fileType = $fileType;
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request, $template = null) {
		parent::initialize($request, $template);

		$this->setFileType($request->getUserVar('fileType'));

		// Is this a new row or an existing row?
		$fileId = $this->getId();

		if (!empty($fileId) && $this->_canEdit) {
			// Actions
			$router = $request->getRouter();
			$actionArgs = array(
				'fileId' => $fileId,
			);

			if ($this->_submission) {
				$actionArgs['submissionId'] = $this->_submission->getId();
			}

			$this->addAction(
				new LinkAction(
					'editFile',
					new AjaxModal(
						$router->url($request, null, null, 'editFile', null, $actionArgs),
						__('grid.action.edit'),
						'modal_edit'
					),
					__('grid.action.edit'),
					'edit'
				)
			);
			$this->addAction(
				new LinkAction(
					'deleteFile',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('common.confirmDelete'), __('common.delete'),
						$router->url($request, null, null, 'deleteFile', null, $actionArgs),
						'modal_delete'
					),
					__('grid.action.delete'),
					'delete'
				)
			);
		}
	}
}

?>
