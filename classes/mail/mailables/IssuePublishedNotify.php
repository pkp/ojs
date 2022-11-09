<?php

/**
 * @file classes/mail/mailables/IssuePublishedNotify.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssuePublishedNotify
 * @ingroup mail_mailables
 *
 * @brief Email is sent automatically to all registered users to notify about new published issue
 */

namespace APP\mail\mailables;

use APP\issue\Issue;
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

    protected static string $issueIdentification = 'issueIdentification';

    protected Context $context;

    public function __construct(Context $context, Issue $issue)
    {
        parent::__construct([$context]);
        $this->context = $context;
        $this->setupIssueIdentificationVariable($issue);
    }

    /**
     * Adds description to the issueIdentification email template variable
     */
    public static function getDataDescriptions(): array
    {
        $variables = parent::getDataDescriptions();
        $variables[static::$issueIdentification] = __('emailTemplate.variable.issue.issueIdentification');
        return $variables;
    }

    /**
     * Include current issue identification to be used as an email template variable
     */
    protected function setupIssueIdentificationVariable(Issue $issue)
    {
        $this->addData([
            static::$issueIdentification => $issue->getData('identification')
        ]);
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
