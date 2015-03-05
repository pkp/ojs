<?php

/**
 * @file classes/submission/form/SubmissionSubmitStep3Form.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSubmitStep3Form
 * @ingroup submission_form
 *
 * @brief Form for Step 3 of author submission.
 */

import('lib.pkp.classes.submission.form.PKPSubmissionSubmitStep3Form');
import('classes.submission.SubmissionMetadataFormImplementation');

class SubmissionSubmitStep3Form extends PKPSubmissionSubmitStep3Form {
	/**
	 * Constructor.
	 */
	function SubmissionSubmitStep3Form($context, $submission) {
		parent::PKPSubmissionSubmitStep3Form(
			$context,
			$submission,
			new SubmissionMetadataFormImplementation($this)
		);
	}

	/**
	 * Save changes to submission.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return int the submission ID
	 */
	function execute($args, $request) {
		parent::execute($args, $request);

		$submission = $this->submission;
		// Send author notification email
		import('classes.mail.ArticleMailTemplate');
		$mail = new ArticleMailTemplate($submission, 'SUBMISSION_ACK');
		$authorMail = new ArticleMailTemplate($submission, 'SUBMISSION_ACK_NOT_USER');

		$context = $request->getContext();
		$router = $request->getRouter();
		if ($mail->isEnabled()) {
			// submission ack emails should be from the contact.
			$mail->setFrom($this->context->getSetting('contactEmail'), $this->context->getSetting('contactName'));
			$authorMail->setFrom($this->context->getSetting('contactEmail'), $this->context->getSetting('contactName'));

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
			if ($context->getSetting('copySubmissionAckPrimaryContact')) {
				$authorMail->addBcc(
					$context->getSetting('contactEmail'),
					$context->getSetting('contactName')
				);
			}
			if ($copyAddress = $context->getSetting('copySubmissionAckAddress')) {
				$authorMail->addBcc($copyAddress);
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
			$mail->bccAssignedSectionEditors($submission->getId(), WORKFLOW_STAGE_ID_SUBMISSION);

			$mail->assignParams(array(
				'authorName' => $user->getFullName(),
				'authorUsername' => $user->getUsername(),
				'editorialContactSignature' => $context->getSetting('contactName') . "\n" . $context->getLocalizedName(),
				'submissionUrl' => $router->url($request, null, 'authorDashboard', 'submission', $submission->getId()),
			));

			$authorMail->assignParams(array(
				'submitterName' => $user->getFullName(),
				'editorialContactSignature' => $context->getSetting('contactName') . "\n" . $context->getLocalizedName(),
			));

			$mail->send($request);

			$recipients = $authorMail->getRecipients();
			if (!empty($recipients)) {
				$authorMail->send($request);
			}
		}

		// Log submission.
		import('classes.log.SubmissionEventLogEntry'); // Constants
		import('lib.pkp.classes.log.SubmissionLog');
		SubmissionLog::logEvent($request, $submission, SUBMISSION_LOG_SUBMISSION_SUBMIT, 'submission.event.submissionSubmitted');

		return $this->submissionId;
	}
}

?>
