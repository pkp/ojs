<?php

/**
 * @file controllers/grid/subscriptions/IndividualSubscriptionForm.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IndividualSubscriptionForm
 *
 * @ingroup subscription
 *
 * @brief Form class for individual subscription create/edits.
 */

namespace APP\controllers\grid\subscriptions;

use APP\core\Application;
use APP\core\Request;
use APP\notification\NotificationManager;
use APP\subscription\form\SubscriptionForm;
use APP\subscription\IndividualSubscription;
use APP\subscription\IndividualSubscriptionDAO;
use APP\subscription\SubscriptionTypeDAO;
use Exception;
use Illuminate\Support\Facades\Mail;
use PKP\db\DAORegistry;
use PKP\notification\PKPNotification;

class IndividualSubscriptionForm extends SubscriptionForm
{
    /**
     * Constructor
     *
     * @param Request $request
     * @param int $subscriptionId leave as default for new subscription
     */
    public function __construct($request, $subscriptionId = null)
    {
        parent::__construct('payments/individualSubscriptionForm.tpl', $subscriptionId);

        $subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;
        $journal = $request->getJournal();
        $journalId = $journal->getId();

        if (isset($subscriptionId)) {
            $subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $subscriptionDao */
            if ($subscriptionDao->subscriptionExists($subscriptionId)) {
                $this->subscription = $subscriptionDao->getById($subscriptionId);
            }
        }

        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionTypeIterator = $subscriptionTypeDao->getByInstitutional($journalId, false);
        $this->subscriptionTypes = [];
        while ($subscriptionType = $subscriptionTypeIterator->next()) {
            $this->subscriptionTypes[$subscriptionType->getId()] = $subscriptionType->getSummaryString();
        }

        if (count($this->subscriptionTypes) == 0) {
            $this->addError('typeId', __('manager.subscriptions.form.typeRequired'));
            $this->addErrorField('typeId');
        }

        // Ensure subscription type is valid
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'typeId', 'required', 'manager.subscriptions.form.typeIdValid', function ($typeId) use ($journalId) {
            $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
            return $subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId) && !$subscriptionTypeDao->getSubscriptionTypeInstitutional($typeId);
        }));

        // Ensure that user does not already have a subscription for this journal
        if (!isset($subscriptionId)) {
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'userId', 'required', 'manager.subscriptions.form.subscriptionExists', [DAORegistry::getDAO('IndividualSubscriptionDAO'), 'subscriptionExistsByUserForJournal'], [$journalId], true));
        } else {
            $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'userId', 'required', 'manager.subscriptions.form.subscriptionExists', function ($userId) use ($journalId, $subscriptionId) {
                $subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $subscriptionDao */
                $checkSubscription = $subscriptionDao->getByUserIdForJournal($userId, $journalId);
                return (!$checkSubscription || $checkSubscription->getId() == $subscriptionId) ? true : false;
            }));
        }
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $insert = false;
        if (!isset($this->subscription)) {
            $this->subscription = new IndividualSubscription();
            $insert = true;
        }

        parent::execute(...$functionArgs);
        $individualSubscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $individualSubscriptionDao */

        if ($insert) {
            $individualSubscriptionDao->insertObject($this->subscription);
        } else {
            $individualSubscriptionDao->updateObject($this->subscription);
        }

        // Send notification email
        if ($this->getData('notifyEmail')) {
            $mailable = $this->_prepareNotificationEmail();
            try {
                Mail::send($mailable);
            } catch (Exception $e) {
                $notificationMgr = new NotificationManager();
                $request = Application::get()->getRequest();
                $notificationMgr->createTrivialNotification($request->getUser()->getId(), PKPNotification::NOTIFICATION_TYPE_ERROR, ['contents' => __('email.compose.error')]);
                error_log($e->getMessage());
            }
        }
    }
}
