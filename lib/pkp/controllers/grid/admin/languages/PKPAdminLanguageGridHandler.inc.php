<?php

/**
 * @file controllers/grid/admin/languages/PKPAdminLanguageGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminLanguageGridHandler
 * @ingroup controllers_grid_admin_languages
 *
 * @brief Handle administrative language grid requests. If in single context (e.g.
 * press) installation, this grid can also handle language management requests.
 * See _canManage().
 */

import('lib.pkp.controllers.grid.languages.LanguageGridHandler');
import('lib.pkp.controllers.grid.languages.LanguageGridRow');
import('lib.pkp.controllers.grid.languages.form.InstallLanguageForm');

class PKPAdminLanguageGridHandler extends LanguageGridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			array(ROLE_ID_SITE_ADMIN),
			array(
				'fetchGrid', 'fetchRow',
				'installLocale', 'saveInstallLocale', 'uninstallLocale',
				'downloadLocale', 'disableLocale', 'enableLocale',
				'reloadLocale', 'setPrimaryLocale'
			)
		);
	}


	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @copydoc GridHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PolicySet');
		$rolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);

		import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
		foreach($roleAssignments as $role => $operations) {
			$rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
		}
		$this->addPolicy($rolePolicy);

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc PKPHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);

		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_ADMIN,
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_APP_MANAGER
		);

		// Grid actions.
		$router = $request->getRouter();

		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'installLocale',
				new AjaxModal(
					$router->url($request, null, null, 'installLocale', null, null),
					__('admin.languages.installLocale'),
					null,
					true
					),
				__('admin.languages.installLocale'),
				'add')
		);

		$cellProvider = $this->getCellProvider();

		// Columns.
		// Enable locale.
		$this->addColumn(
			new GridColumn(
				'enable',
				'common.enable',
				null,
				'controllers/grid/common/cell/selectStatusCell.tpl',
				$cellProvider,
				array('width' => 10)
			)
		);

		// Locale name.
		$this->addNameColumn();

		// Primary locale.
		if ($this->_canManage($request)) {
			$primaryId = 'contextPrimary';
		} else {
			$primaryId = 'sitePrimary';
		}
		$this->addPrimaryColumn($primaryId);

		if ($this->_canManage($request)) {
			$this->addManagementColumns();
		}

		$this->setFootNote('admin.locale.maybeIncomplete');
	}


	//
	// Implement methods from GridHandler.
	//
	/**
	 * @copydoc GridHandler::getRowInstance()
	 */
	protected function getRowInstance() {
		return new LanguageGridRow();
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		$site = $request->getSite();
		$data = array();

		$allLocales = AppLocale::getAllLocales();
		$installedLocales = $site->getInstalledLocales();
		$supportedLocales = $site->getSupportedLocales();
		$primaryLocale = $site->getPrimaryLocale();

		foreach($installedLocales as $localeKey) {
			$data[$localeKey] = array();
			$data[$localeKey]['name'] = $allLocales[$localeKey];
			$data[$localeKey]['incomplete'] = !AppLocale::isLocaleComplete($localeKey);
			if (in_array($localeKey, $supportedLocales)) {
				$supported = true;
			} else {
				$supported = false;
			}
			$data[$localeKey]['supported'] = $supported;

			if ($this->_canManage($request)) {
				$context = $request->getContext();
				$primaryLocale = $context->getPrimaryLocale();
			}

			if ($localeKey == $primaryLocale) {
				$primary = true;
			} else {
				$primary = false;
			}
			$data[$localeKey]['primary'] = $primary;
		}

		if ($this->_canManage($request)) {
			$data = $this->addManagementData($request, $data);
		}

		return $data;
	}


	//
	// Public grid actions.
	//
	/**
	 * Open a form to select locales for installation.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function installLocale($args, $request) {
		// Form handling.
		$installLanguageForm = new InstallLanguageForm();
		$installLanguageForm->initData($request);
		return new JSONMessage(true, $installLanguageForm->fetch($request));

	}

	/**
	 * Save the install language form.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function saveInstallLocale($args, $request) {
		$installLanguageForm = new InstallLanguageForm();
		$installLanguageForm->readInputData($request);

		if ($installLanguageForm->validate($request)) {
			$installLanguageForm->execute($request);
			$this->_updateContextLocaleSettings($request);

			$notificationManager = new NotificationManager();
			$user = $request->getUser();
			$notificationManager->createTrivialNotification(
				$user->getId(), NOTIFICATION_TYPE_SUCCESS,
				array('contents' => __('notification.localeInstalled'))
			);
		}
		return DAO::getDataChangedEvent();
	}

	/**
	 * Download a locale from the PKP web site.
	 * @param $args array
	 * @param $request object
	 * @return JSONMessage JSON object
	 */
	function downloadLocale($args, $request) {
		$this->setupTemplate($request, true);
		$locale = $request->getUserVar('locale');

		import('classes.i18n.LanguageAction');
		$languageAction = new LanguageAction();

		if (!$languageAction->isDownloadAvailable() || !preg_match('/^[a-z]{2}_[A-Z]{2}$/', $locale)) {
			$request->redirect(null, 'admin', 'settings');
		}

		$notificationManager = new NotificationManager();
		$user = $request->getUser();
		$json = new JSONMessage(true);

		$errors = array();
		if (!$languageAction->downloadLocale($locale, $errors)) {
			$notificationManager->createTrivialNotification(
				$user->getId(),
				NOTIFICATION_TYPE_ERROR,
				array('contents' => $errors));
			$json->setEvent('refreshForm', $this->_fetchReviewerForm($args, $request));
		} else {
			$notificationManager->createTrivialNotification(
				$user->getId(),
				NOTIFICATION_TYPE_SUCCESS,
				array('contentLocaleKey' => __('admin.languages.localeInstalled'),
					 'params' => array('locale' => $locale)));
		}

		// Refresh form.
		$installLanguageForm = new InstallLanguageForm();
		$installLanguageForm->initData($request);
		$json->setEvent('refreshForm', $installLanguageForm->fetch($request));
		return $json;
	}

	/**
	 * Uninstall a locale.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function uninstallLocale($args, $request) {
		$site = $request->getSite();
		$locale = $request->getUserVar('rowId');
		$gridData = $this->getGridDataElements($request);

		if ($request->checkCSRF() && array_key_exists($locale, $gridData)) {
			$localeData = $gridData[$locale];
			if ($localeData['primary']) return new JSONMessage(false);

			$installedLocales = $site->getInstalledLocales();
			if (in_array($locale, $installedLocales)) {
				$installedLocales = array_diff($installedLocales, array($locale));
				$site->setInstalledLocales($installedLocales);
				$supportedLocales = $site->getSupportedLocales();
				$supportedLocales = array_diff($supportedLocales, array($locale));
				$site->setSupportedLocales($supportedLocales);
				$siteDao = DAORegistry::getDAO('SiteDAO');
				$siteDao->updateObject($site);

				$this->_updateContextLocaleSettings($request);
				AppLocale::uninstallLocale($locale);

				$notificationManager = new NotificationManager();
				$user = $request->getUser();
				$notificationManager->createTrivialNotification(
					$user->getId(), NOTIFICATION_TYPE_SUCCESS,
					array('contents' => __('notification.localeUninstalled', array('locale' => $localeData['name'])))
				);
			}
			return DAO::getDataChangedEvent($locale);
		}

		return new JSONMessage(false);
	}

	/**
	 * Enable an existing locale.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function enableLocale($args, $request) {
		$rowId = $request->getUserVar('rowId');
		$gridData = $this->getGridDataElements($request);

		if (array_key_exists($rowId, $gridData)) {
			$this->_updateLocaleSupportState($request, $rowId, true);

			$notificationManager = new NotificationManager();
			$user = $request->getUser();
			$notificationManager->createTrivialNotification(
				$user->getId(), NOTIFICATION_TYPE_SUCCESS,
				array('contents' => __('notification.localeEnabled'))
			);
		}

		return DAO::getDataChangedEvent($rowId);
	}

	/**
	 * Disable an existing locale.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function disableLocale($args, $request) {
		$locale = $request->getUserVar('rowId');
		$gridData = $this->getGridDataElements($request);
		$notificationManager = new NotificationManager();
		$user = $request->getUser();

		if ($request->checkCSRF() && array_key_exists($locale, $gridData)) {
			// Don't disable primary locales.
			if ($gridData[$locale]['primary']) {
				$notificationManager->createTrivialNotification(
					$user->getId(), NOTIFICATION_TYPE_ERROR,
					array('contents' => __('admin.languages.cantDisable'))
				);
			} else {
				$this->_updateLocaleSupportState($request, $locale, false);
				$notificationManager->createTrivialNotification(
					$user->getId(), NOTIFICATION_TYPE_SUCCESS,
					array('contents' => __('notification.localeDisabled'))
				);
			}
			return DAO::getDataChangedEvent($locale);
		}

		return new JSONMessage(false);
	}

	/**
	 * Reload locale.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function reloadLocale($args, $request) {
		$site = $request->getSite();
		$locale = $request->getUserVar('rowId');

		$gridData = $this->getGridDataElements($request);
		if ($request->checkCSRF() && array_key_exists($locale, $gridData)) {
			AppLocale::reloadLocale($locale);
			$notificationManager = new NotificationManager();
			$user = $request->getUser();
			$notificationManager->createTrivialNotification(
				$user->getId(), NOTIFICATION_TYPE_SUCCESS,
				array('contents' => __('notification.localeReloaded', array('locale' => $gridData[$locale]['name'])))
			);
			return DAO::getDataChangedEvent($locale);
		}

		return new JSONMessage(false);
	}


	/**
	 * Set primary locale.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function setPrimaryLocale($args, $request) {
		$rowId = $request->getUserVar('rowId');
		$gridData = $this->getGridDataElements($request);
		$localeData = $gridData[$rowId];
		$notificationManager = new NotificationManager();
		$user = $request->getUser();
		$site = $request->getSite();

		if (array_key_exists($rowId, $gridData)) {
			if (AppLocale::isLocaleValid($rowId)) {
				$site->setPrimaryLocale($rowId);
				$siteDao = DAORegistry::getDAO('SiteDAO');
				$siteDao->updateObject($site);

				$notificationManager->createTrivialNotification(
					$user->getId(), NOTIFICATION_TYPE_SUCCESS,
					array('contents' => __('notification.primaryLocaleDefined', array('locale' => $localeData['name'])))
				);
			}
		}

		// Need to refresh whole grid to remove the check in others
		// primary locale radio buttons.
		return DAO::getDataChangedEvent();
	}


	//
	// Helper methods.
	//
	/**
	 * Update the locale support state (enabled or disabled).
	 * @param $request Request
	 * @param $rowId string The locale row id.
	 * @param $enable boolean Enable locale flag.
	 */
	function _updateLocaleSupportState($request, $rowId, $enable) {
		$newSupportedLocales = array();
		$gridData = $this->getGridDataElements($request);

		foreach ($gridData as $locale => $data) {
			if ($data['supported']) {
				array_push($newSupportedLocales, $locale);
			}
		}

		if (AppLocale::isLocaleValid($rowId)) {
			if ($enable) {
				array_push($newSupportedLocales, $rowId);
			} else {
				$key = array_search($rowId, $newSupportedLocales);
				if ($key !== false) unset($newSupportedLocales[$key]);
			}
		}

		$site = $request->getSite();
		$site->setSupportedLocales($newSupportedLocales);

		$siteDao = DAORegistry::getDAO('SiteDAO');
		$siteDao->updateObject($site);

		$this->_updateContextLocaleSettings($request);
	}

	/**
	 * Helper function to update locale settings in all
	 * installed contexts, based on site locale settings.
	 * @param $request object
	 */
	function _updateContextLocaleSettings($request) {
		assert(false); // Must be implemented by subclasses
	}

	/**
	 * This grid can also present management functions
	 * if the conditions above are true.
	 * @param $request Request
	 * @return boolean
	 */
	function _canManage($request) {
		assert(false); // Must be implemented by subclasses
	}
}

?>
