<?php

/**
 * @file classes/template/TemplateManager.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TemplateManager
 * @ingroup template
 *
 * @brief Class for accessing the underlying template engine.
 * Currently integrated with Smarty (from http://smarty.php.net/).
 *
 */

import('classes.search.ArticleSearch');
import('classes.file.PublicFileManager');
import('lib.pkp.classes.template.PKPTemplateManager');

class TemplateManager extends PKPTemplateManager {
	/**
	 * Initialize template engine and assign basic template variables.
	 * @param $request PKPRequest
	 */
	function initialize($request) {
		parent::initialize($request);

		if (!defined('SESSION_DISABLE_INIT')) {
			/**
			 * Kludge to make sure no code that tries to connect to
			 * the database is executed (e.g., when loading
			 * installer pages).
			 */

			$context = $request->getContext();
			$site = $request->getSite();

			$publicFileManager = new PublicFileManager();
			$siteFilesDir = $request->getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath();
			$this->assign('sitePublicFilesDir', $siteFilesDir);
			$this->assign('publicFilesDir', $siteFilesDir); // May be overridden by journal

			if ($site->getData('styleSheet')) {
				$this->addStyleSheet(
					'siteStylesheet',
					$request->getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath() . '/' . $site->getData('styleSheet'),
					array(
						'priority' => STYLE_SEQUENCE_LATE
					)
				);
			}

			// Pass app-specific details to template
			$this->assign(array(
				'brandImage' => 'templates/images/ojs_brand.png',
				'packageKey' => 'common.software',
			));

			// Get a count of unread tasks.
			if ($user = $request->getUser()) {
				$notificationDao = DAORegistry::getDAO('NotificationDAO');
				// Exclude certain tasks, defined in the notifications grid handler
				import('lib.pkp.controllers.grid.notifications.TaskNotificationsGridHandler');
				$this->assign('unreadNotificationCount', $notificationDao->getNotificationCount(false, $user->getId(), null, NOTIFICATION_LEVEL_TASK));
			}
			if (isset($context)) {

				$this->assign(array(
					'currentJournal' => $context,
					'siteTitle' => $context->getLocalizedName(),
					'publicFilesDir' => $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($context->getId()),
					'primaryLocale' => $context->getPrimaryLocale(),
					'supportedLocales' => $context->getSupportedLocaleNames(),
					'displayPageHeaderTitle' => $context->getLocalizedPageHeaderTitle(),
					'displayPageHeaderLogo' => $context->getLocalizedPageHeaderLogo(),
					'displayPageHeaderLogoAltText' => $context->getLocalizedData('pageHeaderLogoImageAltText'),
					'numPageLinks' => $context->getData('numPageLinks'),
					'itemsPerPage' => $context->getData('itemsPerPage'),
					'enableAnnouncements' => $context->getData('enableAnnouncements'),
					'disableUserReg' => $context->getData('disableUserReg'),
				));

				// Assign meta tags
				$favicon = $context->getLocalizedFavicon();
				if (!empty($favicon)) {
					$faviconDir = $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($context->getId());
					$this->addHeader('favicon', '<link rel="icon" href="' . $faviconDir . '/' . $favicon['uploadName'] . '">');
				}

				// Get a link to the settings page for the current context.
				// This allows us to reduce template duplication by using this
				// variable in templates/common/header.tpl, instead of
				// reproducing a lot of OMP/OJS-specific logic there.
				$dispatcher = $request->getDispatcher();
				$this->assign( 'contextSettingsUrl', $dispatcher->url($request, ROUTE_PAGE, null, 'management', 'settings', 'context') );

				$this->assign('pageFooter', $context->getLocalizedData('pageFooter'));
			} else {
				// Check if registration is open for any contexts
				$contextDao = Application::getContextDAO();
				$contexts = $contextDao->getAll(true)->toArray();
				$contextsForRegistration = array();
				foreach($contexts as $context) {
					if (!$context->getData('disableUserReg')) {
						$contextsForRegistration[] = $context;
					}
				}

				$this->assign(array(
					'contexts' => $contextsForRegistration,
					'disableUserReg' => empty($contextsForRegistration),
					'displayPageHeaderTitle' => $site->getLocalizedPageHeaderTitle(),
					'displayPageHeaderLogo' => $site->getLocalizedData('pageHeaderTitleImage'),
					'siteTitle' => $site->getLocalizedTitle(),
					'primaryLocale' => $site->getPrimaryLocale(),
					'supportedLocales' => $site->getSupportedLocaleNames(),
					'pageFooter' => $site->getLocalizedData('pageFooter'),
				));

			}
		}
	}
}


