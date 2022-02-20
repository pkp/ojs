<?php

/**
 * @file plugins/generic/announcementFeed/AnnouncementFeedPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementFeedPlugin
 * @ingroup plugins_generic_announcementFeed
 *
 * @brief Annoucement Feed plugin class
 */

use APP\template\TemplateManager;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;

use PKP\plugins\GenericPlugin;

class AnnouncementFeedPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }
        if ($this->getEnabled($mainContextId)) {
            HookRegistry::register('TemplateManager::display', [$this, 'callbackAddLinks']);
            $this->import('AnnouncementFeedBlockPlugin');
            PluginRegistry::register('blocks', new AnnouncementFeedBlockPlugin($this), $this->getPluginPath());

            $this->import('AnnouncementFeedGatewayPlugin');
            PluginRegistry::register('gateways', new AnnouncementFeedGatewayPlugin($this), $this->getPluginPath());
        }
        return true;
    }

    /**
     * Get the display name of this plugin
     *
     * @return string
     */
    public function getDisplayName()
    {
        return __('plugins.generic.announcementfeed.displayName');
    }

    /**
     * Get the description of this plugin
     *
     * @return string
     */
    public function getDescription()
    {
        return __('plugins.generic.announcementfeed.description');
    }

    /**
     * Add links to the feeds.
     *
     * @param string $hookName
     * @param array $args
     *
     * @return bool Hook processing status
     */
    public function callbackAddLinks($hookName, $args)
    {
        $request = Application::get()->getRequest();
        if ($this->getEnabled() && is_a($request->getRouter(), 'PKPPageRouter')) {
            $templateManager = $args[0];
            $currentJournal = $templateManager->getTemplateVars('currentJournal');
            $announcementsEnabled = $currentJournal ? $currentJournal->getData('enableAnnouncements') : false;

            if (!$announcementsEnabled) {
                return false;
            }

            $displayPage = $currentJournal ? $this->getSetting($currentJournal->getId(), 'displayPage') : null;

            // Define when the <link> elements should appear
            $contexts = 'frontend';
            if ($displayPage == 'homepage') {
                $contexts = ['frontend-index', 'frontend-announcement'];
            } elseif ($displayPage == 'announcement') {
                $contexts = 'frontend-' . $displayPage;
            }

            $templateManager->addHeader(
                'announcementsAtom+xml',
                '<link rel="alternate" type="application/atom+xml" href="' . $request->url(null, 'gateway', 'plugin', ['AnnouncementFeedGatewayPlugin', 'atom']) . '">',
                [
                    'contexts' => $contexts,
                ]
            );
            $templateManager->addHeader(
                'announcementsRdf+xml',
                '<link rel="alternate" type="application/rdf+xml" href="' . $request->url(null, 'gateway', 'plugin', ['AnnouncementFeedGatewayPlugin', 'rss']) . '">',
                [
                    'contexts' => $contexts,
                ]
            );
            $templateManager->addHeader(
                'announcementsRss+xml',
                '<link rel="alternate" type="application/rss+xml" href="' . $request->url(null, 'gateway', 'plugin', ['AnnouncementFeedGatewayPlugin', 'rss2']) . '">',
                [
                    'contexts' => $contexts,
                ]
            );
        }

        return false;
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
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $context = $request->getContext();
                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->registerPlugin('function', 'plugin_url', [$this, 'smartyPluginUrl']);

                $this->import('AnnouncementFeedSettingsForm');
                $form = new AnnouncementFeedSettingsForm($this, $context->getId());

                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }
                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }
}
