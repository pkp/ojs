<?php

/**
 * @file controllers/api/file/ManageFileApiHandler.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManageFileApiHandler
 * @ingroup controllers_api_file
 *
 * @brief Class defining an AJAX API for file manipulation.
 */

// Import the base handler.
import('lib.pkp.controllers.api.file.PKPManageFileApiHandler');
import('lib.pkp.classes.core.JSONMessage');

class ManageFileApiHandler extends PKPManageFileApiHandler {

	/**
	 * Constructor.
	 */
	function ManageFileApiHandler() {
		parent::PKPManageFileApiHandler();
	}

	//
	// Subclassed methods
	//

	/**
	 * indexes the files associated with a submission.
	 * @param $submission Submission
	 * @param $submissionFile SubmissionFile
	 */
	function indexSubmissionFiles($submission, $submissionFile) {
		// update the submission's search index if this submission is published.
		if ($submission->getDatePublished()) {
			import('classes.search.ArticleSearchIndex');
			ArticleSearchIndex::articleFilesChanged($submission);
		}
	}


	/**
	 * logs the deletion event using app-specific logging classes.
	 * @param $request PKPRequest
	 * @param $submission Submission
	 * @param $submissionFile SubmissionFile
	 * @param $user PKPUser
	 */
	function logDeletionEvent($request, $submission, $submissionFile, $user) {
		// log the deletion event.
		import('lib.pkp.classes.log.SubmissionFileLog');
		import('lib.pkp.classes.log.SubmissionFileEventLogEntry'); // constants

		if ($submissionFile->getRevision() > 1) {
			SubmissionFileLog::logEvent($request, $submissionFile, SUBMISSION_LOG_FILE_REVISION_DELETE, 'submission.event.revisionDeleted', array('fileStage' => $submissionFile->getFileStage(), 'sourceFileId' => $submissionFile->getSourceFileId(), 'fileId' => $submissionFile->getFileId(), 'fileRevision' => $submissionFile->getRevision(), 'originalFileName' => $submissionFile->getOriginalFileName(), 'submissionId' => $submissionFile->getSubmissionId(), 'username' => $user->getUsername()));
		} else {
			SubmissionFileLog::logEvent($request, $submissionFile, SUBMISSION_LOG_FILE_DELETE, 'submission.event.fileDeleted', array('fileStage' => $submissionFile->getFileStage(), 'sourceFileId' => $submissionFile->getSourceFileId(), 'fileId' => $submissionFile->getFileId(), 'fileRevision' => $submissionFile->getRevision(), 'originalFileName' => $submissionFile->getOriginalFileName(), 'submissionId' => $submissionFile->getSubmissionId(), 'username' => $user->getUsername()));
		}

		if ($submissionFile->getRevision() == 1 && $submissionFile->getSourceFileId() == null) {
			import('lib.pkp.classes.log.SubmissionLog');
			import('classes.log.SubmissionEventLogEntry'); // constants
			SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_LAST_REVISION_DELETED, 'submission.event.lastRevisionDeleted', array('title' => $submissionFile->getOriginalFileName(), 'submissionId' => $submissionFile->getSubmissionId(), 'username' => $user->getUsername()));
		}

	}
}

?>
