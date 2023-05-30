<?php

/**
 * @file plugins/paymethod/manual/ManualPaymentPlugin.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ManualPaymentPlugin
 *
 * @brief Manual payment plugin class
 */

namespace APP\plugins\paymethod\manual;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\journal\Journal;
use APP\plugins\paymethod\manual\mailables\ManualPaymentNotify;
use APP\template\TemplateManager;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use PKP\components\forms\FormComponent;
use PKP\db\DAORegistry;
use PKP\form\Form;
use PKP\install\Installer;
use PKP\payment\QueuedPaymentDAO;
use PKP\plugins\Hook;
use PKP\plugins\PaymethodPlugin;
use Slim\Http\Request as SlimRequest;

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
            Hook::add('Form::config::before', [$this, 'addSettings']);
            Hook::add('Mailer::Mailables', [$this, 'addMailable']);
            Hook::add('Installer::postInstall', [$this, 'updateSchema']);
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
            ->addField(new \PKP\components\forms\FieldTextarea('manualInstructions', [
                'label' => __('plugins.paymethod.manual.settings'),
                'value' => $this->getSetting($context->getId(), 'manualInstructions'),
                'groupId' => 'manualPayment',
            ]));

        return;
    }

    /**
     * @copydoc PaymethodPlugin::saveSettings
     */
    public function saveSettings(string $hookname, array $args)
    {
        $slimRequest = $args[0]; /** @var SlimRequest $slimRequest */
        $request = $args[1]; /** @var Request $request */
        $updatedSettings = $args[3]; /** @var Collection $updatedSettings */

        $allParams = $slimRequest->getParsedBody();
        $manualInstructions = isset($allParams['manualInstructions']) ? (string) $allParams['manualInstructions'] : '';
        $this->updateSetting($request->getContext()->getId(), 'manualInstructions', $manualInstructions);
        $updatedSettings->put('manualInstructions', $manualInstructions);
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
     * @param Request $request
     */
    public function handle($args, $request)
    {
        $context = $request->getContext(); /** @var Journal $context */
        $templateMgr = TemplateManager::getManager($request);
        $user = $request->getUser();
        $op = $args[0] ?? null;
        $queuedPaymentId = isset($args[1]) ? ((int) $args[1]) : 0;

        $queuedPaymentDao = DAORegistry::getDAO('QueuedPaymentDAO'); /** @var QueuedPaymentDAO $queuedPaymentDao */
        $queuedPayment = $queuedPaymentDao->getById($queuedPaymentId);

        // if the queued payment doesn't exist, redirect away from payments
        if (!$queuedPayment) {
            $request->redirect(null, 'index');
        }

        switch ($op) {
            case 'notify':
                $mailable = new ManualPaymentNotify($context, $queuedPayment);
                $template = Repo::emailTemplate()->getByKey($context->getId(), $mailable::getEmailTemplateKey());
                $locale = $context->getPrimaryLocale();
                $mailable
                    ->sender($user)
                    ->to($context->getData('contactEmail'), $context->getData('contactName'))
                    ->subject($template->getLocalizedData('subject', $locale))
                    ->body($template->getLocalizedData('body', $locale));

                Mail::send($mailable);

                $templateMgr->assign([
                    'currentUrl' => $request->url(null, null, 'payment', 'plugin', ['notify', $queuedPaymentId]),
                    'pageTitle' => 'plugins.paymethod.manual.paymentNotification',
                    'message' => 'plugins.paymethod.manual.notificationSent',
                    'backLink' => $queuedPayment->getRequestUrl(),
                    'backLinkLabel' => 'common.continue'
                ]);
                $templateMgr->display('frontend/pages/message.tpl');
                exit;
            default:
                throw new Exception("Invalid payment operation: {$op}");
        }
    }

    /**
     * @copydoc Plugin::getInstallEmailTemplatesFile
     */
    public function getInstallEmailTemplatesFile()
    {
        return "{$this->getPluginPath()}/emailTemplates.xml";
    }

    /**
     * Add mailable to the list of mailables in the application
     */
    public function addMailable(string $hookName, array $args): void
    {
        $args[0]->push(ManualPaymentNotify::class);
    }

    /**
     * @copydoc Plugin::updateSchema()
     */
    public function updateSchema($hookName, $args)
    {
        $installer = $args[0];
        $result = & $args[1];
        $migration = new ManualPaymentEmailDataMigration($installer, $this);
        try {
            $migration->up();
        } catch (Exception $e) {
            $installer->setError(Installer::INSTALLER_ERROR_DB, __('installer.installMigrationError', ['class' => get_class($migration), 'message' => $e->getMessage()]));
            $result = false;
        }

        return false;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\paymethod\manual\ManualPaymentPlugin', '\ManualPaymentPlugin');
}
