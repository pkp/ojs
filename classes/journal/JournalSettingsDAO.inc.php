<?php

/**
 * @file classes/journal/JournalSettingsDAO.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
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
	 * Constructor
	 */
	function JournalSettingsDAO() {
		parent::SettingsDAO();
	}

	function &_getCache($journalId) {
		static $settingCache;
		if (!isset($settingCache)) {
			$settingCache = array();
		}
		if (!isset($settingCache[$journalId])) {
			$cacheManager = CacheManager::getManager();
			$settingCache[$journalId] = $cacheManager->getFileCache(
				'journalSettings', $journalId,
				array($this, '_cacheMiss')
			);
		}
		return $settingCache[$journalId];
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
}

?>
