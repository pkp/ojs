<?php

/**
 * @file classes/mail/mailables/PaymentRequest.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RequestPayment
 * @ingroup mail_mailables
 *
 * @brief Email is sent automatically to notify authors of the submission about the required payment
 */

namespace APP\mail\mailables;

use APP\core\Application;
use APP\journal\Journal;
use APP\submission\Submission;
use PKP\core\PKPApplication;
use PKP\mail\Mailable;
use PKP\mail\traits\Configurable;
use PKP\mail\traits\Recipient;
use PKP\payment\QueuedPayment;
use PKP\security\Role;

class PaymentRequest extends Mailable
{
    use Configurable;
    use Recipient;

    protected static ?string $name = 'mailable.paymentRequest.name';
    protected static ?string $description = 'mailable.paymentRequest.description';
    protected static ?string $emailTemplateKey = 'PAYMENT_REQUEST_NOTIFICATION';
    protected static array $groupIds = [self::GROUP_OTHER];
    protected static array $fromRoleIds = [self::FROM_SYSTEM];
    protected static array $toRoleIds = [Role::ROLE_ID_AUTHOR];

    protected static string $queuedPaymentUrl = 'queuedPaymentUrl';

    public function __construct(Journal $context, Submission $submission, QueuedPayment $queuedPayment)
    {
        parent::__construct(func_get_args());
        $this->setupPaymentUrlVariable($context, $queuedPayment);
    }

    protected function setupPaymentUrlVariable(Journal $context, QueuedPayment $queuedPayment)
    {
        $this->addData([
            static::$queuedPaymentUrl => Application::get()->getDispatcher()->url(
                Application::get()->getRequest(),
                PKPApplication::ROUTE_PAGE,
                $context->getPath(),
                'payment',
                'pay',
                [$queuedPayment->getId()]
            ),
        ]);
    }

    public static function getDataDescriptions(): array
    {
        return array_merge(
            parent::getDataDescriptions(),
            [
                static::$queuedPaymentUrl => __('emailTemplate.variable.queuedPaymentUrl'),
            ]
        );
    }
}
