<?php

/**
 * @file PaypalPaymentForm.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PaypalPaymentForm
 *
 * Form for Paypal-based payments.
 *
 */

namespace APP\plugins\paymethod\paypal;

use APP\core\Application;
use APP\core\Request;
use APP\template\TemplateManager;
use Omnipay\PayPal\Message\RestAuthorizeResponse;
use PKP\config\Config;
use PKP\form\Form;
use PKP\payment\QueuedPayment;

class PaypalPaymentForm extends Form
{
    /** @var PaypalPaymentPlugin */
    public $_paypalPaymentPlugin;

    /** @var QueuedPayment */
    public $_queuedPayment;

    /**
     * @param PaypalPaymentPlugin $paypalPaymentPlugin
     * @param QueuedPayment $queuedPayment
     */
    public function __construct($paypalPaymentPlugin, $queuedPayment)
    {
        $this->_paypalPaymentPlugin = $paypalPaymentPlugin;
        $this->_queuedPayment = $queuedPayment;
        parent::__construct(null);
    }

    /**
     * @copydoc Form::display()
     *
     * @param null|Request $request
     * @param null|mixed $template
     */
    public function display($request = null, $template = null)
    {
        // Application is set to sandbox mode and will not run the features of plugin
        if (Config::getVar('general', 'sandbox', false)) {
            error_log('Application is set to sandbox mode and no payment will be done via paypal');
            TemplateManager::getManager($request)
                ->assign('message', 'common.sandbox')
                ->display('frontend/pages/message.tpl');
            return;
        }

        try {
            $journal = $request->getJournal();
            $paymentManager = Application::getPaymentManager($journal);
            $gateway = \Omnipay\Omnipay::create('PayPal_Rest');
            $gateway->initialize([
                'clientId' => $this->_paypalPaymentPlugin->getSetting($journal->getId(), 'clientId'),
                'secret' => $this->_paypalPaymentPlugin->getSetting($journal->getId(), 'secret'),
                'testMode' => $this->_paypalPaymentPlugin->getSetting($journal->getId(), 'testMode'),
            ]);
            $transaction = $gateway->purchase([
                'amount' => number_format($this->_queuedPayment->getAmount(), 2, '.', ''),
                'currency' => $this->_queuedPayment->getCurrencyCode(),
                'description' => $paymentManager->getPaymentName($this->_queuedPayment),
                'returnUrl' => $request->url(null, 'payment', 'plugin', [$this->_paypalPaymentPlugin->getName(), 'return'], ['queuedPaymentId' => $this->_queuedPayment->getId()]),
                'cancelUrl' => $request->url(null, 'index'),
            ]);
            /** @var RestAuthorizeResponse */
            $response = $transaction->send();
            if ($response->isRedirect()) {
                $request->redirectUrl($response->getRedirectUrl());
            }
            if (!$response->isSuccessful()) {
                throw new \Exception($response->getMessage());
            }
            throw new \Exception('PayPal response was not redirect!');
        } catch (\Exception $e) {
            error_log('PayPal transaction exception: ' . $e->getMessage());
            $templateMgr = TemplateManager::getManager($request);
            $templateMgr->assign('message', 'plugins.paymethod.paypal.error');
            $templateMgr->display('frontend/pages/message.tpl');
        }
    }
}
