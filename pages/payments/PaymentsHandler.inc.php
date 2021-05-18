<?php

/**
 * @file pages/payments/PaymentsHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PaymentsHandler
 * @ingroup pages_payments
 *
 * @brief Handle requests for payment management.
 */

use PKP\core\JSONMessage;
use PKP\security\Role;

use APP\handler\Handler;
use APP\template\TemplateManager;
use APP\notification\NotificationManager;

class PaymentsHandler extends Handler
{
    /** @copydoc PKPHandler::_isBackendPage */
    public $_isBackendPage = true;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUBSCRIPTION_MANAGER],
            ['index', 'subscriptions', 'subscriptionTypes', 'subscriptionPolicies', 'saveSubscriptionPolicies', 'paymentTypes', 'savePaymentTypes', 'payments']
        );
    }

    /**
     * Display a list of payment tabs for the current journal.
     *
     * @param $args array
     * @param $request PKPRequest
     */
    public function index($args, $request)
    {
        $this->setupTemplate($request);
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_MANAGER);
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'pageTitle' => __('manager.subscriptions'),
        ]);
        $templateMgr->display('payments/index.tpl');
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
        $this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Display a list of subscriptions for the current journal.
     *
     * @param $args array
     * @param $request PKPRequest
     */
    public function subscriptions($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $dispatcher = $request->getDispatcher();
        switch (array_shift($args)) {
            case 'institutional':
                return $templateMgr->fetchAjax(
                    'institutionalSubscriptionsGridContainer',
                    $dispatcher->url(
                        $request,
                        PKPApplication::ROUTE_COMPONENT,
                        null,
                        'grid.subscriptions.InstitutionalSubscriptionsGridHandler',
                        'fetchGrid'
                    )
                );
            case 'individual':
                return $templateMgr->fetchAjax(
                    'individualSubscriptionsGridContainer',
                    $dispatcher->url(
                        $request,
                        PKPApplication::ROUTE_COMPONENT,
                        null,
                        'grid.subscriptions.IndividualSubscriptionsGridHandler',
                        'fetchGrid'
                    )
                );
        }
        $dispatcher->handle404();
    }

    /**
     * Display a list of subscription types for the current journal.
     *
     * @param $args array
     * @param $request PKPRequest
     */
    public function subscriptionTypes($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $dispatcher = $request->getDispatcher();
        return $templateMgr->fetchAjax(
            'subscriptionTypesGridContainer',
            $dispatcher->url(
                $request,
                PKPApplication::ROUTE_COMPONENT,
                null,
                'grid.subscriptions.SubscriptionTypesGridHandler',
                'fetchGrid'
            )
        );
    }

    /**
     * Display subscription policies for the current journal.
     *
     * @param $args array
     * @param $request PKPRequest
     */
    public function subscriptionPolicies($args, $request)
    {
        $this->validate();
        $this->setupTemplate($request);

        import('classes.subscription.form.SubscriptionPolicyForm');

        $templateMgr = TemplateManager::getManager($request);

        if (Config::getVar('general', 'scheduled_tasks')) {
            $templateMgr->assign('scheduledTasksEnabled', true);
        }

        $paymentManager = Application::getPaymentManager($request->getJournal());
        $templateMgr->assign('acceptSubscriptionPayments', $paymentManager->isConfigured());

        $subscriptionPolicyForm = new SubscriptionPolicyForm();
        $subscriptionPolicyForm->initData();
        return new JSONMessage(true, $subscriptionPolicyForm->fetch($request));
    }

    /**
     * Save subscription policies for the current journal.
     *
     * @param $args array
     * @param $request PKPRequest
     */
    public function saveSubscriptionPolicies($args, $request)
    {
        $this->validate();
        $this->setupTemplate($request);

        import('classes.subscription.form.SubscriptionPolicyForm');
        $subscriptionPolicyForm = new SubscriptionPolicyForm();
        $subscriptionPolicyForm->readInputData();
        if ($subscriptionPolicyForm->validate()) {
            $subscriptionPolicyForm->execute();
            $notificationManager = new NotificationManager();
            $user = $request->getUser();
            $notificationManager->createTrivialNotification($user->getId());
            return new JSONMessage(true);
        }
        return new JSONMessage(true, $subscriptionPolicyForm->fetch($request));
    }

    /**
     * Display payment types for the current journal.
     *
     * @param $args array
     * @param $request PKPRequest
     */
    public function paymentTypes($args, $request)
    {
        $this->validate();
        $this->setupTemplate($request);

        import('classes.subscription.form.PaymentTypesForm');

        $paymentTypesForm = new PaymentTypesForm();
        $paymentTypesForm->initData();
        return new JSONMessage(true, $paymentTypesForm->fetch($request));
    }

    /**
     * Save payment types for the current journal.
     *
     * @param $args array
     * @param $request PKPRequest
     */
    public function savePaymentTypes($args, $request)
    {
        $this->validate();
        $this->setupTemplate($request);

        import('classes.subscription.form.PaymentTypesForm');
        $paymentTypesForm = new PaymentTypesForm();
        $paymentTypesForm->readInputData();
        if ($paymentTypesForm->validate()) {
            $paymentTypesForm->execute();
            $notificationManager = new NotificationManager();
            $user = $request->getUser();
            $notificationManager->createTrivialNotification($user->getId());
            return new JSONMessage(true);
        }
        return new JSONMessage(true, $paymentTypesForm->fetch($request));
    }

    /**
     * Display a list of payments for the current journal.
     *
     * @param $args array
     * @param $request PKPRequest
     */
    public function payments($args, $request)
    {
        $templateMgr = TemplateManager::getManager($request);
        $dispatcher = $request->getDispatcher();
        return $templateMgr->fetchAjax(
            'paymentsGridContainer',
            $dispatcher->url(
                $request,
                PKPApplication::ROUTE_COMPONENT,
                null,
                'grid.subscriptions.PaymentsGridHandler',
                'fetchGrid'
            )
        );
    }
}
