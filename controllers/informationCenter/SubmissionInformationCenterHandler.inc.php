<?php

/**
 * @file controllers/informationCenter/SubmissionInformationCenterHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
	 * @return JSONMessage JSON object
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

		return new JSONMessage(true, $submissionMetadataViewForm->fetch($request));
	}

	/**
	 * Save the metadata tab.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function saveForm($args, $request) {
		$this->setupTemplate($request);

		import('controllers.modals.submissionMetadata.form.SubmissionMetadataViewForm');
		$submissionMetadataViewForm = new SubmissionMetadataViewForm($this->_submission->getId());

		// Try to save the form data.
		$submissionMetadataViewForm->readInputData();
		if($submissionMetadataViewForm->validate()) {
			$submissionMetadataViewForm->execute($request);
			// Create trivial notification.
			$notificationManager = new NotificationManager();
			$user = $request->getUser();
			$notificationManager->createTrivialNotification($user->getId(), NOTIFICATION_TYPE_SUCCESS, array('contents' => __('notification.savedSubmissionMetadata')));
			return new JSONMessage();
		}
		$json->setStatus(false);
	}

	/**
	 * Log an event for this file
	 * @param $request PKPRequest
	 * @param $eventType SUBMISSION_LOG_...
	 */
	function _logEvent ($request, $eventType) {
		// Get the log event message
		$logMessage = null; // Suppress scrutinizer warn
		switch($eventType) {
			case SUBMISSION_LOG_NOTE_POSTED:
				$logMessage = 'informationCenter.history.notePosted';
				break;
			default:
				assert(false);
		}

		import('lib.pkp.classes.log.SubmissionLog');
		SubmissionLog::logEvent($request, $this->_submission, $eventType, $logMessage);
	}
}

?>
