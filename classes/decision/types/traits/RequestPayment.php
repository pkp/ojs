<?php
/**
 * @file classes/decision/types/traits/RequestPayment.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class decision
 *
 * @brief Helper functions for decisions that may request a payment
 */

namespace APP\decision\types\traits;

use APP\components\forms\decision\RequestPaymentDecisionForm;
use APP\core\Application;
use APP\facades\Repo;
use APP\mail\mailables\PaymentRequest;
use APP\notification\Notification;
use APP\notification\NotificationManager;
use APP\payment\ojs\OJSPaymentManager;
use APP\submission\Submission;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Validator;
use PKP\context\Context;
use PKP\decision\steps\Form;
use PKP\user\User;

trait RequestPayment
{
    protected string $ACTION_PAYMENT = 'payment';

    /**
     * Get the form to request or waive payment
     */
    protected function getPaymentForm(Context $context): Form
    {
        return new Form(
            $this->ACTION_PAYMENT,
            __('editor.article.payment.requestPayment'),
            '',
            new RequestPaymentDecisionForm($context)
        );
    }

    /**
     * Validate the decision action to request or waive payment
     */
    protected function validatePaymentAction(array $action, string $actionErrorKey, Validator $validator, Context $context)
    {
        $paymentManager = Application::getPaymentManager($context);
        if (!$paymentManager->publicationEnabled()) {
            $validator->errors()->add($actionErrorKey . '.requestPayment', __('payment.requestPublicationFee.notEnabled'));
        } elseif (!isset($action['requestPayment'])) {
            $validator->errors()->add($actionErrorKey . '.requestPayment', __('validator.required'));
        }
    }

    /**
     * Request payment from authors
     */
    protected function requestPayment(Submission $submission, User $editor, Context $context)
    {
        $paymentManager = Application::getPaymentManager($context);
        $queuedPayment = $paymentManager->createQueuedPayment(
            Application::get()->getRequest(),
            OJSPaymentManager::PAYMENT_TYPE_PUBLICATION,
            $editor->getId(),
            $submission->getId(),
            $context->getData('publicationFee'),
            $context->getData('currency')
        );
        $paymentManager->queuePayment($queuedPayment);

        // Notify authors that this needs payment.
        $notificationMgr = new NotificationManager();
        $authorIds = $this->getAssignedAuthorIds($submission);
        foreach ($authorIds as $authorId) {
            $notificationMgr->createNotification(
                Application::get()->getRequest(),
                $authorId,
                Notification::NOTIFICATION_TYPE_PAYMENT_REQUIRED,
                $context->getId(),
                Application::ASSOC_TYPE_QUEUED_PAYMENT,
                $queuedPayment->getId(),
                Notification::NOTIFICATION_LEVEL_TASK
            );

            $mailable = new PaymentRequest($context, $submission, $queuedPayment);
            $template = Repo::emailTemplate()->getByKey($context->getId(), $mailable::getEmailTemplateKey());
            $mailable->from($context->getData('contactEmail'), $context->getData('contactName'))
                ->recipients([Repo::user()->get($authorId)])
                ->subject($template->getLocalizedData('subject'))
                ->body($template->getLocalizedData('body'));

            Mail::send($mailable);
        }
    }
}
