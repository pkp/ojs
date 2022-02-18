<?php

/**
 * @file plugins/generic/datacite/Datacite.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under The MIT License. For full terms see the file LICENSE.
 *
 * @package plugins.generic.crossRefPlugin
 * @class CrossRefPlugin
 *
 * Plugin to let managers deposit DOIs and metadata to Datacite
 *
 */

use APP\core\Application;
use APP\plugins\IDoiRegistrationAgency;
use PKP\context\Context;
use PKP\core\JSONMessage;
use PKP\form\Form;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\HookRegistry;
use PKP\plugins\PluginRegistry;

class DatacitePlugin extends GenericPlugin implements IDoiRegistrationAgency
{
    /** @var DataciteExportPlugin */
    private $_exportPlugin = null;

    /**
     * @see Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.datacite.displayName');
    }

    /**
     * @see Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.datacite.description');
    }

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success) {
            // If the system isn't installed, or is performing an upgrade, don't
            // register hooks. This will prevent DB access attempts before the
            // schema is installed.
            if (Application::isUnderMaintenance()) {
                return true;
            }

            if ($this->getEnabled($mainContextId)) {
                $this->_pluginInitialization();
            }
        }

        return $success;
    }

    /**
     * Remove plugin as configured registration agency if set at the time plugin is disabled.
     *
     * @copydoc LazyLoadPlugin::setEnabled()
     */
    public function setEnabled($enabled)
    {
        parent::setEnabled($enabled);
        if (!$enabled) {
            $contextId = $this->getCurrentContextId();
            /** @var \PKP\context\ContextDAO $contextDao */
            $contextDao = \APP\core\Application::getContextDAO();
            $context = $contextDao->getById($contextId);
            if ($context->getData(Context::SETTING_CONFIGURED_REGISTRATION_AGENCY) === $this->getName()) {
                $context->setData(Context::SETTING_CONFIGURED_REGISTRATION_AGENCY, Context::SETTING_NO_REGISTRATION_AGENCY);
                $contextDao->updateObject($context);
            }
        }
    }

    /**
     * Extend the website settings tabs to include static pages
     *
     * @param $hookName string The name of the invoked hook
     * @param $args array Hook parameters
     *
     * @return boolean Hook handling status
     */
    public function callBackShowDoiManagementTabs($hookName, $args)
    {
        $templateMgr = $args[1];
        $output = & $args[2];
        $request = Application::get()->getRequest();
        $context = $request->getContext();

        if ($context->getData('registrationAgency') === $this->getName()) {
            $output .= $templateMgr->fetch($this->getTemplateResource('dataciteSettingsTab.tpl'));
        }

        // Permit other plugins to continue interacting with this hook
        return false;
    }

    /**
     * @param \APP\submission\Submission[] $submissions
     *
     */
    public function exportSubmissions(array $submissions, \PKP\context\Context $context): array
    {
        $exportPlugin = $this->_getExportPlugin();
        $xmlErrors = [];

        $items = [];

        foreach ($submissions as $submission) {
            $items[] = $submission;
            foreach ($submission->getGalleys() as $galley) {
                $items[] = $galley;
            }
        }

        $temporaryFileId = $exportPlugin->exportAsDownload($context, $items, 'articles', null, $xmlErrors);
        return ['temporaryFileId' => $temporaryFileId, 'xmlErrors' => $xmlErrors];
    }

    /**
     * @param \APP\submission\Submission[] $submissions
     */
    public function depositSubmissions(array $submissions, \PKP\context\Context $context): array
    {
        $exportPlugin = $this->_getExportPlugin();
        $responseMessage = '';

        $items = [];

        foreach ($submissions as $submission) {
            $items[] = $submission;
            foreach ($submission->getGalleys() as $galley) {
                $items[] = $galley;
            }
        }

        $status = $exportPlugin->exportAndDeposit($context, $items, 'articles', $responseMessage);
        return [
            'hasErrors' => !$status,
            'responseMessage' => $responseMessage
        ];
    }

    /**
     * @param \APP\issue\Issue[] $issues
     *
     */
    public function exportIssues(array $issues, \PKP\context\Context $context): array
    {
        $exportPlugin = $this->_getExportPlugin();
        $xmlErrors = [];

        $temporaryFileId = $exportPlugin->exportAsDownload($context, $issues, 'issues', null, $xmlErrors);
        return ['temporaryFileId' => $temporaryFileId, 'xmlErrors' => $xmlErrors];
    }

