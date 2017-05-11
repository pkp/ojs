<?php

/**
 * @file controllers/grid/files/submissionDocuments/form/EditLibraryFileForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EditLibraryFileForm
 * @ingroup controllers_grid_files_submissionDocuments_form
 *
 * @brief Form for editing a library file
 */

import('lib.pkp.controllers.grid.files.form.LibraryFileForm');

class EditLibraryFileForm extends LibraryFileForm {
	/** the file being edited, or null for new */
	var $libraryFile;

	/** the id of the submission for this library file */
	var $submissionId;

	/**
	 * Constructor.
	 * @param $contextId int
	 * @param $fileType int LIBRARY_FILE_TYPE_...
	 * @param $fileId int optional
	 */
	function __construct($contextId, $fileId, $submissionId) {
		parent::__construct('controllers/grid/files/submissionDocuments/form/editFileForm.tpl', $contextId);

		$this->submissionId = $submissionId;
		$libraryFileDao = DAORegistry::getDAO('LibraryFileDAO');
		$this->libraryFile = $libraryFileDao->getById($fileId);

		if (!$this->libraryFile || $this->libraryFile->getContextId() !== $this->contextId || $this->libraryFile->getSubmissionId() !== $this->getSubmissionId()) {
			fatalError('Invalid library file!');
		}
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$this->_data = array(
			'submissionId' => $this->libraryFile->getSubmissionId(),
			'libraryFileName' => $this->libraryFile->getName(null), // Localized
			'libraryFile' => $this->libraryFile // For read-only info
		);
	}

	/**
	 * Save name for library file
	 */
	function execute() {
		$this->libraryFile->setName($this->getData('libraryFileName'), null); // Localized
		$this->libraryFile->setType($this->getData('fileType'));

		$libraryFileDao = DAORegistry::getDAO('LibraryFileDAO');
		$libraryFileDao->updateObject($this->libraryFile);
	}

	/**
	 * return the submission ID for this library file.
	 * @return int
	 */
	function getSubmissionId() {
		return $this->submissionId;
	}
}

?>
