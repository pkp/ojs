<?php

/**
 * @file classes/template/TemplateManager.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class TemplateManager
 *
 * @ingroup template
 *
 * @brief Class for accessing the underlying template engine.
 * Currently integrated with Smarty (from http://smarty.php.net/).
 *
 */

namespace APP\template;

use APP\core\Application;
use APP\core\PageRouter;
use APP\file\PublicFileManager;
use PKP\facades\Locale;
use PKP\i18n\LocaleMetadata;
use PKP\security\Role;
use PKP\session\SessionManager;
use PKP\site\Site;
use PKP\template\PKPTemplateManager;

class TemplateManager extends PKPTemplateManager
{
    /**
     * Initialize template engine and assign basic template variables.
     *
     * @param \APP\core\Request $request
     */
    public function initialize($request)
    {
        parent::initialize($request);

        // Pass app-specific details to template
        $this->assign([
            'brandImage' => 'templates/images/ojs_brand.png',
        ]);

        if (!SessionManager::isDisabled()) {
            /**
             * Kludge to make sure no code that tries to connect to
             * the database is executed (e.g., when loading
             * installer pages).
             */

            $context = $request->getContext();
            $site = $request->getSite(); /** @var Site $site */

            $publicFileManager = new PublicFileManager();
            $siteFilesDir = $request->getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath();
            $this->assign('sitePublicFilesDir', $siteFilesDir);
            $this->assign('publicFilesDir', $siteFilesDir); // May be overridden by journal

            if ($site->getData('styleSheet')) {
                $this->addStyleSheet(
                    'siteStylesheet',
                    $request->getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath() . '/' . $site->getData('styleSheet')['uploadName'],
                    ['priority' => self::STYLE_SEQUENCE_LATE]
                );
            }
            if (isset($context)) {
                $this->assign([
                    'currentJournal' => $context,
                    'siteTitle' => $context->getLocalizedName(),
                    'publicFilesDir' => $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($context->getId()),
                    'primaryLocale' => $context->getPrimaryLocale(),
                    'supportedLocales' => $context->getSupportedLocaleNames(LocaleMetadata::LANGUAGE_LOCALE_ONLY),
                    'numPageLinks' => $context->getData('numPageLinks'),
                    'itemsPerPage' => $context->getData('itemsPerPage'),
                    'enableAnnouncements' => $context->getData('enableAnnouncements'),
                    'disableUserReg' => $context->getData('disableUserReg'),
                    'pageFooter' => $context->getLocalizedData('pageFooter'),
                ]);
            } else {
                // Check if registration is open for any contexts
                $contextDao = Application::getContextDAO();
                $contexts = $contextDao->getAll(true)->toArray();
                $contextsForRegistration = [];
                foreach ($contexts as $context) {
                    if (!$context->getData('disableUserReg')) {
                        $contextsForRegistration[] = $context;
                    }
                }

                $this->assign([
                    'contexts' => $contextsForRegistration,
                    'disableUserReg' => empty($contextsForRegistration),
                    'siteTitle' => $site->getLocalizedTitle(),
                    'primaryLocale' => $site->getPrimaryLocale(),
                    'supportedLocales' => Locale::getFormattedDisplayNames(
                        $site->getSupportedLocales(),
                        Locale::getLocales(),
                        LocaleMetadata::LANGUAGE_LOCALE_ONLY
                    ),
                    'pageFooter' => $site->getLocalizedData('pageFooter'),
                ]);
            }
        }
    }

    /**
     * @copydoc PKPTemplateManager::setupBackendPage()
     */
    public function setupBackendPage()
    {
        parent::setupBackendPage();

        $request = Application::get()->getRequest();
        if (SessionManager::isDisabled()
                || !$request->getContext()
                || !$request->getUser()) {
            return;
        }

        /** @var PageRouter */
        $router = $request->getRouter();
        $handler = $router->getHandler();
        $userRoles = (array) $handler->getAuthorizedContextObject(Application::ASSOC_TYPE_USER_ROLES);

        $menu = (array) $this->getState('menu');

        // Add issues after submissions items
        if (count(array_intersect([Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN], $userRoles))) {
            $issuesLink = [
                'name' => __('editor.navigation.issues'),
                'url' => $router->url($request, null, 'manageIssues'),
                'isCurrent' => $request->getRequestedPage() === 'manageIssues',
            ];
            $index = array_search('submissions', array_keys($menu));
            if ($index === false || count($menu) <= $index + 1) {
                $menu['issues'] = $issuesLink;
            } else {
                $menu = array_slice($menu, 0, $index + 1, true)
                    + ['issues' => $issuesLink]
                    + array_slice($menu, $index + 1, null, true);
            }
        }

        if (count(array_intersect([Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_SUB_EDITOR], $userRoles))) {
            $statsIssuesLink = [
                'name' => __('editor.navigation.issues'),
                'url' => $router->url($request, null, 'stats', 'issues', 'issues'),
                'isCurrent' => $router->getRequestedPage($request) === 'stats' && $router->getRequestedOp($request) === 'issues',
            ];
            $statsPublicationsIndex = array_search('publications', array_keys($menu['statistics']));
            $menu['statistics']['submenu'] = array_slice($menu['statistics']['submenu'], 0, $statsPublicationsIndex + 1, true) +
                ['issues' => $statsIssuesLink] +
                array_slice($menu['statistics']['submenu'], $statsPublicationsIndex + 1, null, true);
        }

        // Add payments link before settings
        if ($request->getContext()->getData('paymentsEnabled') && array_intersect([Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUBSCRIPTION_MANAGER], $userRoles)) {
            $paymentsLink = [
                'name' => __('common.payments'),
                'url' => $router->url($request, null, 'payments'),
                'isCurrent' => $request->getRequestedPage() === 'payments',
            ];

            $index = array_search('settings', array_keys($menu));
            if ($index === false || count($menu) === $index) {
                $menu['payments'] = $paymentsLink;
            } else {
                $menu = array_slice($menu, 0, $index, true) +
                    ['payments' => $paymentsLink] +
                    array_slice($menu, $index, null, true);
            }

            // add institutions menu if needed
            $institutionsLink = [
                'name' => __('institution.institutions'),
                'url' => $router->url($request, null, 'management', 'settings', 'institutions'),
                'isCurrent' => $request->getRequestedPage() === 'management' && in_array('institutions', (array) $request->getRequestedArgs()),
            ];
            $paymentsIndex = array_search('payments', array_keys($menu));
            $menu = array_slice($menu, 0, $paymentsIndex, true) +
                ['institutions' => $institutionsLink] +
                array_slice($menu, $paymentsIndex, null, true);
        }

        $this->setState(['menu' => $menu]);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\template\TemplateManager', '\TemplateManager');
}
