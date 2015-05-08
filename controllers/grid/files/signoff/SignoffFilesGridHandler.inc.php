<?php

/**
 * @file controllers/grid/files/signoff/SignoffFilesGridHandler.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SignoffFilesGridHandler
 * @ingroup controllers_grid_files_signoff
 *
 * @brief Base grid for providing a list of files as categories and the requested signoffs on that file as rows.
 */

// import grid base class
import('lib.pkp.controllers.grid.files.signoff.PKPSignoffFilesGridHandler');


class SignoffFilesGridHandler extends PKPSignoffFilesGridHandler {

	/**
	 * Constructor
	 * @param $stageId int WORKFLOW_STAGE_ID_...
	 * @param $fileStage int SUBMISSION_FILE_...
	 * @param $symbolic string
	 * @param $eventType int
	 * @param $eventType
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 */
	function SignoffFilesGridHandler($stageId, $fileStage, $symbolic, $eventType, $assocType = null, $assocId = null) {
		parent::PKPSignoffFilesGridHandler($stageId, $fileStage, $symbolic, $eventType, $assocType, $assocId);
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {

		// If a representation ID was specified, authorize it.
		if ($request->getUserVar('representationId')) {
			import('lib.pkp.classes.security.authorization.internal.RepresentationRequiredPolicy');
			$this->addPolicy(new RepresentationRequiredPolicy($request, $args));
		}

		return parent::authorize($request, $args, $roleAssignments);
	}

	//
	// Getters and Setters
	//
	/**
	 * Get galley, if any.
	 * @return Galley
	 */
	function getGalley() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_GALLEY);
	}

	/*
	 * return a context-specific instance of the form for this grid.
	* @return AuditorReminderForm
	*/
	function _getAuditorReminderForm() {
		import('controllers.grid.files.fileSignoff.form.AuditorReminderForm');
		$signoff = $this->getAuthorizedContextObject(ASSOC_TYPE_SIGNOFF);
		$submission = $this->getSubmission();
		$galley = $this->getGalley();
		$galleyId = null;
		if (is_a($galley, 'ArticleGalley')) {
			$galleyId = $galley->getId();
		}
		$auditorReminderForm = new AuditorReminderForm($signoff, $submission->getId(), $this->getStageId(), $galleyId);
		return $auditorReminderForm;
	}

	/**
	 * return a context-specific instance of the file auditor form for this grid.
	 * @return FileAuditorForm
	 */
	function _getFileAuditorForm() {
		import('controllers.grid.files.signoff.form.FileAuditorForm');
			$galley = $this->getGalley();
		$galleyId = null;
		if (is_a($galley, 'ArticleGalley')) {
			$galleyId = $galley->getId();
		}
		$auditorForm = new FileAuditorForm($this->getSubmission(), $this->getFileStage(), $this->getStageId(), $this->getSymbolic(), $this->getEventType(), $this->getAssocId(), $galleyId);
		return $auditorForm;
	}
}

?>
