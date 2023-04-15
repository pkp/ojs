<?php

/**
 * @file classes/mail/mailables/PaymentRequest.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PaymentRequest
 *
 * @ingroup mail_mailables
 *
 * @brief Email is sent automatically to notify authors of the submission about the required payment
 */

namespace APP\mail\mailables;

use APP\core\Application;
use APP\journal\Journal;
use APP\mail\variables\ContextEmailVariable;
use APP\submission\Submission;
use PKP\core\PKPApplication;
use PKP\mail\Mailable;
use PKP\mail\traits\Configurable;
use PKP\mail\traits\Recipient;
use PKP\mail\variables\ContextEmailVariable as PKPContextEmailVariable;
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
    protected static string $submissionGuidelinesUrl = 'submissionGuidelinesUrl';

    public function __construct(Journal $context, Submission $submission, QueuedPayment $queuedPayment)
    {
        parent::__construct(func_get_args());
        $this->setupPaymentUrlVariable($context, $queuedPayment);
    }

    protected function setupPaymentUrlVariable(Journal $context, QueuedPayment $queuedPayment)
    {
        $request = Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();
        $this->addData([
            static::$queuedPaymentUrl => $dispatcher->url(
                $request,
                PKPApplication::ROUTE_PAGE,
                $context->getPath(),
                'payment',
                'pay',
                [$queuedPayment->getId()]
            ),
            static::$submissionGuidelinesUrl => $dispatcher->url(
                $request,
                Application::ROUTE_PAGE,
                $context->getPath(),
                'about',
                'submissions'
            ),
        ]);
    }

    public static function getDataDescriptions(): array
    {
        return array_merge(
            parent::getDataDescriptions(),
            [
                static::$queuedPaymentUrl => __('emailTemplate.variable.queuedPaymentUrl'),
                static::$submissionGuidelinesUrl => __('emailTemplate.variable.submissionGuidelinesUrl'),
            ]
        );
    }

    protected function addFooter(string $locale): self
    {
        $this->footer = $this->renameContextVariables(
            __('emails.paymentRequestNotification.footer', [], $locale)
        );
        return $this;
    }

    /**
     * Replace email template variables in the locale string, so they correspond to the application,
     * e.g., contextName => journalName/pressName/serverName
     */
    protected function renameContextVariables(string $footer): string
    {
        $map = [
            '{$' . PKPContextEmailVariable::CONTEXT_NAME . '}' => '{$' . ContextEmailVariable::CONTEXT_NAME . '}',
            '{$' . PKPContextEmailVariable::CONTEXT_URL . '}' => '{$' . ContextEmailVariable::CONTEXT_URL . '}',
        ];

        return str_replace(array_keys($map), array_values($map), $footer);
    }
}
