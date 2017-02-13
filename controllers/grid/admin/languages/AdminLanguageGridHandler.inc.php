<?php

/**
 * @file controllers/grid/admin/languages/AdminLanguageGridHandler.inc.php
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

import('lib.pkp.controllers.grid.admin.languages.PKPAdminLanguageGridHandler');

class AdminLanguageGridHandler extends PKPAdminLanguageGridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * @see PKPHandler::initialize()
	 * @param $request PKPRequest
	 */
	function initialize($request) {
		parent::initialize($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_ADMIN);
	}

	/**
	 * Helper function to update locale settings in all
	 * installed journals, based on site locale settings.
	 * @param $request object
	 */
	function _updateContextLocaleSettings($request) {
		$site = $request->getSite();
		$siteSupportedLocales = $site->getSupportedLocales();

		$journalDao = DAORegistry::getDAO('JournalDAO');
		$contexts = $journalDao->getAll()->toArray();
		foreach ($contexts as $context) {
			$primaryLocale = $context->getPrimaryLocale();
			$supportedLocales = $context->getSetting('supportedLocales');

			if (isset($primaryLocale) && !in_array($primaryLocale, $siteSupportedLocales)) {
				$context->setPrimaryLocale($site->getPrimaryLocale());
				$this->updateContext($context);
			}

			if (is_array($supportedLocales)) {
				$supportedLocales = array_intersect($supportedLocales, $siteSupportedLocales);
				$context->updateSetting('supportedLocales', $supportedLocales, 'object');
			}
		}
	}

	/**
	 * This grid can also present management functions
	 * if the conditions above are true.
	 * @param $request Request
	 * @return boolean
	 */
	function _canManage($request) {
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getAll();
		$userRoles = $this->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES);
		$journal = $request->getJournal();
		return ($journals->getCount() == 1 && $journal && in_array(ROLE_ID_MANAGER, $userRoles));
	}
}

?>
