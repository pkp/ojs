<?php

/**
 * @file controllers/grid/settings/languages/ManageLanguageGridHandler.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ManageLanguageGridHandler
 * @ingroup controllers_grid_settings_languages
 *
 * @brief Handle language management grid requests only.
 */

import('classes.controllers.grid.languages.LanguageGridHandler');

import('lib.pkp.controllers.grid.languages.LanguageGridRow');

class ManageLanguageGridHandler extends LanguageGridHandler {
	/**
	 * Constructor
	 */
	function ManageLanguageGridHandler() {
		parent::LanguageGridHandler();
		$this->addRoleAssignment(array(
			ROLE_ID_MANAGER),
			array('fetchGrid', 'fetchRow'));
	}


	//
	// Implement methods from GridHandler.
	//
	/**
	 * @see GridHandler::loadData()
	 */
	function loadData(&$request, $filter) {
		$site =& $request->getSite();
		$context =& $request->getContext();

		$allLocales = AppLocale::getAllLocales();
		$supportedLocales = $site->getSupportedLocales();
		$contextPrimaryLocale = $context->getPrimaryLocale();
		$data = array();

		foreach ($supportedLocales as $locale) {
			$data[$locale] = array();
			$data[$locale]['name'] = $allLocales[$locale];
			$data[$locale]['supported'] = true;
			$data[$locale]['primary'] = ($locale == $contextPrimaryLocale);
		}

		$data = $this->addManagementData($request, $data);
		return $data;
	}

	//
	// Extended methods from LanguageGridHandler.
	//
	/**
	 * @see LanguageGridHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);

		$this->setInstructions('manager.languages.languageInstructions');

		$this->addNameColumn();
		$this->addPrimaryColumn('contextPrimary');
		$this->addManagementColumns();
	}
}

?>
