<?php

/**
 * @file jobs/notifications/OpenAccessMailUsers.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OpenAccessMailUsers
 *
 * @ingroup jobs
 *
 * @brief Class to send issue open access notification to userIds
 */

namespace APP\jobs\notifications;

use APP\core\Application;
use APP\facades\Repo;
use APP\journal\Journal;
use APP\mail\mailables\OpenAccessNotify;
use APP\notification\Notification;
use APP\notification\NotificationManager;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use PKP\jobs\BaseJob;

class OpenAccessMailUsers extends BaseJob
{
    use Batchable;

    protected Collection $userIds;
    protected int $contextId;
    protected int $issueId;

    public function __construct(Collection $userIds, int $contextId, int $issueId)
    {
        parent::__construct();

        $this->userIds = $userIds;
        $this->contextId = $contextId;
        $this->issueId = $issueId;
    }

    public function handle()
    {
        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById($this->contextId); /** @var Journal $context */
        $issue = Repo::issue()->get($this->issueId, $this->contextId);

        if (!$context || !$issue) {
            return;
        }

        $locale = $context->getPrimaryLocale();
        $template = Repo::emailTemplate()->getByKey($this->contextId, OpenAccessNotify::getEmailTemplateKey());

        foreach ($this->userIds as $userId) {
            $user = Repo::user()->get($userId);
            if (!$user) {
                continue;
            }

            $notificationManager = new NotificationManager();
            $notification = $notificationManager->createNotification(
                null,
                $userId,
                Notification::NOTIFICATION_TYPE_OPEN_ACCESS,
                $this->contextId
            );

            $mailable = new OpenAccessNotify($context, $issue);
            $mailable
                ->subject($template->getLocalizedData('subject', $locale))
                ->body($template->getLocalizedData('body', $locale))
                ->from($context->getData('contactEmail'), $context->getData('contactName'))
                ->recipients([$user])
                ->allowUnsubscribe($notification);

            Mail::send($mailable);
        }
    }
}
