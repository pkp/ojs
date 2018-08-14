<?php

/**
 * @file controllers/tab/issueEntry/IssueEntryTabHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueEntryTabHandler
 * @ingroup controllers_tab_catalogEntry
 *
 * @brief Handle AJAX operations for tabs on the submission issue management page.
 */

// Import the base Handler.
import('lib.pkp.controllers.tab.publicationEntry.PublicationEntryTabHandler');

class IssueEntryTabHandler extends PublicationEntryTabHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
			array(
				'publicationMetadata', 'savePublicationMetadataForm',
				'identifiers', 'clearPubId', 'updateIdentifiers',
				'assignPubIds', 'uploadCoverImage', 'deleteCoverImage',
			)
		);
	}


	//
	// Public handler methods
	//
	/**
	 * An action to upload an article cover image.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function uploadCoverImage($args, $request) {
		$user = $request->getUser();

		import('lib.pkp.classes.file.TemporaryFileManager');
		$temporaryFileManager = new TemporaryFileManager();
		$temporaryFile = $temporaryFileManager->handleUpload('uploadedFile', $user->getId());
		if ($temporaryFile) {
			$json = new JSONMessage(true);
			$json->setAdditionalAttributes(array(
				'temporaryFileId' => $temporaryFile->getId()
			));
			return $json;
		} else {
			return new JSONMessage(false, __('common.uploadFailed'));
		}
	}

	/**
	 * An action to delete an article cover image.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	 function deleteCoverImage($args, $request) {
		assert(!empty($args['coverImage']) && !empty($args['submissionId']));

		$submission = $this->getSubmission();
		$submissionDao = Application::getSubmissionDAO();
		$file = $args['coverImage'];

		// Remove cover image and alt text from article settings
		$locale = AppLocale::getLocale();
		$submission->setCoverImage('', $locale);
		$submission->setCoverImageAltText('', $locale);

		$submissionDao->updateObject($submission);

		// Remove the file
		$publicFileManager = new PublicFileManager();
		if ($publicFileManager->removeJournalFile($submission->getJournalId(), $file)) {
			$json = new JSONMessage(true);
			$json->setEvent('fileDeleted');
			return $json;
		} else {
			return new JSONMessage(false, __('editor.article.removeCoverImageFileNotFound'));
		}
	}

	/**
	 * Show the publication metadata form when scheduling for publication.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function publicationMetadata($args, $request) {
		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		$user = $request->getUser();
		import('controllers.tab.issueEntry.form.IssueEntryPublicationMetadataForm');
		$issueEntryPublicationMetadataForm = new IssueEntryPublicationMetadataForm($submission->getId(), $user->getId(), $stageId);
		$issueEntryPublicationMetadataForm->initData();
		return new JSONMessage(true, $issueEntryPublicationMetadataForm->fetch($request));
	}

	/**
	 * Save publication metadata form when scheduling for publication.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function savePublicationMetadataForm($args, $request) {
		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		$user = $request->getUser();

		import('controllers.tab.issueEntry.form.IssueEntryPublicationMetadataForm');
		$form = new IssueEntryPublicationMetadataForm($submission->getId(), $user->getId(), $stageId);
		$form->readInputData();
		if($form->validate()) {
			$form->execute();
			// Log the event
			import('lib.pkp.classes.log.SubmissionLog');
			import('classes.log.SubmissionEventLogEntry'); // Log consts
			SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_ISSUE_METADATA_UPDATE, 'submission.event.issueMetadataUpdated');
			// Create trivial notification in place on the form
			$notificationManager = new NotificationManager();
			$notificationKey = 'notification.savedIssueMetadata';
			$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __($notificationKey)));
			$notificationManager->updateNotification(
				$request,
				array(NOTIFICATION_TYPE_PUBLICATION_SCHEDULED),
				null,
				ASSOC_TYPE_SUBMISSION,
				$submission->getId()
			);
			// Display assign public identifiers form
			$assignPubIds = false;
			$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
			foreach ($pubIdPlugins as $pubIdPlugin) {
				if ($pubIdPlugin->isObjectTypeEnabled('Submission', $submission->getContextId())) {
					$assignPubIds = true;
					break;
				}
			}
			if ($assignPubIds) {
				import('controllers.grid.pubIds.form.AssignPublicIdentifiersForm');
				$formTemplate = $this->getAssignPublicIdentifiersFormTemplate();
				$formParams = array('stageId' => $stageId);
				$assignPublicIdentifiersForm = new AssignPublicIdentifiersForm($formTemplate, $submission, true, '', $formParams);
				$assignPublicIdentifiersForm->initData();
				$json = new JSONMessage(true, $assignPublicIdentifiersForm->fetch($request));
			} else {
				$json = new JSONMessage();
			}
			$json->setEvent('dataChanged');
		} else {
			$json = new JSONMessage(true, $form->fetch($request));
		}
		return $json;
	}

	/**
	 * Assign submission public identifiers when scheduling for publication.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function assignPubIds($args, $request) {
		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		import('controllers.grid.pubIds.form.AssignPublicIdentifiersForm');
		$formTemplate = $this->getAssignPublicIdentifiersFormTemplate();
		$formParams = array('stageId' => $stageId);
		$assignPublicIdentifiersForm = new AssignPublicIdentifiersForm($formTemplate, $submission, true, '', $formParams);
		// Assign pub ids
		$assignPublicIdentifiersForm->readInputData();
		$assignPublicIdentifiersForm->execute(true);
		return new JSONMessage();
	}

	/**
	 * Edit article pub ids
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function identifiers($args, $request) {
		import('controllers.tab.pubIds.form.PublicIdentifiersForm');
		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		$identifiersForm = new PublicIdentifiersForm($submission, $stageId, array('displayedInContainer' => true));
		$identifiersForm->initData();
		return new JSONMessage(true, $identifiersForm->fetch($request));
	}

	/**
	 * Clear article pub id.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function clearPubId($args, $request) {
		if (!$request->checkCSRF()) return new JSONMessage(false);

		import('controllers.tab.pubIds.form.PublicIdentifiersForm');
		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		$identifiersForm = new PublicIdentifiersForm($submission, $stageId, array('displayedInContainer' => true));
		$identifiersForm->clearPubId($request->getUserVar('pubIdPlugIn'));
		$json = new JSONMessage(true);
		$json->setEvent('containerReloadRequested', array(
			'tabsUrl' => $request->getRouter()->getDispatcher()->url(
				$request,
				ROUTE_COMPONENT,
				null,
				'modals.submissionMetadata.IssueEntryHandler',
				'fetch',
				null,
				array(
					'submissionId' => $submission->getId(),
					'stageId' => $stageId
				)
			)
		));
		return $json;
	}

	/**
	 * Update article pub ids.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updateIdentifiers($args, $request) {
		import('controllers.tab.pubIds.form.PublicIdentifiersForm');
		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		$form = new PublicIdentifiersForm($submission, $stageId, array('displayedInContainer' => true));
		$form->readInputData();
		if ($form->validate()) {
			$form->execute();
			$json = new JSONMessage();
			import('lib.pkp.classes.log.SubmissionLog');
			import('classes.log.SubmissionEventLogEntry'); // Log consts
			SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_ISSUE_METADATA_UPDATE, 'submission.event.publicIdentifiers');
			if ($request->getUserVar('displayedInContainer')) {
				$router = $request->getRouter();
				$dispatcher = $router->getDispatcher();
				$url = $dispatcher->url($request, ROUTE_COMPONENT, null, $this->_getHandlerClassPath(), 'fetch', null, array('submissionId' => $submission->getId(), 'stageId' => $stageId, 'tabPos' => $this->getTabPosition(), 'hideHelp' => true));
				$json->setAdditionalAttributes(array('reloadContainer' => true, 'tabsUrl' => $url));
				$json->setContent(true); // prevents modal closure
			}
			return $json;
		} else {
			return new JSONMessage(true, $form->fetch($request));
		}
	}

	/**
	 * Get the template for the assign public identifiers form.
	 * @return string
	 */
	function getAssignPublicIdentifiersFormTemplate() {
		return 'controllers/grid/pubIds/form/assignPublicIdentifiersForm.tpl';
	}

	/**
	 * Returns an instance of the form used for reviewing a submission's 'submission' metadata.
	 * @copydoc PublicationEntryTabHandler::_getPublicationEntrySubmissionReviewForm()
	 * @return PKPForm
	 */
	function _getPublicationEntrySubmissionReviewForm() {

		$submission = $this->getSubmission();
		import('controllers.modals.submissionMetadata.form.IssueEntrySubmissionReviewForm');
		return new IssueEntrySubmissionReviewForm($submission->getId(), $this->getStageId(), array('displayedInContainer' => true));
	}

	/**
	 * return a string to the Handler for this modal.
	 * @return String
	 */
	function _getHandlerClassPath() {
		return 'modals.submissionMetadata.IssueEntryHandler';
	}
}

?>
