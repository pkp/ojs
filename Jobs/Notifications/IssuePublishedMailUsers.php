<?php

/**
 * @file Jobs/Notifications/IssuePublishedMailUsers.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssuePublishedMailUsers
 * @ingroup jobs
 *
 * @brief Class to send emails when a new issue is published
 */

namespace APP\Jobs\Notifications;

use APP\core\Application;
use APP\facades\Repo;
use APP\mail\mailables\IssuePublishedNotify;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use PKP\context\Context;
use PKP\emailTemplate\EmailTemplate;
use PKP\Support\Jobs\BaseJob;
use PKP\user\User;

class IssuePublishedMailUsers extends BaseJob
{
    use Batchable;

    protected Collection $recipientIds;
    protected int $contextId;
    protected User $sender;
    protected string $locale;

    public function __construct(Collection $recipientIds, int $contextId, User $sender, string $locale)
    {
        parent::__construct();

        $this->recipientIds = $recipientIds;
        $this->contextId = $contextId;
        $this->sender = $sender;
        $this->locale = $locale;
    }

    public function handle()
    {
        $context = Application::getContextDAO()->getById($this->contextId);
        $template = Repo::emailTemplate()->getByKey($this->contextId, IssuePublishedNotify::getEmailTemplateKey());
        foreach ($this->recipientIds as $recipientId) {
            $recipient = Repo::user()->get($recipientId);
            if (!$recipient) {
                continue;
            }
            $mailable = $this->createMailable($context, $recipient, $template);
            $mailable->setData($this->locale);
            Mail::send($mailable);
        }
    }

    /**
     * Creates new issue published notification email
     */
    protected function createMailable(Context $context, User $recipient, EmailTemplate $template): IssuePublishedNotify
    {
        $mailable = new IssuePublishedNotify($context);
        $mailable
            ->recipients([$recipient])
            ->sender($this->sender)
            ->body($template->getData('body', $this->locale))
            ->subject($template->getData('subject', $this->locale));

        return $mailable;
    }
}
