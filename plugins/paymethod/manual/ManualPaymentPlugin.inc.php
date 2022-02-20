<?php

/**
 * @file plugins/paymethod/manual/ManualPaymentPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ManualPaymentPlugin
 * @ingroup plugins_paymethod_manual
 *
 * @brief Manual payment plugin class
 */

use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\mail\MailTemplate;
use PKP\plugins\PaymethodPlugin;

class ManualPaymentPlugin extends PaymethodPlugin
{
    /**
     * @copydoc Plugin::getName
     */
    public function getName()
    {
        return 'ManualPayment';
    }

    /**
     * @copydoc Plugin::getDisplayName
     */
    public function getDisplayName()
    {
        return __('plugins.paymethod.manual.displayName');
    }

    /**
     * @copydoc Plugin::getDescription
     */
    public function getDescription()
    {
        return __('plugins.paymethod.manual.description');
    }

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        if (parent::register($category, $path, $mainContextId)) {
            $this->addLocaleData();
            \HookRegistry::register('Form::config::before', [$this, 'addSettings']);
            return true;
        }
        return false;
    }

    /**
     * Add settings to the payments form
     *
     * @param string $hookName
     * @param FormComponent $form
     */
    public function addSettings($hookName, $form)
    {
        import('lib.pkp.classes.components.forms.context.PKPPaymentSettingsForm'); // Load constant
        if ($form->id !== FORM_PAYMENT_SETTINGS) {
            return;
        }

        $context = Application::get()->getRequest()->getContext();
        if (!$context) {
            return;
        }

        $form->addGroup([
            'id' => 'manualPayment',
            'label' => __('plugins.paymethod.manual.displayName'),
            'showWhen' => 'paymentsEnabled',
        ])
            ->addField(new \PKP\components\forms\FieldTextArea('manualInstructions', [
                'label' => __('plugins.paymethod.manual.settings'),
                'value' => $this->getSetting($context->getId(), 'manualInstructions'),
                'groupId' => 'manualPayment',
            ]));

        return;
    }

    /**
     * @copydoc PaymethodPlugin::saveSettings()
     */
    public function saveSettings($params, $slimRequest, $request)
    {
        $allParams = $slimRequest->getParsedBody();
        $manualInstructions = isset($allParams['manualInstructions']) ? (string) $allParams['manualInstructions'] : '';
        $this->updateSetting($request->getContext()->getId(), 'manualInstructions', $manualInstructions);
        return [];
    }

    /**
     * @copydoc PaymethodPlugin::isConfigured
     */
    public function isConfigured($context)
    {
        if (!$context) {
            return false;
        }
        if ($this->getSetting($context->getId(), 'manualInstructions') == '') {
            return false;
        }
        return true;
    }

    /**
     * @copydoc PaymethodPlugin::getPaymentForm
     */
    public function getPaymentForm($context, $queuedPayment)
    {
        if (!$this->isConfigured($context)) {
            return null;
        }

        $paymentForm = new Form($this->getTemplateResource('paymentForm.tpl'));
        $paymentManager = Application::getPaymentManager($context);
        $paymentForm->setData([
            'itemName' => $paymentManager->getPaymentName($queuedPayment),
            'itemAmount' => $queuedPayment->getAmount() > 0 ? $queuedPayment->getAmount() : null,
            'itemCurrencyCode' => $queuedPayment->getAmount() > 0 ? $queuedPayment->getCurrencyCode() : null,
            'manualInstructions' => $this->getSetting($context->getId(), 'manualInstructions'),
            'queuedPaymentId' => $queuedPayment->getId(),
        ]);
        return $paymentForm;
    }

    /**
     * Handle incoming requests/notifications
     *
     * @param array $args
     * @param PKPRequest $request
     */
    public function handle($args, $request)
    {
        $context = $request->getContext();
        $templateMgr = TemplateManager::getManager($request);
        $user = $request->getUser();
        $op = $args[0] ?? null;
        $queuedPaymentId = isset($args[1]) ? ((int) $args[1]) : 0;

        $queuedPaymentDao = DAORegistry::getDAO('QueuedPaymentDAO'); /** @var QueuedPaymentDAO $queuedPaymentDao */
        $queuedPayment = $queuedPaymentDao->getById($queuedPaymentId);
        $paymentManager = Application::getPaymentManager($context);
        // if the queued payment doesn't exist, redirect away from payments
        if (!$queuedPayment) {
            $request->redirect(null, 'index');
        }

        switch ($op) {
            case 'notify':
                $contactName = $context->getData('contactName');
                $contactEmail = $context->getData('contactEmail');
                $mail = new MailTemplate('MANUAL_PAYMENT_NOTIFICATION');
                $mail->setReplyTo(null);
                $mail->addRecipient($contactEmail, $contactName);
                $mail->assignParams([
                    'contextName' => $context->getLocalizedName(),
                    'userFullName' => $user ? $user->getFullName() : ('(' . __('common.none') . ')'),
                    'userName' => $user ? $user->getUsername() : ('(' . __('common.none') . ')'),
                    'itemName' => $paymentManager->getPaymentName($queuedPayment),
                    'itemCost' => $queuedPayment->getAmount(),
                    'itemCurrencyCode' => $queuedPayment->getCurrencyCode()
                ]);
                $mail->send();

                $templateMgr->assign([
                    'currentUrl' => $request->url(null, null, 'payment', 'plugin', ['notify', $queuedPaymentId]),
                    'pageTitle' => 'plugins.paymethod.manual.paymentNotification',
                    'message' => 'plugins.paymethod.manual.notificationSent',
                    'backLink' => $queuedPayment->getRequestUrl(),
                    'backLinkLabel' => 'common.continue'
                ]);
                $templateMgr->display('frontend/pages/message.tpl');
                exit;
        }
        parent::handle($args, $request); // Don't know what to do with it
    }

    /**
     * @copydoc Plugin::getInstallEmailTemplatesFile
     */
    public function getInstallEmailTemplatesFile()
    {
        return "{$this->getPluginPath()}/emailTemplates.xml";
    }
}
