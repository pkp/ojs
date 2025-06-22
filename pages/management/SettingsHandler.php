<?php

/**
 * @file pages/management/SettingsHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SettingsHandler
 *
 * @ingroup pages_management
 *
 * @brief Handle requests for settings pages.
 */

namespace APP\pages\management;

use APP\core\Application;
use APP\API\v1\reviewers\recommendations\resources\ReviewerRecommendationResource;
use APP\components\forms\context\AccessForm;
use APP\components\forms\context\ArchivingLockssForm;
use APP\components\listPanels\ReviewerRecommendationsListPanel;
use APP\template\TemplateManager;
use PKP\components\forms\context\PKPContextStatisticsForm;
use PKP\components\forms\context\PKPDisableSubmissionsForm;
use PKP\components\forms\context\PKPDoiRegistrationSettingsForm;
use PKP\components\forms\context\PKPDoiSetupSettingsForm;
use PKP\components\forms\context\PKPLicenseForm;
use PKP\components\forms\context\PKPMetadataSettingsForm;
use PKP\components\forms\context\PKPPaymentSettingsForm;
use PKP\components\forms\context\PKPReviewGuidanceForm;
use PKP\components\forms\context\PKPReviewSetupForm;
use PKP\components\forms\context\PKPSearchIndexingForm;
use PKP\core\PKPApplication;
use PKP\core\PKPRequest;
use PKP\pages\management\ManagementHandler;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;
use PKP\security\Role;
use PKP\submission\reviewer\recommendation\ReviewerRecommendation;

