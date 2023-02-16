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

use APP\issue\Issue;
use APP\journal\Journal;
use APP\mail\variables\IssueEmailVariable;
use PKP\mail\Mailable;
use PKP\mail\traits\Configurable;
use PKP\mail\traits\Recipient;
use PKP\mail\traits\Unsubscribe;
use PKP\security\Role;

class OpenAccessNotify extends Mailable
{
    use Configurable;
    use Recipient;
    use Unsubscribe;

    protected static ?string $name = 'mailable.openAccessNotify.name';
    protected static ?string $description = 'mailable.openAccessNotify.description';
    protected static ?string $emailTemplateKey = 'OPEN_ACCESS_NOTIFY';
    protected static array $groupIds = [self::GROUP_OTHER];
    protected static array $fromRoleIds = [self::FROM_SYSTEM];
    protected static array $toRoleIds = [Role::ROLE_ID_READER];

    protected Journal $context;

    public function __construct(Journal $context, Issue $issue)
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
