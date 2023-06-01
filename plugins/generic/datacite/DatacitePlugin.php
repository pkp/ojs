<?php

/**
 * @file plugins/generic/datacite/DatacitePlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under The MIT License. For full terms see the file LICENSE.
 *
 * @class Datacite
 *
 * @brief Plugin to let managers deposit DOIs and metadata to Datacite
 *
 */

namespace APP\plugins\generic\datacite;

use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\issue\Issue;
use APP\plugins\generic\datacite\classes\DataciteSettings;
use APP\plugins\IDoiRegistrationAgency;
use Illuminate\Support\Collection;
use PKP\context\Context;
use PKP\doi\RegistrationAgencySettings;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;
use PKP\services\PKPSchemaService;

class DatacitePlugin extends GenericPlugin implements IDoiRegistrationAgency
{
    private ?DataciteExportPlugin $_exportPlugin = null;
    private DataciteSettings $settingsObject;

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
            $contextDao = Application::getContextDAO();
            $context = $contextDao->getById($contextId);
            if ($context->getData(Context::SETTING_CONFIGURED_REGISTRATION_AGENCY) === $this->getName()) {
                $context->setData(Context::SETTING_CONFIGURED_REGISTRATION_AGENCY, Context::SETTING_NO_REGISTRATION_AGENCY);
                $contextDao->updateObject($context);
            }
        }
    }

    /**
     * @param \APP\submission\Submission[] $submissions
     *
     */
    public function exportSubmissions(array $submissions, Context $context): array
    {
        $exportPlugin = $this->_getExportPlugin();
        $xmlErrors = [];

        $items = [];

        foreach ($submissions as $submission) {
            $items[] = $submission;
            if (in_array(Repo::doi()::TYPE_REPRESENTATION, $context->getEnabledDoiTypes())) {
                foreach ($submission->getGalleys() as $galley) {
                    if ($galley->getDoi()) {
                        $items[] = $galley;
                    }
                }
            }
        }

        $temporaryFileId = $exportPlugin->exportAsDownload($context, $items, null, $xmlErrors);
        return ['temporaryFileId' => $temporaryFileId, 'xmlErrors' => $xmlErrors];
    }

    /**
     * @param \APP\submission\Submission[] $submissions
     */
    public function depositSubmissions(array $submissions, Context $context): array
    {
        $exportPlugin = $this->_getExportPlugin();
        $responseMessage = '';

        $items = [];

        foreach ($submissions as $submission) {
            $items[] = $submission;
            if (in_array(Repo::doi()::TYPE_REPRESENTATION, $context->getEnabledDoiTypes())) {
                foreach ($submission->getGalleys() as $galley) {
                    if ($galley->getDoi()) {
                        $items[] = $galley;
                    }
                }
            }
        }

        $status = $exportPlugin->exportAndDeposit($context, $items, $responseMessage);
        return [
            'hasErrors' => !$status,
            'responseMessage' => $responseMessage
        ];
    }

    /**
     * @param \APP\issue\Issue[] $issues
     *
     */
    public function exportIssues(array $issues, Context $context): array
    {
        $exportPlugin = $this->_getExportPlugin();
        $xmlErrors = [];

        $temporaryFileId = $exportPlugin->exportAsDownload($context, $issues, null, $xmlErrors);
        return ['temporaryFileId' => $temporaryFileId, 'xmlErrors' => $xmlErrors];
    }

    /**
     * @param Issue[] $issues
     *
     */
    public function depositIssues(array $issues, Context $context): array
    {
        $exportPlugin = $this->_getExportPlugin();
        $responseMessage = '';


        $status = $exportPlugin->exportAndDeposit($context, $issues, $responseMessage);
        return [
            'hasErrors' => !$status,
            'responseMessage' => $responseMessage
        ];
    }

    /**
     * Includes plugin in list of configurable registration agencies for DOI depositing functionality
     *
     * @param string $hookName DoiSettingsForm::setEnabledRegistrationAgencies
     * @param array $args [
     *
     * @option $enabledRegistrationAgencies array
     * ]
     */
    public function addAsRegistrationAgencyOption(string $hookName, array $args)
    {
        /** @var Collection<int,IDoiRegistrationAgency> $enabledRegistrationAgencies */
        $enabledRegistrationAgencies = &$args[0];
        $enabledRegistrationAgencies->add($this);
    }

    /**
     * Checks if plugin meets registration agency-specific requirements for being active and handling deposits
     *
     */
    public function isPluginConfigured(Context $context): bool
    {
        $settingsObject = $this->getSettingsObject();

        /** @var PKPSchemaService $schemaService */
        $schemaService = Services::get('schema');
        $requiredProps = $schemaService->getRequiredProps($settingsObject::class);

        foreach ($requiredProps as $requiredProp) {
            $settingValue = $this->getSetting($context->getId(), $requiredProp);
            if (empty($settingValue)) {
                return false;
            }
        }

        $doiPrefix = $context->getData(Context::SETTING_DOI_PREFIX);
        if (empty($doiPrefix)) {
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

    /**
     * @return DataciteExportPlugin
     */
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
     * Helper to register hooks that are used in normal plugin setup and in CLI tool usage.
     */
    private function _pluginInitialization()
    {
        PluginRegistry::register('importexport', new DataciteExportPlugin($this), $this->getPluginPath());

        Hook::add('DoiSettingsForm::setEnabledRegistrationAgencies', [$this, 'addAsRegistrationAgencyOption']);
        Hook::add('DoiSetupSettingsForm::getObjectTypes', [$this, 'addAllowedObjectTypes']);
        Hook::add('DoiListPanel::setConfig', [$this, 'addRegistrationAgencyName']);
    }

    /**
     * Includes human-readable name of registration agency for display in conjunction with how/with whom the
     * DOI was registered.
     *
     * @param string $hookName DoiListPanel::setConfig
     * @param array $args [
     *
     *      @option $config array
     * ]
     */
    public function addRegistrationAgencyName(string $hookName, array $args): bool
    {
        $config = &$args[0];
        $config['registrationAgencyNames'][$this->_getExportPlugin()->getName()] = $this->getRegistrationAgencyName();

        return HOOK::CONTINUE;
    }

    /**
     * Adds self to "allowed" list of pub object types that can be assigned DOIs for this registration agency.
     *
     * @param string $hookName DoiSetupSettingsForm::getObjectTypes
     * @param array $args [
     *
     *      @option array &$objectTypeOptions
     * ]
     */
    public function addAllowedObjectTypes(string $hookName, array $args): bool
    {
        $objectTypeOptions = &$args[0];
        $allowedTypes = $this->getAllowedDoiTypes();

        $objectTypeOptions = array_map(function ($option) use ($allowedTypes) {
            if (in_array($option['value'], $allowedTypes)) {
                $option['allowedBy'][] = $this->getName();
            }
            return $option;
        }, $objectTypeOptions);

        return Hook::CONTINUE;
    }

    /**
     * @inheritDoc
     */
    public function getSettingsObject(): RegistrationAgencySettings
    {
        if (!isset($this->settingsObject)) {
            $this->settingsObject = new DataciteSettings($this);
        }

        return $this->settingsObject;
    }

    /**
     * @inheritDoc
     */
    public function getAllowedDoiTypes(): array
    {
        return [
            Repo::doi()::TYPE_PUBLICATION,
            Repo::doi()::TYPE_REPRESENTATION,
            Repo::doi()::TYPE_ISSUE,
        ];
    }
}
