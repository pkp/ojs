<?php

declare(strict_types=1);

/**
 * @file classes/Commands/SubscriptionExpiryReminderCommand.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionExpiryReminderCommand
 * @ingroup classes_Commands
 *
 * @brief CLI Command to execute SubscriptionExpiryReminder task
 */

namespace APP\Commands;

use APP\tasks\SubscriptionExpiryReminder;

use PKP\cliTool\CommandLineTool;

class SubscriptionExpiryReminderCommand extends CommandLineTool
{
    public function execute()
    {
        (new SubscriptionExpiryReminder())->execute();
    }
}
