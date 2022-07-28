<?php

/**
 * @file controllers/grid/subscriptions/InstitutionalSubscriptionForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InstitutionalSubscriptionForm
 * @ingroup subscription
 *
 * @brief Form class for institutional subscription create/edits.
 */

namespace APP\controllers\grid\subscriptions;

use APP\core\Application;
use APP\facades\Repo;
use APP\notification\NotificationManager;
use APP\subscription\form\SubscriptionForm;
use APP\subscription\InstitutionalSubscription;
use APP\subscription\SubscriptionType;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use PKP\notification\PKPNotification;

class InstitutionalSubscriptionForm extends SubscriptionForm
{
    /** @var array of the journal institutions [institutionId => name] */
    public array $institutions;

    /**
     * Constructor
     *
     * @param PKPRequest $request
     * @param int $subscriptionId leave as default for new subscription
     */
    public function __construct($request, $subscriptionId = null)
    {
        parent::__construct('payments/institutionalSubscriptionForm.tpl', $subscriptionId);

        $subscriptionId = isset($subscriptionId) ? (int) $subscriptionId : null;
        $userId = isset($userId) ? (int) $userId : null;

        $journal = $request->getJournal();
        $journalId = $journal->getId();

        $subscriptionInstitutionId = null;

        if (isset($subscriptionId)) {
            $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
            if ($subscriptionDao->subscriptionExists($subscriptionId)) {
                $this->subscription = $subscriptionDao->getById($subscriptionId);
                $subscriptionInstitutionId = $this->subscription->getInstitutionId();
            }
        }

        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionTypeIterator = $subscriptionTypeDao->getByInstitutional($journalId, true);
        $this->subscriptionTypes = [];
        while ($subscriptionType = $subscriptionTypeIterator->next()) {
            $this->subscriptionTypes[$subscriptionType->getId()] = $subscriptionType->getSummaryString();
        }
        if (count($this->subscriptionTypes) == 0) {
            $this->addError('typeId', __('manager.subscriptions.form.typeRequired'));
            $this->addErrorField('typeId');
        }

        $collector = Repo::institution()->getCollector()->filterByContextIds([$journalId]);
        $institutions = Repo::institution()->getMany($collector)->values();
        $this->institutions = [];
        foreach ($institutions as $institution) {
            $this->institutions[$institution->getId()] = $institution->getLocalizedName();
        }
        if (isset($subscriptionInstitutionId) && !array_key_exists($subscriptionInstitutionId, $this->institutions)) {
            // The institution is soft deleted, add it to the institutions list
            $subscriptionInstitution = Repo::institution()->get($subscriptionInstitutionId);
            $this->institutions[$subscriptionInstitutionId] = $subscriptionInstitution->getLocalizedName();
        }
        if (!count($this->institutions)) {
            $this->addError('institutionId', __('manager.subscriptions.form.institutionRequired'));
            $this->addErrorField('institutionId');
        }

        // Ensure subscription type is valid
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'typeId', 'required', 'manager.subscriptions.form.typeIdValid', function ($typeId) use ($journalId) {
            $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
            return $subscriptionTypeDao->subscriptionTypeExistsByTypeId($typeId, $journalId) && $subscriptionTypeDao->getSubscriptionTypeInstitutional($typeId);
        }));

        // Ensure institution ID exists
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'institutionId', 'required', 'manager.subscriptions.form.institutionIdValid', function ($institutionId) use ($journalId, $subscriptionInstitutionId) {
            return ($institutionId == $subscriptionInstitutionId) || Repo::institution()->existsInContext($institutionId, $journalId);
        }));

        // If provided, domain is valid
        $this->addCheck(new \PKP\form\validation\FormValidatorRegExp($this, 'domain', 'optional', 'manager.subscriptions.form.domainValid', '/^' .
                '[A-Z0-9]+([\-_\.][A-Z0-9]+)*' .
                '\.' .
                '[A-Z]{2,4}' .
            '$/i'));
    }

    /**
     * @copydoc Form::fetch
     *
     * @param null|string $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'institutions' => $this->institutions,
        ]);
        return parent::fetch($request, $template, $display);
    }

    /**
     * Initialize form data from current subscription.
     */
    public function initData()
    {
        parent::initData();

        if (isset($this->subscription)) {
            $this->_data = array_merge(
                $this->_data,
                [
                    'institutionMailingAddress' => $this->subscription->getInstitutionMailingAddress(),
                    'domain' => $this->subscription->getDomain(),
                    'institutionId' => $this->subscription->getInstitutionId(),
                ]
            );
        }
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        parent::readInputData();
        $this->readUserVars(['institutionMailingAddress', 'domain', 'institutionId']);
    }

    /**
     * @copydoc Form::validate()
     */
    public function validate($callHooks = true)
    {
        if (!parent::validate()) {
            return false;
        }

        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /** @var SubscriptionTypeDAO $subscriptionTypeDao */
        $subscriptionType = $subscriptionTypeDao->getById($this->getData('typeId'));

        $institution = Repo::institution()->get($this->getData('institutionId'));
        $ipRanges = $institution->getIPRanges();

        $domain = $this->getData('domain');
        // If online or print + online, domain or at least one IP range has to be provided
        if ($subscriptionType->getFormat() != SubscriptionType::SUBSCRIPTION_TYPE_FORMAT_PRINT) {
            if (empty($domain) && empty($ipRanges)) {
                $this->addError('domain', __('manager.subscriptions.form.domainIPRangeRequired'));
                $this->addErrorField('domain');
                return false;
            }
        }
        return true;
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $insert = false;
        if (!isset($this->subscription)) {
            $this->subscription = new InstitutionalSubscription();
            $insert = true;
        }

        parent::execute(...$functionArgs);

        $this->subscription->setInstitutionId($this->getData('institutionId'));
        $this->subscription->setInstitutionMailingAddress($this->getData('institutionMailingAddress'));
        $this->subscription->setDomain($this->getData('domain'));

        $institutionalSubscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /* @var InstitutionalSubscriptionDAO $institutionalSubscriptionDao */
        if ($insert) {
            $institutionalSubscriptionDao->insertObject($this->subscription);
        } else {
            $institutionalSubscriptionDao->updateObject($this->subscription);
        }

        // Send notification email
        if ($this->_data['notifyEmail'] == 1) {
            $mail = $this->_prepareNotificationEmail('SUBSCRIPTION_NOTIFY');
            if (!$mail->send()) {
                $notificationMgr = new NotificationManager();
                $request = Application::get()->getRequest();
                $notificationMgr->createTrivialNotification($request->getUser()->getId(), PKPNotification::NOTIFICATION_TYPE_ERROR, ['contents' => __('email.compose.error')]);
            }
        }
    }
}
