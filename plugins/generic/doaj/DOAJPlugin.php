<?php

/**
 * @file plugins/generic/doaj/DOAJPlugin.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under The MIT License. For full terms see the file LICENSE.
 *
 * @class DOAJPlugin
 *
 * @brief Plugin to let register articles or article versions with DOAJ
 *
 */

namespace APP\plugins\generic\doaj;

use APP\plugins\PubObjectsExportGenericPlugin;
use PKP\plugins\PluginRegistry;

class DOAJPlugin extends PubObjectsExportGenericPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        return parent::register($category, $path, $mainContextId);
    }

    public function getDisplayName(): string
    {
        return __('plugins.generic.doaj.displayName');
    }

    public function getDescription(): string
    {
        return __('plugins.generic.doaj.description');
    }

    protected function setExportPlugin(): void
    {
        PluginRegistry::register('importexport', new DOAJExportPlugin(), $this->getPluginPath());
        $this->exportPlugin = PluginRegistry::getPlugin('importexport', 'DOAJExportPlugin');
    }

    /**
     * @copydoc Plugin::getContextSpecificPluginSettingsFile()
     */
    public function getContextSpecificPluginSettingsFile(): string
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    /**
     * @copydoc Plugin::getInstallSitePluginSettingsFile()
     */
    public function getInstallSitePluginSettingsFile(): string
    {
        return $this->getPluginPath() . '/settings.xml';
    }
}
