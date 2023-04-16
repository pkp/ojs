<?php

/**
 * @file classes/mail/mailables/IssuePublishedNotify.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssuePublishedNotify
 *
 * @ingroup mail_mailables
 *
 * @brief Email is sent automatically to all registered users to notify about new published issue
 */

namespace APP\mail\mailables;

use APP\issue\Issue;
use APP\mail\variables\IssueEmailVariable;
use PKP\context\Context;
use PKP\mail\Mailable;
use PKP\mail\traits\Configurable;
use PKP\mail\traits\Recipient;
use PKP\mail\traits\Sender;
use PKP\mail\traits\Unsubscribe;
use PKP\security\Role;

class IssuePublishedNotify extends Mailable
{
    use Configurable;
    use Recipient;
    use Sender;
    use Unsubscribe;

    protected static ?string $name = 'mailable.issuePublishNotify.name';
    protected static ?string $description = 'mailable.issuePublishNotify.description';
    protected static ?string $emailTemplateKey = 'ISSUE_PUBLISH_NOTIFY';
    protected static array $groupIds = [self::GROUP_OTHER];
    protected static array $fromRoleIds = [Role::ROLE_ID_SUB_EDITOR];
    protected static array $toRoleIds = [Role::ROLE_ID_READER];

    protected Context $context;

    public function __construct(Context $context, Issue $issue)
    {
        parent::__construct([$context, $issue]);

        $this->context = $context;
    }

    protected static function templateVariablesMap(): array
    {
        $map = parent::templateVariablesMap();
        $map[Issue::class] = IssueEmailVariable::class;
        return $map;
    }

    /**
     * Adds a footer with unsubscribe link
     */
    protected function addFooter(string $locale): Mailable
    {
        $this->setupUnsubscribeFooter($locale, $this->context);
        return $this;
    }
}
