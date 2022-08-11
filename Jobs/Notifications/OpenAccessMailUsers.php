<?php

/**
 * @file Jobs/Notifications/OpenAccessMailUsers.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OpenAccessMailUsers
 * @ingroup jobs
 *
 * @brief Class to send issue open access notification to users
 */

namespace APP\Jobs\Notifications;

use APP\core\Application;
use APP\facades\Repo;
use APP\journal\Journal;
use APP\mail\mailables\OpenAccessNotify;
use APP\template\TemplateManager;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\LazyCollection;
use PKP\Support\Jobs\BaseJob;
use Symfony\Component\Mime\Message;

class OpenAccessMailUsers extends BaseJob
{
    use Batchable;

    protected LazyCollection $users;
    protected int $contextId;
    protected int $issueId;

    public function __construct(LazyCollection $users, int $contextId, int $issueId)
    {
        parent::__construct();

        $this->users = $users;
        $this->contextId = $contextId;
        $this->issueId = $issueId;
    }

    public function handle()
    {
        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById($this->contextId); /** @var Journal $context */
        $issue = Repo::issue()->get($this->issueId);
        $locale = $context->getPrimaryLocale();
        $template = Repo::emailTemplate()->getByKey($this->contextId, OpenAccessNotify::getEmailTemplateKey());

        foreach ($this->users as $user) {
            $mailable = new OpenAccessNotify($context, $issue);
            $mailable
                ->from($context->getData('contactEmail'), $context->getData('contactName'))
                ->recipients([$user]);

            $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
            $templateMgr->assign($mailable->getSmartyTemplateVariables());

            $mailable
                ->subject($template->getData('subject', $locale))
                ->body($templateMgr->fetch('payments/openAccessNotifyEmail.tpl'));

            $mailable->withSymfonyMessage(function (Message $message) use ($templateMgr) {
                $message->getHeaders()->addHeader(
                    'Content-Type',
                    'multipart/alternative; boundary="' . $templateMgr->getTemplateVars('mimeBoundary') . '"'
                );
            });

            Mail::send($mailable);
        }
    }
}
