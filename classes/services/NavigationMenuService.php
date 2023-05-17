<?php

/**
 * @file classes/services/NavigationMenuService.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NavigationMenuService
 *
 * @ingroup services
 *
 * @brief Helper class that encapsulates NavigationMenu business logic
 */

namespace APP\services;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\plugins\Hook;
use PKP\security\Validation;

class NavigationMenuService extends \PKP\services\PKPNavigationMenuService
{
    // Types for all ojs default navigationMenuItems
    public const NMI_TYPE_SUBSCRIPTIONS = 'NMI_TYPE_SUBSCRIPTIONS';
    public const NMI_TYPE_MY_SUBSCRIPTIONS = 'NMI_TYPE_MY_SUBSCRIPTIONS';
    public const NMI_TYPE_CURRENT = 'NMI_TYPE_CURRENT';
    public const NMI_TYPE_ARCHIVES = 'NMI_TYPE_ARCHIVES';

    /**
     * Initialize hooks for extending PKPNavigationMenuService
     */
    public function __construct()
    {
        Hook::add('NavigationMenus::itemTypes', [$this, 'getMenuItemTypesCallback']);
        Hook::add('NavigationMenus::displaySettings', [$this, 'getDisplayStatusCallback']);
    }

    /**
     * Return all default navigationMenuItemTypes.
     *
     * @param string $hookName
     * @param array $args of arguments passed
     */
    public function getMenuItemTypesCallback($hookName, $args)
    {
        $types = & $args[0];

        $ojsTypes = [
            self::NMI_TYPE_CURRENT => [
                'title' => __('editor.issues.currentIssue'),
                'description' => __('manager.navigationMenus.current.description'),
            ],
            self::NMI_TYPE_ARCHIVES => [
                'title' => __('navigation.archives'),
                'description' => __('manager.navigationMenus.archives.description'),
            ],
            self::NMI_TYPE_SUBSCRIPTIONS => [
                'title' => __('navigation.subscriptions'),
                'description' => __('manager.navigationMenus.subscriptions.description'),
                'conditionalWarning' => __('manager.navigationMenus.subscriptions.conditionalWarning'),
            ],
            self::NMI_TYPE_MY_SUBSCRIPTIONS => [
                'title' => __('user.subscriptions.mySubscriptions'),
                'description' => __('manager.navigationMenus.mySubscriptions.description'),
                'conditionalWarning' => __('manager.navigationMenus.mySubscriptions.conditionalWarning'),
            ],
        ];

        $types = array_merge($types, $ojsTypes);
    }

    /**
     * Callback for display menu item functionality
     *
     * @param string $hookName
     * @param array $args of arguments passed
     */
    public function getDisplayStatusCallback($hookName, $args)
    {
        $navigationMenuItem = & $args[0];

        $request = Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();
        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());

        $isUserLoggedIn = Validation::isLoggedIn();
        $isUserLoggedInAs = Validation::loggedInAs();
        $context = $request->getContext();

        $this->transformNavMenuItemTitle($templateMgr, $navigationMenuItem);

        $menuItemType = $navigationMenuItem->getType();

        // Conditionally hide some items
        switch ($menuItemType) {
            case self::NMI_TYPE_CURRENT:
            case self::NMI_TYPE_ARCHIVES:
                $navigationMenuItem->setIsDisplayed($context && $context->getData('publishingMode') != \APP\journal\Journal::PUBLISHING_MODE_NONE);
                break;
            case self::NMI_TYPE_SUBSCRIPTIONS:
                if ($context) {
                    $paymentManager = Application::getPaymentManager($context);
                    $navigationMenuItem->setIsDisplayed($context->getData('paymentsEnabled') && $paymentManager->isConfigured());
                }
                break;
            case self::NMI_TYPE_MY_SUBSCRIPTIONS:
                if ($context) {
                    $paymentManager = Application::getPaymentManager($context);
                    $navigationMenuItem->setIsDisplayed(Validation::isLoggedIn() && $context->getData('paymentsEnabled') && $paymentManager->isConfigured() && $context->getData('publishingMode') == \APP\journal\Journal::PUBLISHING_MODE_SUBSCRIPTION);
                }
                break;
        }

        if ($navigationMenuItem->getIsDisplayed()) {
            // Set the URL
            switch ($menuItemType) {
                case self::NMI_TYPE_CURRENT:
                    $navigationMenuItem->setUrl($dispatcher->url(
                        $request,
                        Application::ROUTE_PAGE,
                        null,
                        'issue',
                        'current',
                        null
                    ));
                    break;
                case self::NMI_TYPE_ARCHIVES:
                    $navigationMenuItem->setUrl($dispatcher->url(
                        $request,
                        Application::ROUTE_PAGE,
                        null,
                        'issue',
                        'archive',
                        null
                    ));
                    break;
                case self::NMI_TYPE_SUBSCRIPTIONS:
                    $navigationMenuItem->setUrl($dispatcher->url(
                        $request,
                        Application::ROUTE_PAGE,
                        null,
                        'about',
                        'subscriptions',
                        null
                    ));
                    break;
                case self::NMI_TYPE_MY_SUBSCRIPTIONS:
                    $navigationMenuItem->setUrl($dispatcher->url(
                        $request,
                        Application::ROUTE_PAGE,
                        null,
                        'user',
                        'subscriptions',
                        null
                    ));
                    break;
            }
        }
    }
}

if (!PKP_STRICT_MODE) {
    foreach ([
        'NMI_TYPE_SUBSCRIPTIONS',
        'NMI_TYPE_MY_SUBSCRIPTIONS',
        'NMI_TYPE_CURRENT',
        'NMI_TYPE_ARCHIVES',
    ] as $constantName) {
        define($constantName, constant('\APP\services\NavigationMenuService::' . $constantName));
    }
}
