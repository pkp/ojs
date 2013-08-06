<?php

/**
 * @file controllers/grid/users/stageParticipant/StageParticipantGridHandler.inc.php
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StageParticipantGridHandler
 * @ingroup controllers_grid_users_stageParticipant
 *
 * @brief Handle stageParticipant grid requests.
 */

// import grid base classes
import('lib.pkp.controllers.grid.users.stageParticipant.PKPStageParticipantGridHandler');

class StageParticipantGridHandler extends PKPStageParticipantGridHandler {
	/**
	 * Constructor
	 */
	function StageParticipantGridHandler() {
		parent::PKPStageParticipantGridHandler();
	}

	/**
	 * return the app-specific ID for the section.
	 * @return int
	 */
	function _getIdForSubEditorFilter($submission) {
		$seriesId = $submission->getSectionId();
		return $seriesId;
	}

	/**
	 * Fetches an email template's message body and returns it via AJAX.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function fetchTemplateBody($args, $request) {
		$templateId = $request->getUserVar('template');
		import('lib.pkp.classes.mail.SubmissionMailTemplate');
		$template = new SubmissionMailTemplate($this->getSubmission(), $templateId);
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
			case SUBMISSION_LOG_MESSAGE_SENT:
				$logMessage = 'informationCenter.history.messageSent';
				break;
			default:
				assert(false);
		}

		import('lib.pkp.classes.log.SubmissionLog');
		SubmissionLog::logEvent($request, $this->getSubmission(), $eventType, $logMessage);
	}
}

?>
