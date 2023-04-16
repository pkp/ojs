<?php

/**
 * @file classes/subscription/SubscriptionAction.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionAction
 *
 * @ingroup subscriptions
 *
 * Common actions for subscription management functions.
 */

namespace APP\subscription;

use APP\core\Request;
use APP\facades\Repo;
use APP\notification\NotificationManager;
use Exception;
use Illuminate\Support\Facades\Mail;
use PKP\mail\Mailable;
use PKP\notification\PKPNotification;

class SubscriptionAction
{
    /**
     * Send notification email to Subscription Manager when online payment is completed.
     */
    public static function sendOnlinePaymentNotificationEmail(Request $request, Mailable $mailable): void
    {
        $journal = $request->getJournal();

        $subscriptionContactName = $journal->getData('subscriptionName');
        $subscriptionContactEmail = $journal->getData('subscriptionEmail');

        if (empty($subscriptionContactEmail)) {
            $subscriptionContactEmail = $journal->getData('contactEmail');
            $subscriptionContactName = $journal->getData('contactName');
        }

        if (empty($subscriptionContactEmail)) {
            return;
        }

        $template = Repo::emailTemplate()->getByKey($journal->getId(), $mailable::getEmailTemplateKey());
        $mailable
            ->sender($request->getUser())
            ->replyTo($subscriptionContactEmail, $subscriptionContactName)
            ->to($subscriptionContactEmail, $subscriptionContactName)
            ->subject($template->getLocalizedData('subject', $journal->getPrimaryLocale()))
            ->body($template->getLocalizedData('body', $journal->getPrimaryLocale()));

        try {
            Mail::send($mailable);
        } catch (Exception $e) {
            $notificationMgr = new NotificationManager();
            $notificationMgr->createTrivialNotification(
                $request->getUser()->getId(),
                PKPNotification::NOTIFICATION_TYPE_ERROR,
                ['contents' => __('email.compose.error')]
            );
            error_log($e->getMessage());
        }
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\SubscriptionAction', '\SubscriptionAction');
}
