<?php

/**
 * @file /pages/dois/DoiManagementHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoisHandler
 * @ingroup pages_doi
 *
 * @brief Handle requests for DOI management functions.
 */

use APP\components\forms\context\DoiSetupSettingsForm;
use APP\components\listPanels\DoiListPanel;
use APP\facades\Repo;
use PKP\components\forms\context\PKPDoiRegistrationSettingsForm;
use PKP\components\forms\context\PKPDoiSetupSettingsForm;
use PKP\plugins\HookRegistry;
use PKP\plugins\IPKPDoiRegistrationAgency;

import('lib.pkp.pages.dois.PKPDoisHandler');

class DoisHandler extends PKPDoisHandler
{
    /**
     * Displays the DOI management page
     *
     * @param array $args
     * @param \PKP\handler\PKPRequest $request
     */
    public function index($args, $request)
    {
        $this->setupTemplate($request);

        $context = $request->getContext();
        $contextId = $context->getId();

        $enabledDoiTypes = $context->getData(Context::SETTING_ENABLED_DOI_TYPES);

        $templateMgr = TemplateManager::getManager($request);

        $commonArgs = [
            'doiPrefix' => $context->getData(Context::SETTING_DOI_PREFIX),
            'doiApiUrl' => $request->getDispatcher()->url($request, PKPApplication::ROUTE_API, $context->getPath(), 'dois'),
            'lazyLoad' => true,
            'enabledDoiTypes' => $enabledDoiTypes,
            'registrationAgencyInfo' => $this->_getRegistrationAgencyInfo($context),
        ];

        HookRegistry::call('DoisHandler::setListPanelArgs', [&$commonArgs]);

        $stateComponents = [];

        // Publication and Galley DOIs
        if (count(array_intersect($enabledDoiTypes, [Repo::doi()::TYPE_PUBLICATION, Repo::doi()::TYPE_REPRESENTATION])) > 0) {
            $submissionDoiListPanel = new DoiListPanel(
                'submissionDoiListPanel',
                __('plugins.pubIds.doi.manager.articleDois'),
                array_merge(
                    $commonArgs,
                    [
                        'apiUrl' => $request->getDispatcher()->url($request, PKPApplication::ROUTE_API, $context->getPath(), 'submissions'),
                        'getParams' => [
                            'stageIds' => [WORKFLOW_STAGE_ID_PUBLISHED, WORKFLOW_STAGE_ID_PRODUCTION],
                        ],
                        'isSubmission' => true,
                        'includeIssuesFilter' => true,
                        'itemType' => 'submission'
                    ]
                )
            );
            $stateComponents[$submissionDoiListPanel->id] = $submissionDoiListPanel->getConfig();
        }

        // Issues DOIs
        if (in_array(Repo::doi()::TYPE_ISSUE, $enabledDoiTypes)) {
            $issueDoiListPanel = new DoiListPanel(
                'issueDoiListPanel',
                __('plugins.pubIds.doi.manager.issueDois'),
                array_merge(
                    $commonArgs,
                    [
                        'apiUrl' => $request->getDispatcher()->url($request, PKPApplication::ROUTE_API, $context->getPath(), 'issues'),
                        'getParams' => [],
                        'isSubmission' => false,
                        'includeIssuesFilter' => false,
                        'itemType' => 'issue',
                    ]
                )
            );
            $stateComponents[$issueDoiListPanel->id] = $issueDoiListPanel->getConfig();
        }

        // DOI settings
        $router = $request->getRouter();
        $dispatcher = $request->getDispatcher();

        $contextApiUrl = $dispatcher->url($request, PKPApplication::ROUTE_API, $context->getPath(), 'contexts/' . $context->getId());
        $supportedFormLocales = $context->getSupportedFormLocales();
        $localeNames = AppLocale::getAllLocales();
        $locales = array_map(function ($localeKey) use ($localeNames) {
            return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
        }, $supportedFormLocales);

        $doiSetupSettingsForm = new DoiSetupSettingsForm($contextApiUrl, $locales, $context);
        $doiRegistrationSettingsForm = new PKPDoiRegistrationSettingsForm($contextApiUrl, $locales, $context);
        $stateComponents[PKPDoiSetupSettingsForm::FORM_DOI_SETUP_SETTINGS] = $doiSetupSettingsForm->getConfig();
        $stateComponents[PKPDoiRegistrationSettingsForm::FORM_DOI_REGISTRATION_SETTINGS] = $doiRegistrationSettingsForm->getConfig();

        $templateMgr->setState(['components' => $stateComponents]);

        $templateMgr->assign([
            'pageTitle' => __('plugins.pubIds.doi.manager.displayName'),
            'displayArticlesTab' => count(array_intersect($enabledDoiTypes, [Repo::doi()::TYPE_PUBLICATION, Repo::doi()::TYPE_REPRESENTATION])) > 0,
            'displayIssuesTab' => in_array(Repo::doi()::TYPE_ISSUE, $enabledDoiTypes),
        ]);

        $templateMgr->display('management/dois.tpl');
    }

    private function _getRegistrationAgencyInfo(\PKP\context\Context $context): stdClass
    {
        $info = new stdClass();
        $info->isConfigured = false;
        $info->displayName = '';
        $info->errorMessageKey = null;
        $info->registeredMessageKey = null;
        $info->errorMessagePreamble = null;
        $info->registeredMessagePreamble = null;

        /** @var IPKPDoiRegistrationAgency $plugin */
        $plugin = $context->getConfiguredDoiAgency();
        if ($plugin != null) {
            $info->isConfigured = $plugin->isPluginConfigured($context);
            $info->displayName = $plugin->getRegistrationAgencyName();
            $info->errorMessageKey = $plugin->getErrorMessageKey();
            $info->registeredMessageKey = $plugin->getRegisteredMessageKey();
            $info->errorMessagePreamble = __('manager.dois.registrationAgency.errorMessagePreamble', ['registrationAgency' => $plugin->getRegistrationAgencyName()]);
            $info->registeredMessagePreamble = __('manager.dois.registrationAgency.registrationMessagePreamble', ['registrationAgency' => $plugin->getRegistrationAgencyName()]);
        }

        return $info;
    }
}