    /**
     * @param \APP\issue\Issue $issues
     *
     */
    public function depositIssues(array $issues, \PKP\context\Context $context): array
    {
        $exportPlugin = $this->_getExportPlugin();
        $responseMessage = '';


        $status = $exportPlugin->exportAndDeposit($context, $issues, 'issues', $responseMessage);
        return [
            'hasErrors' => !$status,
            'responseMessage' => $responseMessage
        ];
    }

    /**
     * Includes plugin in list of configurable registration agencies for DOI depositing functionality
     *
     * @param $hookName string DoiSettingsForm::setEnabledRegistrationAgencies
     * @param $args array [
     * @option $enabledRegistrationAgencies array
     * ]
     */
    public function addAsRegistrationAgencyOption(string $hookName, array $args)
    {
        $enabledRegistrationAgencies = &$args[0];
        $enabledRegistrationAgencies[] = [
            'value' => $this->getName(),
            'label' => 'Datacite'
        ];
    }

    /**
     * Checks if plugin meets registration agency-specific requirements for being active and handling deposits
     *
     */
    public function isPluginConfigured(\PKP\context\Context $context): bool
    {
        $this->import('classes.form.DataciteSettingsForm');
        $form = new DataciteSettingsForm($this->_getExportPlugin(), $context->getId());
        $configurationErrors = $this->_getConfigurationErrors($context, $form);

        if (!empty($configurationErrors)) {
            return false;
        }

        return true;
    }

    /**
     * Get configured registration agency display name for use in DOI management pages
     *
     */
    public function getRegistrationAgencyName(): string
    {
        return __('plugins.generic.datacite.registrationAgency.name');
    }

    /**
     * Get key for retrieving error message if one exists on DOI object
     *
     */
    public function getErrorMessageKey(): ?string
    {
        return null;
    }

    /**
     * Get key for retrieving registered message if one exists on DOI object
     *
     */
    public function getRegisteredMessageKey(): ?string
    {
        return null;
    }


    private function _getExportPlugin()
    {
        if (empty($this->_exportPlugin)) {
            $pluginCategory = 'importexport';
            $pluginPathName = 'DataciteExportPlugin';
            $this->_exportPlugin = PluginRegistry::getPlugin($pluginCategory, $pluginPathName);
            // If being run from CLI, there is no context, so plugin initialization would not have been fired
            if ($this->_exportPlugin === null && !isset($_SERVER['SERVER_NAME'])) {
                $this->_pluginInitialization();
                $this->_exportPlugin = PluginRegistry::getPlugin($pluginCategory, $pluginPathName);
            }
        }
        return $this->_exportPlugin;
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $verb)
    {
        $router = $request->getRouter();
        return array_merge(
            $this->getEnabled() ? [
                new LinkAction(
                    'settings',
                    new AjaxModal(
                        $router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']),
                        $this->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                ),
            ] : [],
            parent::getActions($request, $verb)
        );
    }

    /**
     * Helper to register hooks that are used in normal plugin setup and in CLI tool usage.
     */
    private function _pluginInitialization()
    {
        $this->import('DataciteExportPlugin');
        PluginRegistry::register('importexport', new DataciteExportPlugin(), $this->getPluginPath());

        HookRegistry::register('Template::doiManagement', [$this, 'callbackShowDoiManagementTabs']);
        HookRegistry::register('DoiSettingsForm::setEnabledRegistrationAgencies', [$this, 'addAsRegistrationAgencyOption']);
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {

            // Return a JSON response containing the
            // settings form
            case 'settings':
                $context = $request->getContext();

                $this->import('classes.form.DataciteSettingsForm');
                $form = new DataciteSettingsForm($this->_getExportPlugin(), $context->getId());
                $form->initData();

                // Check for configuration errors
                $configurationErrors = $this->_getConfigurationErrors($context, $form);

                $templateMgr = \APP\template\TemplateManager::getManager($request);
                $templateMgr->assign(
                    [
                        'configurationErrors' => $configurationErrors
                    ]
                );

                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

    /**
     * Helper to determine whether plugin has been properly configured
     */
    private function _getConfigurationErrors(Context $context, Form $form = null): array
    {
        $configurationErrors = [];

        foreach ($form->getFormFields() as $fieldName => $fieldType) {
            if ($form->isOptional($fieldName)) {
                continue;
            }
            $pluginSetting = $this->_getExportPlugin()->getSetting($context->getId(), $fieldName);
            if (empty($pluginSetting)) {
                $configurationErrors[] = EXPORT_CONFIG_ERROR_SETTINGS;
                break;
            }
        }
        $doiPrefix = $context->getData(Context::SETTING_DOI_PREFIX);
        if (empty($doiPrefix)) {
            $configurationErrors[] = DOI_EXPORT_CONFIG_ERROR_DOIPREFIX;
        }

        return $configurationErrors;
    }
}
