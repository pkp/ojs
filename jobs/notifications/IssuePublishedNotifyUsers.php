<?php

/**
 * @file jobs/notifications/IssuePublishedMailUsers.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssuePublishedNotifyUsers
 *
 * @ingroup jobs
 *
 * @brief Class to send emails when a new issue is published
 */

namespace APP\jobs\notifications;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\mail\mailables\IssuePublishedNotify;
use APP\notification\Notification;
use APP\notification\NotificationManager;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use PKP\context\Context;
use PKP\emailTemplate\EmailTemplate;
use PKP\jobs\BaseJob;
use PKP\user\User;

class IssuePublishedNotifyUsers extends BaseJob
{
    use Batchable;

    protected Collection $recipientIds;
    protected int $contextId;
    protected Issue $issue;
    protected string $locale;

    // The sender of the email
    protected ?User $sender;

    public function __construct(
        Collection $recipientIds,
        int $contextId,
        Issue $issue,
        string $locale,
        ?User $sender = null // Leave null to not send an email
    ) {
        parent::__construct();

        $this->recipientIds = $recipientIds;
        $this->contextId = $contextId;
        $this->issue = $issue;
        $this->locale = $locale;
        $this->sender = $sender;
    }

    public function handle()
    {
        $context = Application::getContextDAO()->getById($this->contextId);
        $template = Repo::emailTemplate()->getByKey($this->contextId, IssuePublishedNotify::getEmailTemplateKey());

        foreach ($this->recipientIds as $recipientId) {
            /** @var int $recipientId */
            $recipient = Repo::user()->get($recipientId);

            if (!$recipient) {
                continue;
            }

            $notificationManager = new NotificationManager();
            $notification = $notificationManager->createNotification(
                null,
                $recipientId,
                Notification::NOTIFICATION_TYPE_PUBLISHED_ISSUE,
                $this->contextId,
                Application::ASSOC_TYPE_ISSUE,
                $this->issue->getId()
            );

            if (!$this->sender) {
                continue;
            }

            $mailable = $this->createMailable($context, $this->issue, $recipient, $template, $notification);
            $mailable->setData($this->locale);
            Mail::send($mailable);
        }
    }

    /**
     * Creates new issue published notification email
     */
    protected function createMailable(
        Context $context,
        Issue $issue,
        User $recipient,
        EmailTemplate $template,
        Notification $notification
    ): IssuePublishedNotify {
        $mailable = new IssuePublishedNotify($context, $issue);
        $mailable
            ->recipients([$recipient])
            ->sender($this->sender)
            ->body($template->getLocalizedData('body', $this->locale))
            ->subject($template->getLocalizedData('subject', $this->locale))
            ->allowUnsubscribe($notification);

        return $mailable;
    }
}