class SettingsHandler extends ManagementHandler
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_SITE_ADMIN],
            [
                'access', 'settings',
            ]
        );
        $this->addRoleAssignment(
            Role::ROLE_ID_MANAGER,
            [
                'settings',
            ]
        );
    }

    /**
     * Add the workflow settings page
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function workflow($args, $request)
    {
        parent::workflow($args, $request);

        $this->addReviewFormWorkflowSupport($request);

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->registerClass(PKPReviewGuidanceForm::class, PKPReviewGuidanceForm::class); // FORM_REVIEW_GUIDANCE
        $templateMgr->registerClass(PKPReviewSetupForm::class, PKPReviewSetupForm::class); // FORM_REVIEW_SETUP
        $templateMgr->registerClass(PKPMetadataSettingsForm::class, PKPMetadataSettingsForm::class); // FORM_METADATA_SETTINGS
        $templateMgr->registerClass(PKPDisableSubmissionsForm::class, PKPDisableSubmissionsForm::class); // FORM_DISABLE_SUBMISSIONS
        $templateMgr->display('management/workflow.tpl');
    }

    /**
     * Add support for review related forms in workflow.
     */
    protected function addReviewFormWorkflowSupport(PKPRequest $request): void
    {
        parent::addReviewFormWorkflowSupport($request);

        $templateManager = TemplateManager::getManager($request);
        $components = $templateManager->getState('components');

        $context = $request->getContext();
        $recommendations = ReviewerRecommendation::query()->withContextId($context->getId())->get();

        $reviewerRecommendationsListPanel = new ReviewerRecommendationsListPanel(
            __('manager.reviewerRecommendations'),
            $context,
            $this->getSupportedFormLocales($context),
            array_values(
                ReviewerRecommendationResource::collection($recommendations)
                    ->toArray(app()->get('request'))
            ),
            $recommendations->count()
        );

        $components[$reviewerRecommendationsListPanel->id] = $reviewerRecommendationsListPanel->getConfig();
        $templateManager->setState(['components' => $components]);
        $templateManager->assign('hasCustomizableRecommendation', Application::get()->hasCustomizableReviewerRecommendation());
    }

    /**
     * Add the archive and payments tabs to the distribution settings page
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function distribution($args, $request)
    {
        parent::distribution($args, $request);

        $templateMgr = TemplateManager::getManager($request);
        $context = $request->getContext();
        $router = $request->getRouter();
        $dispatcher = $request->getDispatcher();

        $apiUrl = $this->getContextApiUrl($request);
        $lockssUrl = $router->url($request, $context->getPath(), 'gateway', 'lockss');
        $clockssUrl = $router->url($request, $context->getPath(), 'gateway', 'clockss');

        $locales = $this->getSupportedFormLocales($context);

        $accessForm = new AccessForm($apiUrl, $locales, $context);
        $archivingLockssForm = new ArchivingLockssForm($apiUrl, $locales, $context, $lockssUrl, $clockssUrl);

        // Create a dummy "form" for the PKP Preservation Network settings. This
        // form loads a single field which enables/disables the plugin, and does
        // not need to be submitted. It's a dirty hack, but we can change this once
        // an API is in place for plugins and plugin settings.
        $plnPlugin = PluginRegistry::getPlugin('generic', 'plnplugin');
        $archivePnForm = new \PKP\components\forms\FormComponent('archivePn', 'PUT', 'dummy', $locales);
        $archivePnForm->addPage([
            'id' => 'default',
            'submitButton' => null,
        ])
            ->addGroup([
                'id' => 'default',
                'pageId' => 'default',
            ]);

        if ($plnPlugin) {
            $plnPlugin = PluginRegistry::getPlugin('generic', 'plnplugin');
            $pnEnablePluginUrl = $dispatcher->url($request, PKPApplication::ROUTE_COMPONENT, null, 'grid.settings.plugins.SettingsPluginGridHandler', 'enable', null, ['plugin' => 'plnplugin', 'category' => 'generic']);
            $pnDisablePluginUrl = $dispatcher->url($request, PKPApplication::ROUTE_COMPONENT, null, 'grid.settings.plugins.SettingsPluginGridHandler', 'disable', null, ['plugin' => 'plnplugin', 'category' => 'generic']);
            $pnSettingsUrl = $dispatcher->url($request, PKPApplication::ROUTE_COMPONENT, null, 'grid.settings.plugins.SettingsPluginGridHandler', 'manage', null, ['verb' => 'settings', 'plugin' => 'plnplugin', 'category' => 'generic']);

            $archivePnForm->addField(new \APP\components\forms\FieldArchivingPn('pn', [
                'label' => __('manager.setup.plnPluginArchiving'),
                'description' => __('manager.setup.plnDescription'),
                'terms' => __('manager.setup.plnSettingsDescription'),
                'options' => [
                    [
                        'value' => true,
                        'label' => __('manager.setup.plnPluginEnable'),
                    ],
                ],
                'value' => (bool) $plnPlugin,
                'enablePluginUrl' => $pnEnablePluginUrl,
                'disablePluginUrl' => $pnDisablePluginUrl,
                'settingsUrl' => $pnSettingsUrl,
                'csrfToken' => $request->getSession()->token(),
                'groupId' => 'default',
                'enablePluginSuccess' => __('common.pluginEnabled', ['pluginName' => __('manager.setup.plnPluginArchiving')]),
                'disablePluginSuccess' => __('common.pluginDisabled', ['pluginName' => __('manager.setup.plnPluginArchiving')]),
            ]));
        } else {
            $archivePnForm->addField(new \PKP\components\forms\FieldHTML('pn', [
                'label' => __('manager.setup.plnPluginArchiving'),
                'description' => __('manager.setup.plnPluginNotInstalled'),
                'groupId' => 'default',
            ]));
        }

        // Add forms to the existing settings data
        $components = $templateMgr->getState('components');
        $components[$accessForm->id] = $accessForm->getConfig();
        $components[$archivingLockssForm->id] = $archivingLockssForm->getConfig();
        $components[$archivePnForm->id] = $archivePnForm->getConfig();
        $templateMgr->setState(['components' => $components]);

        // Add a payments link to be added/removed when payments form submitted
        $templateMgr->setState([
            'paymentsNavLink' => [
                'name' => __('common.payments'),
                'url' => $router->url($request, null, 'payments'),
                'isCurrent' => false,
            ],
        ]);

        // Hook into the settings templates to add the appropriate tabs
        Hook::add('Template::Settings::distribution', function ($hookName, $args) {
            $templateMgr = $args[1];
            $templateMgr->registerClass(AccessForm::class, AccessForm::class); // FORM_ACCESS
            $templateMgr->registerClass(ArchivingLockssForm::class, ArchivingLockssForm::class); // FORM_ARCHIVING_LOCKSS
            $output = &$args[2];
            $output .= $templateMgr->fetch('management/additionalDistributionTabs.tpl');
            return false;
        });

        $templateMgr->registerClass(PKPLicenseForm::class, PKPLicenseForm::class); // FORM_LICENSE
        $templateMgr->registerClass(PKPContextStatisticsForm::class, PKPContextStatisticsForm::class); // FORM_CONTEXT_STATISTICS
        $templateMgr->registerClass(PKPPaymentSettingsForm::class, PKPPaymentSettingsForm::class); // FORM_CONTEXT_STATISTICS
        $templateMgr->registerClass(PKPSearchIndexingForm::class, PKPSearchIndexingForm::class); // FORM_SEARCH_INDEXING
        $templateMgr->registerClass(PKPDoiRegistrationSettingsForm::class, PKPDoiRegistrationSettingsForm::class); // FORM_DOI_REGISTRATION_SETTINGS
        $templateMgr->registerClass(PKPDoiSetupSettingsForm::class, PKPDoiSetupSettingsForm::class); // FORM_DOI_SETUP_SETTINGS
        $templateMgr->display('management/distribution.tpl');
    }

    protected function getEmailFromFilters(): array
    {
        $filters = parent::getEmailFromFilters();
        $filters[Role::ROLE_ID_SUBSCRIPTION_MANAGER] = __('user.role.subscriptionManager');
        return $filters;
    }

    protected function getEmailToFilters(): array
    {
        $filters = parent::getEmailToFilters();
        $filters[Role::ROLE_ID_SUBSCRIPTION_MANAGER] = __('user.role.subscriptionManager');
        return $filters;
    }
}
