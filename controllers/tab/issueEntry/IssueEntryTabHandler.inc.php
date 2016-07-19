<?php

/**
 * @file controllers/tab/issueEntry/IssueEntryTabHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
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
	function IssueEntryTabHandler() {
		parent::PublicationEntryTabHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
			array(
				'publicationMetadata', 'savePublicationMetadataForm', 'identifiers', 'clearPubId', 'updateIdentifiers', 'assignPubIds',
			)
		);
	}


	//
	// Public handler methods
	//

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
		if($form->validate($request)) {
			$form->execute($request);
			// Log the event
			import('lib.pkp.classes.log.SubmissionLog');
			import('classes.log.SubmissionEventLogEntry'); // Log consts
			SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_ISSUE_METADATA_UPDATE, 'submission.event.issueMetadataUpdated');
			// Create trivial notification in place on the form
			$notificationManager = new NotificationManager();
			$notificationKey = 'notification.savedIssueMetadata';
			$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __($notificationKey)));
			// Display assign public identifiers form
			import('lib.pkp.controllers.grid.pubIds.form.PKPAssignPublicIdentifiersForm');
			$formTemplate = $this->getAssignPublicIdentifiersFormTemplate();
			$formParams['stageId'] = $stageId;
			$assignPublicIdentifiersForm = new PKPAssignPublicIdentifiersForm($formTemplate, $submission, true, '', $formParams);
			$assignPublicIdentifiersForm->initData($args, $request);
			return new JSONMessage(true, $assignPublicIdentifiersForm->fetch($request));
		} else {
			return new JSONMessage(true, $form->fetch($request));
		}
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
		import('lib.pkp.controllers.grid.pubIds.form.PKPAssignPublicIdentifiersForm');
		$formTemplate = $this->getAssignPublicIdentifiersFormTemplate();
		$formParams['stageId'] = $stageId;
		$assignPublicIdentifiersForm = new PKPAssignPublicIdentifiersForm($formTemplate, $submission, true, '', $formParams);
		// Asign pub ids
		$assignPublicIdentifiersForm->readInputData();
		$assignPublicIdentifiersForm->execute($request, true);
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
		import('controllers.tab.pubIds.form.PublicIdentifiersForm');
		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		$identifiersForm = new PublicIdentifiersForm($submission, $stageId, array('displayedInContainer' => true));
		$identifiersForm->clearPubId($request->getUserVar('pubIdPlugIn'));
		return new JSONMessage(true);
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
		if ($form->validate($request)) {
			$form->execute($request);
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
