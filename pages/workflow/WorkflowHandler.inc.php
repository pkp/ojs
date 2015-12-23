<?php

/**
 * @file pages/workflow/WorkflowHandler.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class WorkflowHandler
 * @ingroup pages_reviewer
 *
 * @brief Handle requests for the submssion workflow.
 */

import('lib.pkp.pages.workflow.PKPWorkflowHandler');

// Access decision actions constants.
import('classes.workflow.EditorDecisionActionsManager');

class WorkflowHandler extends PKPWorkflowHandler {
	/**
	 * Constructor
	 */
	function WorkflowHandler() {
		parent::PKPWorkflowHandler();

		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER, ROLE_ID_ASSISTANT),
			array(
				'access', 'index', 'submission',
				'editorDecisionActions', // Submission & review
				'externalReview', // review
				'editorial',
				'production',
				'submissionHeader',
				'submissionProgressBar',
				'expedite'
			)
		);
	}


	//
	// Public handler methods
	//
	/**
	 * Expedites a submission through the submission process, if the submitter is a manager or editor.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function expedite($args, $request) {

		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		import('controllers.tab.issueEntry.form.IssueEntryPublicationMetadataForm');
		$user = $request->getUser();
		$form = new IssueEntryPublicationMetadataForm($submission->getId(), $user, null, array('expeditedSubmission' => true));
		if ($submission && (int) $request->getUserVar('issueId') > 0) {

			// Process our submitted form in order to create the published article entry.
			$form->readInputData();
			if($form->validate()) {
				$form->execute($request);
				// Create trivial notification in place on the form, and log the event.
				$notificationManager = new NotificationManager();
				$user = $request->getUser();
				import('lib.pkp.classes.log.SubmissionLog');
				import('classes.log.SubmissionEventLogEntry'); // Log consts
				SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_ISSUE_METADATA_UPDATE, 'submission.event.issueMetadataUpdated');
				$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.savedIssueMetadata')));

				// For indexing
				import('classes.search.ArticleSearchIndex');
				$articleSearchIndex = new ArticleSearchIndex();

				// Now, create a galley for this submission.  Assume PDF, and set to 'available'.
				$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
				$articleGalley = $articleGalleyDao->newDataObject();
				$articleGalley->setGalleyType('pdfarticlegalleyplugin');
				$articleGalley->setIsApproved(true);
				$articleGalley->setSubmissionId($submission->getId());
				$articleGalley->setLocale($submission->getLocale());
				$articleGalley->setLabel('PDF');
				$articleGalley->setSeq($articleGalleyDao->getNextGalleySequence($submission->getId()));
				$articleGalleyId = $articleGalleyDao->insertObject($articleGalley);

				// Next, create a galley PROOF file out of the submission file uploaded.
				$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
				import('lib.pkp.classes.submission.SubmissionFile'); // SUBMISSION_FILE_... constants
				$submissionFiles = $submissionFileDao->getLatestRevisions($submission->getId(), SUBMISSION_FILE_SUBMISSION);
				// Watch for a single file, or any PDFs.
				foreach ($submissionFiles as $submissionFile) {
					// test both mime type and file extension in case the mime type isn't correct after uploading.
					if (count($submissionFiles) == 1 || $submissionFile->getFileType() == 'application/pdf' || preg_match('/\.pdf$/', $submissionFile->getOriginalFileName())) {

						// Get the path of the current file because we change the file stage in a bit.
						$currentFilePath = $submissionFile->getFilePath();

						// this will be a new file based on the old one.
						$submissionFile->setFileId(null);
						$submissionFile->setRevision(1);
						$submissionFile->setFileStage(SUBMISSION_FILE_PROOF);
						$submissionFile->setAssocType(ASSOC_TYPE_GALLEY);
						$submissionFile->setAssocId($articleGalleyId);

						$submissionFileDao->insertObject($submissionFile, $currentFilePath);

						// Update file index
						$articleSearchIndex->submissionFileChanged($submission->getId(), SUBMISSION_SEARCH_GALLEY_FILE, $submissionFile->getFileId());
						break;
					}
				}

				// Update index
				$articleSearchIndex->articleMetadataChanged($submission);
				$articleSearchIndex->articleChangesFinished();

				// no errors, clear all notifications for this submission which may have been created during the submission process and close the modal.
				$context = $request->getContext();
				$notificationDao = DAORegistry::getDAO('NotificationDAO');
				$notificationFactory = $notificationDao->deleteByAssoc(
					ASSOC_TYPE_SUBMISSION,
					$submission->getId(),
					null,
					null,
					$context->getId()
				);

				return new JSONMessage(true);
			} else {
				return new JSONMessage(true, $form->fetch($request));
			}
		}
		return new JSONMessage(true, $form->fetch($request));
	}

	//
	// Protected helper methods
	//
	/**
	 * Return the editor assignment notification type based on stage id.
	 * @param $stageId int
	 * @return int
	 */
	protected function getEditorAssignmentNotificationTypeByStageId($stageId) {
		switch ($stageId) {
			case WORKFLOW_STAGE_ID_SUBMISSION:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_SUBMISSION;
			case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EXTERNAL_REVIEW;
			case WORKFLOW_STAGE_ID_EDITING:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_EDITING;
			case WORKFLOW_STAGE_ID_PRODUCTION:
				return NOTIFICATION_TYPE_EDITOR_ASSIGNMENT_PRODUCTION;
		}
		return null;
	}

	/**
	 * @see PKPWorkflowHandler::isSubmissionReady()
	 */
	protected function isSubmissionReady($submission) {
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = $publishedArticleDao->getPublishedArticleByArticleId($submission->getId());
		if ($publishedArticle) {
			return true;
		} else {
			return false;
		}
	}
}

?>
