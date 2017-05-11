<?php
/**
 * @devgroup controllers_wizard_fileUpload_form
 */

/**
 * @file controllers/wizard/fileUpload/form/SupplementaryFileMetadataForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SupplementaryFileMetadataForm
 * @ingroup controllers_wizard_fileUpload_form
 *
 * @brief Form for editing artwork file metadata.
 */

import('lib.pkp.controllers.wizard.fileUpload.form.SubmissionFilesMetadataForm');

class SupplementaryFileMetadataForm extends SubmissionFilesMetadataForm {
	/**
	 * Constructor.
	 * @param $submissionFile SubmissionFile
	 * @param $stageId integer One of the WORKFLOW_STAGE_ID_* constants.
	 * @param $reviewRound ReviewRound (optional) Current review round, if any.
	 */
	function __construct($submissionFile, $stageId, $reviewRound = null) {
		parent::__construct($submissionFile, $stageId, $reviewRound, 'controllers/wizard/fileUpload/form/supplementaryFileMetadataForm.tpl');
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_GRID);
	}


	//
	// Implement template methods from Form
	//
	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array(
			'creator', 'subject', 'description', 'publisher', 'sponsor', 'source', 'language', 'dateCreated',
		));
		parent::readInputData();
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute($args, $request) {
		// Update the submission file from form data.
		$submissionFile = $this->getSubmissionFile();
		$submissionFile->setSubject($this->getData('subject'), null); // Localized
		$submissionFile->setCreator($this->getData('creator'), null); // Localized
		$submissionFile->setDescription($this->getData('description'), null); // Localized
		$submissionFile->setPublisher($this->getData('publisher'), null); // Localized
		$submissionFile->setSponsor($this->getData('sponsor'), null); // Localized
		$submissionFile->setSource($this->getData('source'), null); // Localized
		$submissionFile->setLanguage($this->getData('language'));
		$submissionFile->setDateCreated($this->getData('dateCreated'));

		// Persist the submission file.
		parent::execute($args, $request);
	}
}

?>
