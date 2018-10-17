<?php

/**
 * @file classes/journal/JournalSettingsDAO.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSettingsDAO
 * @ingroup journal
 *
 * @brief Operations for retrieving and modifying journal settings.
 */

import('lib.pkp.classes.db.SettingsDAO');

class JournalSettingsDAO extends SettingsDAO {

	/**
	 * @see SettingsDAO::reloadLocalizedDefaultSettings()
	 *
	 * Install locale field only settings from an XML file.
	 * @param $request Request
	 * @param $locale string locale id for which settings will be loaded
	 */
	function reloadLocalizedDefaultContextSettings($request, $locale) {
		$context = $request->getContext();
		$filename = 'registry/journalSettings.xml';
		$paramArray = array(
			'indexUrl' => $request->getIndexUrl(),
			'journalPath' => $context->getData('path'),
			'primaryLocale' => $context->getPrimaryLocale(),
			'journalName' => $context->getName($context->getPrimaryLocale())
		);
		parent::reloadLocalizedDefaultSettings($context->getId(), $filename, $paramArray, $locale);
	}

	/**
	 * Get the settings table name.
	 * @return string
	 */
	protected function _getTableName() {
		return 'journal_settings';
	}

	/**
	 * Get the primary key column name.
	 */
	protected function _getPrimaryKeyColumn() {
		return 'journal_id';
	}

	/**
	 * Get the cache name.
	 */
	protected function _getCacheName() {
		return 'journalSettings';
	}
}


