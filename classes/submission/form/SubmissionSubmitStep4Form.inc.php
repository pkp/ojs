<?php

/**
 * @file classes/submission/form/SubmissionSubmitStep4Form.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
	function execute(...$functionParams) {
		parent::execute(...$functionParams);

		$submission = $this->submission;
		// Send author notification email
		import('classes.mail.ArticleMailTemplate');
		$request = Application::get()->getRequest();
		$context = $request->getContext();
		$router = $request->getRouter();
		$user = $request->getUser();

		$mail = new ArticleMailTemplate($submission, 'SUBMISSION_ACK', null, null, false);
		if ($mail->isEnabled()) {
			// submission ack emails should be from the contact.
			$mail->setFrom($this->context->getData('contactEmail'), $this->context->getData('contactName'));
			$mail->addRecipient($user->getEmail(), $user->getFullName());

			$mail->bccAssignedSubEditors($submission->getId(), WORKFLOW_STAGE_ID_SUBMISSION);

			$mail->assignParams([
				'authorName' => htmlspecialchars($user->getFullName()),
				'authorUsername' => htmlspecialchars($user->getUsername()),
				'editorialContactSignature' => htmlspecialchars($context->getData('contactName')),
				'submissionUrl' => $router->url($request, null, 'authorDashboard', 'submission', $submission->getId()),
			]);

			if (!$mail->send($request)) {
				import('classes.notification.NotificationManager');
				$notificationMgr = new NotificationManager();
				$notificationMgr->createTrivialNotification($request->getUser()->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('email.compose.error')));
			}
		}

		$authorMail = new ArticleMailTemplate($submission, 'SUBMISSION_ACK_NOT_USER', null, null, false);
		if ($authorMail->isEnabled()) {
			$authorMail->setFrom($this->context->getData('contactEmail'), $this->context->getData('contactName'));

			$primaryAuthor = $submission->getPrimaryAuthor();
			if (!isset($primaryAuthor)) {
				$authors = $submission->getAuthors();
				$primaryAuthor = $authors[0];
			}

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

			$authorMail->assignParams([
				'submitterName' => htmlspecialchars($user->getFullName()),
				'editorialContactSignature' => htmlspecialchars($context->getData('contactName')),
			]);

			if (!empty($authorMail->getRecipients()) && !$authorMail->send($request)) {
				import('classes.notification.NotificationManager');
				$notificationMgr = new NotificationManager();
				$notificationMgr->createTrivialNotification($request->getUser()->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('email.compose.error')));
			}
		}

		// Log submission.
		import('classes.log.SubmissionEventLogEntry'); // Constants
		import('lib.pkp.classes.log.SubmissionLog');
		SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_SUBMISSION_SUBMIT, 'submission.event.submissionSubmitted');

		return $this->submissionId;
	}
}


