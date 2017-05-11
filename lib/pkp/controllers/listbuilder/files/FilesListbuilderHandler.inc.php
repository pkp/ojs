<?php

/**
 * @file controllers/listbuilder/files/FilesListbuilderHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilesListbuilderHandler
 * @ingroup listbuilder
 *
 * @brief Base class for selecting files to add a user to.
 */

import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');

class FilesListbuilderHandler extends ListbuilderHandler {

	/** @var int|null File stage **/
	var $_fileStage;

	/**
	 * Constructor
	 * @param $fileStage int File stage (or null for any)
	 */
	function __construct($fileStage = null) {
		parent::__construct();

		$this->_fileStage = $fileStage;

		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT),
			array('fetch', 'fetchRow', 'fetchOptions')
		);
	}

	/**
	 * Get file stage.
	 * @return int|null
	 */
	function getFileStage() {
		return $this->_fileStage;
	}


	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments, $stageId = null) {
		import('lib.pkp.classes.security.authorization.WorkflowStageAccessPolicy'); // context-specific.
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy'); // context-specific.
		if ($stageId !== null) $this->addPolicy(new WorkflowStageAccessPolicy($request, $args, $roleAssignments, 'submissionId', $stageId), true);
		else $this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Configure the grid
	 * @param PKPRequest $request
	 */
	function initialize($request) {
		parent::initialize($request);

		// Basic configuration
		$this->setSourceType(LISTBUILDER_SOURCE_TYPE_SELECT);
		$this->setSaveType(LISTBUILDER_SAVE_TYPE_EXTERNAL);
		$this->setSaveFieldName('files');

		// Add the file column
		$itemColumn = new ListbuilderGridColumn($this, 'name', 'common.name', null, null, null, array('anyhtml' => true));
		import('lib.pkp.controllers.listbuilder.files.FileListbuilderGridCellProvider');
		$itemColumn->setCellProvider(new FileListbuilderGridCellProvider());
		$this->addColumn($itemColumn);
	}


	//
	// Public methods
	//

	/**
	 * Load possible items to populate drop-down list with.
	 * @param $submissionFiles Array Submission files of this submission.
	 * @return Array
	 */
	function getOptions($submissionFiles) {
		$itemList = array();
		foreach ($submissionFiles as $submissionFile) {
			$itemList[$submissionFile->getFileId()] = $submissionFile->getFileId() . '-' . $submissionFile->getRevision() . ' ' . $submissionFile->getFileLabel();
		}
		return array($itemList);
	}


	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridHandler::getRequestArgs()
	 */
	function getRequestArgs() {
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$args = parent::getRequestArgs();
		$args['submissionId'] = $submission->getId();
		return $args;
	}

	/**
	 * @copydoc PKPHandler::setupTemplate()
	 */
	function setupTemplate($request) {
		parent::setupTemplate($request);

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION);
	}

	/**
	 * @copydoc GridHandler::getRowDataElement()
	 */
	protected function getRowDataElement($request, &$rowId) {
		// fallback on the parent if a rowId is found
		if (!empty($rowId)) {
			return parent::getRowDataElement($request, $rowId);
		}

		// Otherwise return from the newRowId
		$newRowId = $this->getNewRowId($request);
		$fileId = (int) $newRowId['name'];
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		import('lib.pkp.classes.submission.SubmissionFile'); // Bring in const
		$submissionFiles = $submissionFileDao->getLatestRevisions($submission->getId(), $this->getFileStage());
		foreach ($submissionFiles as $submissionFile) {
			if ($submissionFile->getFileId() == $fileId) {
				return $submissionFile;
			}
		}
		return null;
	}
}

?>
