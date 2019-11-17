<?php

/**
 * @file classes/submission/form/SubmissionSubmitStep4Form.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSubmitStep4Form
 * @ingroup submission_form
 *
 * @brief Form for Step 4 of author submission.
 */

import('lib.pkp.classes.submission.form.PKPSubmissionSubmitStep4Form');

class SubmissionSubmitStep4Form extends PKPSubmissionSubmitStep4Form {
	/**
	 * Constructor.
	 * @param $context Context
	 * @param $submission Submission
	 */
	function __construct($context, $submission) {
		parent::__construct(
			$context,
			$submission
		);
	}

	/**
	 * Save changes to submission.
	 * @return int the submission ID
	 */
	function execute() {
		parent::execute();

		$submission = $this->submission;

		// PPS: Move the submission to production stage 
		$submissionDao = Application::getSubmissionDAO();
		$submission->setStageId(WORKFLOW_STAGE_ID_PRODUCTION);
		$submissionDao->updateObject($submission);

		// Send author notification email
		import('classes.mail.ArticleMailTemplate');
		$mail = new ArticleMailTemplate($submission, 'SUBMISSION_ACK', null, null, false);
		$authorMail = new ArticleMailTemplate($submission, 'SUBMISSION_ACK_NOT_USER', null, null, false);

		$request = Application::get()->getRequest();
		$context = $request->getContext();
		$router = $request->getRouter();
		if ($mail->isEnabled()) {
			// submission ack emails should be from the contact.
			$mail->setFrom($this->context->getData('contactEmail'), $this->context->getData('contactName'));
			$authorMail->setFrom($this->context->getData('contactEmail'), $this->context->getData('contactName'));

			$user = $request->getUser();
			$primaryAuthor = $submission->getPrimaryAuthor();
			if (!isset($primaryAuthor)) {
				$authors = $submission->getAuthors();
				$primaryAuthor = $authors[0];
			}
			$mail->addRecipient($user->getEmail(), $user->getFullName());

			if ($user->getEmail() != $primaryAuthor->getEmail()) {
				$authorMail->addRecipient($primaryAuthor->getEmail(), $primaryAuthor->getFullName());
			}

			$assignedAuthors = $submission->getAuthors();

			foreach ($assignedAuthors as $author) {
				$authorEmail = $author->getEmail();
				// only add the author email if they have not already been added as the primary author
				// or user creating the submission.
				if ($authorEmail != $primaryAuthor->getEmail() && $authorEmail != $user->getEmail()) {
					$authorMail->addRecipient($author->getEmail(), $author->getFullName());
				}
			}
			$mail->bccAssignedSubEditors($submission->getId(), WORKFLOW_STAGE_ID_PRODUCTION);

			$mail->assignParams(array(
				'authorName' => $user->getFullName(),
				'authorUsername' => $user->getUsername(),
				'editorialContactSignature' => $context->getData('contactName'),
				'submissionUrl' => $router->url($request, null, 'authorDashboard', 'submission', $submission->getId()),
			));

			$authorMail->assignParams(array(
				'submitterName' => $user->getFullName(),
				'editorialContactSignature' => $context->getData('contactName'),
			));

			if (!$mail->send($request)) {
				import('classes.notification.NotificationManager');
				$notificationMgr = new NotificationManager();
				$notificationMgr->createTrivialNotification($request->getUser()->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('email.compose.error')));
			}

			$recipients = $authorMail->getRecipients();
			if (!empty($recipients)) {
				if (!$authorMail->send($request)) {
					import('classes.notification.NotificationManager');
					$notificationMgr = new NotificationManager();
					$notificationMgr->createTrivialNotification($request->getUser()->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('email.compose.error')));
				}
			}
		}

		// Log submission.
		import('classes.log.SubmissionEventLogEntry'); // Constants
		import('lib.pkp.classes.log.SubmissionLog');
		SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_SUBMISSION_SUBMIT, 'submission.event.submissionSubmitted');

		return $this->submissionId;
	}
}


