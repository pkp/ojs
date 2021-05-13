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

namespace APP\submission\form;

use PKP\submission\form\PKPSubmissionSubmitStep4Form;
use PKP\log\SubmissionLog;
use PKP\notification\PKPNotification;

use APP\log\SubmissionEventLogEntry;
use APP\core\Application;
use APP\notification\NotificationManager;
use APP\mail\ArticleMailTemplate;

class SubmissionSubmitStep4Form extends PKPSubmissionSubmitStep4Form
{
    /**
     * Save changes to submission.
     *
     * @return int the submission ID
     */
    public function execute(...$functionParams)
    {
        parent::execute(...$functionParams);

        $submission = $this->submission;
        // Send author notification email
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

            // Add primary contact and e-mail addresses as specified in the journal submission settings
            if ($this->context->getData('copySubmissionAckPrimaryContact')) {
                $mail->addBcc(
                    $context->getData('contactEmail'),
                    $context->getData('contactName')
                );
            }

            $submissionAckAddresses = $this->context->getData('copySubmissionAckAddress');
            if (!empty($submissionAckAddresses)) {
                $submissionAckAddressArray = explode(',', $submissionAckAddresses);
                foreach ($submissionAckAddressArray as $submissionAckAddress) {
                    $mail->addBcc($submissionAckAddress);
                }
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
            $mail->bccAssignedSubEditors($submission->getId(), WORKFLOW_STAGE_ID_SUBMISSION);

            $mail->assignParams([
                'authorName' => $user->getFullName(),
                'authorUsername' => $user->getUsername(),
                'editorialContactSignature' => $context->getData('contactName'),
                'submissionUrl' => $router->url($request, null, 'authorDashboard', 'submission', $submission->getId()),
            ]);

            $authorMail->assignParams([
                'submitterName' => $user->getFullName(),
                'editorialContactSignature' => $context->getData('contactName'),
            ]);

            if (!$mail->send($request)) {
                $notificationMgr = new NotificationManager();
                $notificationMgr->createTrivialNotification($request->getUser()->getId(), PKPNotification::NOTIFICATION_TYPE_ERROR, ['contents' => __('email.compose.error')]);
            }

            $recipients = $authorMail->getRecipients();
            if (!empty($recipients)) {
                if (!$authorMail->send($request)) {
                    $notificationMgr = new NotificationManager();
                    $notificationMgr->createTrivialNotification($request->getUser()->getId(), PKPNotification::NOTIFICATION_TYPE_ERROR, ['contents' => __('email.compose.error')]);
                }
            }
        }

        // Log submission.
        SubmissionLog::logEvent($request, $submission, SubmissionEventLogEntry::SUBMISSION_LOG_SUBMISSION_SUBMIT, 'submission.event.submissionSubmitted');

        return $this->submissionId;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\submission\form\SubmissionSubmitStep4Form', '\SubmissionSubmitStep4Form');
}
