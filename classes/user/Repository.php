<?php
/**
 * @file classes/user/Repository.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repository
 *
 * @brief A repository to find and manage users.
 */

namespace APP\user;

use APP\payment\ojs\OJSCompletedPaymentDAO;
use APP\subscription\IndividualSubscriptionDAO;
use APP\subscription\InstitutionalSubscriptionDAO;
use PKP\db\DAORegistry;

class Repository extends \PKP\user\Repository
{
    /**
     * @copydoc \PKP\user\Repository::mergeUsers()
     */
    public function mergeUsers($oldUserId, $newUserId)
    {
        if (!parent::mergeUsers($oldUserId, $newUserId)) {
            return false;
        }

        // Transfer old user's individual subscriptions for each journal if new user
        // does not have a valid individual subscription for a given journal.
        $individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $individualSubscriptionDao */
        $oldUserSubscriptions = $individualSubscriptionDao->getByUserId($oldUserId);

        while ($oldUserSubscription = $oldUserSubscriptions->next()) {
            $subscriptionJournalId = $oldUserSubscription->getJournalId();
            $oldUserValidSubscription = $individualSubscriptionDao->isValidIndividualSubscription($oldUserId, $subscriptionJournalId);
            if ($oldUserValidSubscription) {
                // Check if new user has a valid subscription for current journal
                $newUserSubscription = $individualSubscriptionDao->getByUserIdForJournal($newUserId, $subscriptionJournalId);
                if (!$newUserSubscription) {
                    // New user does not have this subscription, transfer old user's
                    $oldUserSubscription->setUserId($newUserId);
                    $individualSubscriptionDao->updateObject($oldUserSubscription);
                } elseif (!$individualSubscriptionDao->isValidIndividualSubscription($newUserId, $subscriptionJournalId)) {
                    // New user has a subscription but it's invalid. Delete it and
                    // transfer old user's valid one
                    $individualSubscriptionDao->deleteByUserIdForJournal($newUserId, $subscriptionJournalId);
                    $oldUserSubscription->setUserId($newUserId);
                    $individualSubscriptionDao->updateObject($oldUserSubscription);
                }
            }
        }

        // Delete any remaining old user's subscriptions not transferred to new user
        $individualSubscriptionDao->deleteByUserId($oldUserId);

        // Transfer all old user's institutional subscriptions for each journal to
        // new user. New user now becomes the contact person for these.
        $institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /** @var InstitutionalSubscriptionDAO $institutionalSubscriptionDao */
        $oldUserSubscriptions = $institutionalSubscriptionDao->getByUserId($oldUserId);

        while ($oldUserSubscription = $oldUserSubscriptions->next()) {
            $oldUserSubscription->setUserId($newUserId);
            $institutionalSubscriptionDao->updateObject($oldUserSubscription);
        }

        // Transfer completed payments.
        $paymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO'); /** @var OJSCompletedPaymentDAO $paymentDao */
        $paymentFactory = $paymentDao->getByUserId($oldUserId);
        while ($payment = $paymentFactory->next()) {
            $payment->setUserId($newUserId);
            $paymentDao->updateObject($payment);
        }

        return true;
    }
}
