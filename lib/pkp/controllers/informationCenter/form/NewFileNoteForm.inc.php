<?php

/**
 * @file controllers/informationCenter/form/NewFileNoteForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NewFileNoteForm
 * @ingroup informationCenter_form
 *
 * @brief Form to display and post notes on a file
 */


import('lib.pkp.controllers.informationCenter.form.NewNoteForm');

class NewFileNoteForm extends NewNoteForm {
	/** @var int The ID of the submission file to attach the note to */
	var $fileId;

	/**
	 * Constructor.
	 */
	function __construct($fileId) {
		parent::__construct();

		$this->fileId = $fileId;
	}

	/**
	 * Return the assoc type for this note.
	 * @return int
	 */
	function getAssocType() {
		return ASSOC_TYPE_SUBMISSION_FILE;
	}

	/**
	 * Return the submit note button locale key.
	 * Can be overriden by subclasses.
	 * @return string
	 */
	function getSubmitNoteLocaleKey() {
		return 'informationCenter.addNote';
	}

	/**
	 * Return the assoc ID for this note.
	 * @return int
	 */
	function getAssocId() {
		return $this->fileId;
	}

	/**
	 * @copydoc NewFileNoteForm::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('showEarlierEntries', true);
		return parent::fetch($request);
	}
}

?>
