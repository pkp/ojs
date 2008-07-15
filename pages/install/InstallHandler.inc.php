<?php

/**
 * @file InstallHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InstallHandler
 * @ingroup pages_install
 *
 * @brief Handle installation requests. 
 */

// $Id$


/* FIXME Prevent classes from trying to initialize the session manager (and thus the database connection) */
define('SESSION_DISABLE_INIT', 1);

import('install.form.InstallForm');
import('install.form.UpgradeForm');
import('core.Handler');

class InstallHandler extends Handler {

	/**
	 * If no journal is selected, display list of journals.
	 * Otherwise, display the index page for the selected journal.
	 */
	function index() {
		// Make sure errors are displayed to the browser during install.
		@ini_set('display_errors', E_ALL);

		InstallHandler::validate();

		if (($setLocale = Request::getUserVar('setLocale')) != null && Locale::isLocaleValid($setLocale)) {
			Request::setCookieVar('currentLocale', $setLocale);
		}

		$installForm = &new InstallForm();
		$installForm->initData();
		$installForm->display();
	}

	/**
	 * Redirect to index if system has already been installed.
	 */
	function validate() {
		if (Config::getVar('general', 'installed')) {
			Request::redirect(null, 'index');	
		}
	}

	/**
	 * Execute installer.
	 */
	function install() {
		InstallHandler::validate();

		$installForm = &new InstallForm();
		$installForm->readInputData();

		if ($installForm->validate()) {
			$installForm->execute();

		} else {
			$installForm->display();
		}
	}

	/**
	 * Display upgrade form.
	 */
	function upgrade() {
		InstallHandler::validate();

		if (($setLocale = Request::getUserVar('setLocale')) != null && Locale::isLocaleValid($setLocale)) {
			Request::setCookieVar('currentLocale', $setLocale);
		}

		$installForm = &new UpgradeForm();
		$installForm->initData();
		$installForm->display();
	}

	/**
	 * Execute upgrade.
	 */
	function installUpgrade() {
		InstallHandler::validate();

		$installForm = &new UpgradeForm();
		$installForm->readInputData();

		if ($installForm->validate()) {
			$installForm->execute();

		} else {
			$installForm->display();
		}
	}

}

?>
