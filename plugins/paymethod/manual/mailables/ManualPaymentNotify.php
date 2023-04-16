<?php

/**
 * @file classes/mail/mailables/ManualPaymentNotify.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ManualPaymentNotify
 *
 * @ingroup mail_mailables
 *
 * @brief Email is sent automatically to notify journal manager about new payment that needs to be processed
 */

namespace APP\plugins\paymethod\manual\mailables;

use APP\journal\Journal;
use PKP\mail\Mailable;
use PKP\mail\traits\Configurable;
use PKP\mail\traits\Sender;
use PKP\payment\QueuedPayment;
use PKP\security\Role;
use PKP\user\User;

class ManualPaymentNotify extends Mailable
{
    use Configurable;
    use Sender;

    protected static ?string $name = 'plugins.paymethod.manual.manualPaymentNotify.name';
    protected static ?string $description = 'emails.manualPaymentNotification.description';
    protected static ?string $emailTemplateKey = 'MANUAL_PAYMENT_NOTIFICATION';
    protected static array $toRoleIds = [Role::ROLE_ID_SUB_EDITOR];

    protected const SENDER_USERNAME = 'senderUsername';

    public function __construct(Journal $context, QueuedPayment $queuedPayment)
    {
        parent::__construct(func_get_args());
    }

    /**
     * Add description to the mailable-specific email template variables
     *
     * @copydoc PKP\mail\Mailable::getDataDescriptions()
     */
    public static function getDataDescriptions(): array
    {
        return array_merge(
            parent::getDataDescriptions(),
            [
                self::SENDER_USERNAME => __('emailTemplate.variable.manualPaymentPlugin.senderUsername'),
            ]
        );
    }

    /**
     * Setup a variable containing a username of the user making the payment
     */
    public function sender(User $sender, ?string $defaultLocale = null): Mailable
    {
        $this->addData([self::SENDER_USERNAME => $sender->getUsername()]);
        return $this;
    }
}
