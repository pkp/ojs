<?php

/**
 * @file plugins/generic/webFeed/WebFeedPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WebFeedPlugin
 * @ingroup plugins_block_webFeed
 *
 * @brief Web Feeds plugin class
 */

use APP\core\Application;
use APP\facades\Repo;
use APP\notification\NotificationManager;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;

use PKP\plugins\HookRegistry;
use PKP\plugins\PluginRegistry;

class WebFeedPlugin extends GenericPlugin
{
    /**
     * Get the display name of this plugin
     *
     * @return string
     */
    public function getDisplayName()
    {
        return __('plugins.generic.webfeed.displayName');
    }

    /**
     * Get the description of this plugin
     *
     * @return string
     */
    public function getDescription()
    {
        return __('plugins.generic.webfeed.description');
    }

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
            $this->import('WebFeedBlockPlugin');
            PluginRegistry::register('blocks', new WebFeedBlockPlugin($this), $this->getPluginPath());

            $this->import('WebFeedGatewayPlugin');
            PluginRegistry::register('gateways', new WebFeedGatewayPlugin($this), $this->getPluginPath());
        }
        return true;
    }

    /**
     * Get the name of the settings file to be installed on new context
     * creation.
     *
     * @return string
     */
    public function getContextSpecificPluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    /**
     * Add feed links to page <head> on select/all pages.
     */
    public function callbackAddLinks($hookName, $args)
    {
        // Only page requests will be handled
        $request = Application::get()->getRequest();
        if (!is_a($request->getRouter(), 'PKPPageRouter')) {
            return false;
        }

        $templateManager = & $args[0];
        $currentJournal = $templateManager->getTemplateVars('currentJournal');
        if (is_null($currentJournal)) {
            return;
        }
        $currentIssue = Repo::issue()->getCurrent($currentJournal->getId(), true);

        if (!$currentIssue) {
            return;
        }

        $displayPage = $this->getSetting($currentJournal->getId(), 'displayPage');

        // Define when the <link> elements should appear
        $contexts = 'frontend';
        if ($displayPage == 'homepage') {
            $contexts = ['frontend-index', 'frontend-issue'];
        } elseif ($displayPage == 'issue') {
            $contexts = 'frontend-issue';
        }

        $templateManager->addHeader(
            'webFeedAtom+xml',
            '<link rel="alternate" type="application/atom+xml" href="' . $request->url(null, 'gateway', 'plugin', ['WebFeedGatewayPlugin', 'atom']) . '">',
            [
                'contexts' => $contexts,
            ]
        );
        $templateManager->addHeader(
            'webFeedRdf+xml',
            '<link rel="alternate" type="application/rdf+xml" href="' . $request->url(null, 'gateway', 'plugin', ['WebFeedGatewayPlugin', 'rss']) . '">',
            [
                'contexts' => $contexts,
            ]
        );
        $templateManager->addHeader(
            'webFeedRss+xml',
            '<link rel="alternate" type="application/rss+xml" href="' . $request->url(null, 'gateway', 'plugin', ['WebFeedGatewayPlugin', 'rss2']) . '">',
            [
                'contexts' => $contexts,
            ]
        );

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
                $this->import('WebFeedSettingsForm');
                $form = new WebFeedSettingsForm($this, $request->getContext()->getId());

                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        $notificationManager = new NotificationManager();
                        $notificationManager->createTrivialNotification($request->getUser()->getId());
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
