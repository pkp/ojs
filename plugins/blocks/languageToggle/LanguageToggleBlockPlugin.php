<?php

/**
 * @file plugins/blocks/languageToggle/LanguageToggleBlockPlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LanguageToggleBlockPlugin
 *
 * @brief Class for language selector block plugin
 */

namespace APP\plugins\blocks\languageToggle;

use APP\core\Application;
use PKP\facades\Locale;
use PKP\i18n\LocaleMetadata;
use PKP\plugins\BlockPlugin;
use PKP\session\SessionManager;

class LanguageToggleBlockPlugin extends BlockPlugin
{
    /**
     * Install default settings on system install.
     *
     * @return string
     */
    public function getInstallSitePluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    /**
     * Install default settings on journal creation.
     *
     * @return string
     */
    public function getContextSpecificPluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    /**
     * Get the display name of this plugin.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return __('plugins.block.languageToggle.displayName');
    }

    /**
     * Get a description of the plugin.
     */
    public function getDescription()
    {
        return __('plugins.block.languageToggle.description');
    }

    /**
     * Get the HTML contents for this block.
     *
     * @param \APP\template\TemplateManager $templateMgr
     * @param \APP\core\Request $request
     */
    public function getContents($templateMgr, $request = null)
    {
        $templateMgr->assign('isPostRequest', $request->isPost());

        if (!SessionManager::isDisabled()) {
            $request ??= Application::get()->getRequest();
            $context = $request->getContext();
            $locales = Locale::getFormattedDisplayNames(
                isset($context)
                    ? $context->getSupportedLocales()
                    : $request->getSite()->getSupportedLocales(),
                Locale::getLocales(),
                LocaleMetadata::LANGUAGE_LOCALE_ONLY
            );
        } else {
            $locales = Locale::getFormattedDisplayNames(null, null, LocaleMetadata::LANGUAGE_LOCALE_ONLY);
            $templateMgr->assign('languageToggleNoUser', true);
        }

        if (!empty($locales)) {
            $templateMgr->assign('enableLanguageToggle', true);
            $templateMgr->assign('languageToggleLocales', $locales);
        }

        return parent::getContents($templateMgr, $request);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\blocks\languageToggle\LanguageToggleBlockPlugin', '\LanguageToggleBlockPlugin');
}
