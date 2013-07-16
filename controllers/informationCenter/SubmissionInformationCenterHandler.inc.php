<?php

/**
 * @file controllers/informationCenter/SubmissionInformationCenterHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionInformationCenterHandler
 * @ingroup controllers_informationCenter
 *
 * @brief Handle requests to view the information center for a submission.
 */

import('lib.pkp.controllers.informationCenter.PKPSubmissionInformationCenterHandler');

class SubmissionInformationCenterHandler extends PKPSubmissionInformationCenterHandler {
	/**
	 * Constructor
	 */
	function SubmissionInformationCenterHandler() {
		parent::PKPSubmissionInformationCenterHandler();
	}

	/**
	 * Display the metadata tab.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function metadata($args, $request) {
		$this->setupTemplate($request);

		import('controllers.modals.submissionMetadata.form.SubmissionMetadataViewForm');
		// prevent anyone but managers and editors from submitting the catalog entry form
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		$params = array();
		if (!array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR), $userRoles)) {
			$params['hideSubmit'] = true;
			$params['readOnly'] = true;
		}
		$submissionMetadataViewForm = new SubmissionMetadataViewForm($this->_submission->getId(), null, $params);
		$submissionMetadataViewForm->initData($args, $request);

		$json = new JSONMessage(true, $submissionMetadataViewForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Save the metadata tab.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveForm($args, $request) {
		$this->setupTemplate($request);

		import('controllers.modals.submissionMetadata.form.SubmissionMetadataViewForm');
		$submissionMetadataViewForm = new SubmissionMetadataViewForm($this->_submission->getId());

		$json = new JSONMessage();

		// Try to save the form data.
		$submissionMetadataViewForm->readInputData($request);
		if($submissionMetadataViewForm->validate()) {
			$submissionMetadataViewForm->execute($request);
			// Create trivial notification.
			$notificationManager = new NotificationManager();
			$user = $request->getUser();
			$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.savedSubmissionMetadata')));
		} else {
			$json->setStatus(false);
		}

		return $json->getString();
	}


	/**
	 * Fetches an email template's message body and returns it via AJAX.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function fetchTemplateBody($args, $request) {
		$templateId = $request->getUserVar('template');
		import('lib.pkp.classes.mail.SubmissionMailTemplate');
		$template = new SubmissionMailTemplate($this->_submission, $templateId);
		if ($template) {
			$user = $request->getUser();
			$dispatcher = $request->getDispatcher();
			$context = $request->getContext();
			$template->assignParams(array(
				'editorialContactSignature' => $user->getContactSignature(),
				'signatureFullName' => $user->getFullname(),
			));

			$json = new JSONMessage(true, $template->getBody() . "\n" . $context->getSetting('emailSignature'));
			return $json->getString();
		}
	}

	/**
	 * Log an event for this file
	 * @param $request PKPRequest
	 * @param $eventType SUBMISSION_LOG_...
	 */
	function _logEvent ($request, $eventType) {
		// Get the log event message
		switch($eventType) {
			case SUBMISSION_LOG_NOTE_POSTED:
				$logMessage = 'informationCenter.history.notePosted';
				break;
			case SUBMISSION_LOG_MESSAGE_SENT:
				$logMessage = 'informationCenter.history.messageSent';
				break;
			default:
				assert(false);
		}

		import('lib.pkp.classes.log.SubmissionLog');
		SubmissionLog::logEvent($request, $this->_submission, $eventType, $logMessage);
	}
}

?>
