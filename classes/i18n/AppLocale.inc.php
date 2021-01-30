<?php

/**
 * @file classes/i18n/AppLocale.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AppLocale
 * @ingroup i18n
 *
 * @brief Provides methods for loading locale data and translating strings identified by unique keys
 *
 */

import('lib.pkp.classes.i18n.PKPLocale');

class AppLocale extends PKPLocale {
	/**
	 * Make a map of components to their respective files.
	 * @param $locale string
	 * @return array
	 */
	static function makeComponentMap($locale) {
		$baseDir = "locale/$locale/";
		return parent::makeComponentMap($locale) + array(
			LOCALE_COMPONENT_APP_COMMON => $baseDir . 'locale.po',
			LOCALE_COMPONENT_APP_AUTHOR => $baseDir . 'author.po',
			LOCALE_COMPONENT_APP_SUBMISSION => $baseDir . 'submission.po',
			LOCALE_COMPONENT_APP_EDITOR => $baseDir . 'editor.po',
			LOCALE_COMPONENT_APP_MANAGER => $baseDir . 'manager.po',
			LOCALE_COMPONENT_APP_ADMIN => $baseDir . 'admin.po',
			LOCALE_COMPONENT_APP_DEFAULT => $baseDir . 'default.po',
			LOCALE_COMPONENT_APP_API => $baseDir . 'api.po',
			LOCALE_COMPONENT_APP_EMAIL => $baseDir . 'emails.po',
		);
	}
}

