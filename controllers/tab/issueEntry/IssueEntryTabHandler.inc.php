<?php

/**
 * @file controllers/tab/issueEntry/IssueEntryTabHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
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
				'publicationMetadata',
			)
		);
	}


	//
	// Public handler methods
	//

	/**
	 * Show the publication metadata form.
	 * @param $request Request
	 * @param $args array
	 * @return string JSON message
	 */
	function publicationMetadata($args, $request) {
		import('controllers.tab.issueEntry.form.IssueEntryPublicationMetadataForm');

		$submission = $this->getSubmission();
		$stageId = $this->getStageId();
		$user = $request->getUser();

		$issueEntryPublicationMetadataForm = new IssueEntryPublicationMetadataForm($submission->getId(), $user->getId(), $stageId, array('displayedInContainer' => true));

		$issueEntryPublicationMetadataForm->initData($args, $request);
		$json = new JSONMessage(true, $issueEntryPublicationMetadataForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Get the form for a particular tab.
	 */
	function _getFormFromCurrentTab(&$form, &$notificationKey, $request) {
		parent::_getFormFromCurrentTab($form, $notificationKey, $request); // give PKP-lib a chance to set the form and key.

		if (!$form) { // nothing applicable in parent.
			$submission = $this->getSubmission();
			switch ($this->getCurrentTab()) {
				case 'publication':
					import('controllers.tab.issueEntry.form.IssueEntryPublicationMetadataForm');
					$user = $request->getUser();
					$form = new IssueEntryPublicationMetadataForm($submission->getId(), $user->getId(), $this->getStageId(), array('displayedInContainer' => true, 'tabPos' => $this->getTabPosition()));
					$notificationKey = 'notification.savedIssueMetadata';
					import('lib.pkp.classes.log.SubmissionLog');
					SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_ISSUE_METADATA_UPDATE, 'submission.event.issueMetadataUpdated');
					break;
			}
		}
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
