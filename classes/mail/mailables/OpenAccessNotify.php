<?php

/**
 * @file classes/mail/mailables/OpenAccessNotify.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OpenAccessNotify
 *
 * @brief Email sent to notify user about issue open access
 */

namespace APP\mail\mailables;

use APP\facades\Repo;
use APP\issue\Issue;
use APP\journal\Journal;
use PKP\mail\Mailable;
use PKP\mail\traits\Configurable;
use PKP\mail\traits\Recipient;
use PKP\security\Role;

class OpenAccessNotify extends Mailable
{
    use Configurable;
    use Recipient;

    protected static ?string $name = 'mailable.openAccessNotify.name';
    protected static ?string $description = 'mailable.openAccessNotify.description';
    protected static ?string $emailTemplateKey = 'OPEN_ACCESS_NOTIFY';
    protected static array $groupIds = [self::GROUP_OTHER];
    protected static array $toRoleIds = [Role::ROLE_ID_READER];

    protected Journal $context;
    protected Issue $issue;

    public function __construct(Journal $context, Issue $issue)
    {
        parent::__construct([$context]);
        $this->context = $context;
        $this->issue = $issue;
    }

    /**
     * Setup Smarty variables for the message template
     */
    public function getSmartyTemplateVariables(): array
    {
        $template = Repo::emailTemplate()->getByKey($this->context->getId(), static::$emailTemplateKey);
        return [
            'body' => $template->getData('body', $this->context->getPrimaryLocale()),
            'mimeBoundary' => '==boundary_' . md5(microtime()),
            'issue' => $this->issue,
            'publishedSubmissions' => Repo::submission()->getInSections($this->issue->getId(), $this->issue->getJournalId()),
        ];
    }
}
