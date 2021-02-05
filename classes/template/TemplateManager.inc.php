<?php

/**
 * @file classes/template/TemplateManager.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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

		// Pass app-specific details to template
		$this->assign([
			'brandImage' => 'templates/images/ojs_brand.png',
		]);

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
					$request->getBaseUrl() . '/' . $publicFileManager->getSiteFilesPath() . '/' . $site->getData('styleSheet')['uploadName'],
					['priority' => STYLE_SEQUENCE_LATE]
				);
			}
			if (isset($context)) {
				$this->assign([
					'currentJournal' => $context,
					'siteTitle' => $context->getLocalizedName(),
					'publicFilesDir' => $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($context->getId()),
					'primaryLocale' => $context->getPrimaryLocale(),
					'supportedLocales' => $context->getSupportedLocaleNames(),
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
				foreach($contexts as $context) {
					if (!$context->getData('disableUserReg')) {
						$contextsForRegistration[] = $context;
					}
				}

				$this->assign([
					'contexts' => $contextsForRegistration,
					'disableUserReg' => empty($contextsForRegistration),
					'siteTitle' => $site->getLocalizedTitle(),
					'primaryLocale' => $site->getPrimaryLocale(),
					'supportedLocales' => $site->getSupportedLocaleNames(),
					'pageFooter' => $site->getLocalizedData('pageFooter'),
				]);

			}
		}
	}

	/**
	 * @copydoc PKPTemplateManager::setupBackendPage()
	 */
	function setupBackendPage() {
		parent::setupBackendPage();

		$request = Application::get()->getRequest();
		if (defined('SESSION_DISABLE_INIT')
				|| !$request->getContext()
				|| !$request->getUser()) {
			return;
		}

		$router = $request->getRouter();
		$handler = $router->getHandler();
		$userRoles = (array) $handler->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);

		$menu = (array) $this->getState('menu');

		// Add issues after submissions items
		if (in_array(ROLE_ID_MANAGER, $userRoles)) {
			$issuesLink = [
				'name' => __('editor.navigation.issues'),
				'url' => $router->url($request, null, 'manageIssues'),
				'isCurrent' => $request->getRequestedPage() === 'manageIssues',
			];

			$index = array_search('submissions', array_keys($menu));
			if ($index === false || count($menu) <= ($index + 1)) {
				$menu['issues'] = $issuesLink;
			} else {
				$menu = array_slice($menu, 0, $index + 1, true) +
						['issues' => $issuesLink] +
						array_slice($menu, $index + 1, null, true);
			}
		}

		// Add payments link before settings
		if ($request->getContext()->getData('paymentsEnabled') && array_intersect([ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER, ROLE_ID_SUBSCRIPTION_MANAGER], $userRoles)) {
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
		}

		$this->setState(['menu' => $menu]);
	}
}


