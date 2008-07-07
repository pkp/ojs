<?php

/**
 * @file SiteSettingsDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package site 
 * @class SiteSettingsDAO
 *
 * Class for Site Settings DAO.
 * Operations for retrieving and modifying site settings.
 *
 * $Id$
 */

class SiteSettingsDAO extends DAO {
	function &_getCache() {
		static $settingCache;
		if (!isset($settingCache)) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$settingCache = $cacheManager->getCache(
				'siteSettings', 'site',
				array($this, '_cacheMiss')
			);
		}
		return $settingCache;
	}

	/**
	 * Retrieve a site setting value.
	 * @param $name string
	 * @param $locale string optional
	 * @return mixed
	 */
	function &getSetting($name, $locale = null) {
		$cache =& $this->_getCache();
		$returner = $cache->get($name);
		if ($locale !== null) {
			if (!isset($returner[$locale]) || !is_array($returner)) {
				unset($returner);
				$returner = null;
				return $returner;
			}
			return $returner[$locale];
		}
		return $returner;
	}

	function _cacheMiss(&$cache, $id) {
		$settings =& $this->getSiteSettings();
		if (!isset($settings)) {
			// Make sure that even null values are cached
			$cache->setCache($id, null);
			return null;
		}
		return $settings;
	}

	/**
	 * Retrieve and cache all settings for a site.
	 * @return array
	 */
	function &getSiteSettings() {
		$siteSettings = array();

		$result = &$this->retrieve(
			'SELECT setting_name, setting_value, setting_type, locale FROM site_settings'
		);

		if ($result->RecordCount() == 0) {
			$returner = null;
			$result->Close();
			return $returner;

		} else {
			while (!$result->EOF) {
				$row = &$result->getRowAssoc(false);
				$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
				if ($row['locale'] == '') $siteSettings[$row['setting_name']] = $value;
				else $siteSettings[$row['setting_name']][$row['locale']] = $value;
				$result->MoveNext();
			}
			$result->close();
			unset($result);

			$cache =& $this->_getCache();
			$cache->setEntireCache($siteSettings);

			return $siteSettings;
		}
	}

	/**
	 * Add/update a site setting.
	 * @param $name string
	 * @param $value mixed
	 * @param $type string data type of the setting. If omitted, type will be guessed
	 * @param $isLocalized boolean
	 */
	function updateSetting($name, $value, $type = null, $isLocalized = false) {
		$cache =& $this->_getCache();
		$cache->setCache($name, $value);

		$keyFields = array('setting_name', 'locale');
		
		if (!$isLocalized) {
			$value = $this->convertToDB($value, $type);
			$this->replace('site_settings',
				array(
					'setting_name' => $name,
					'setting_value' => $value,
					'setting_type' => $type,
					'locale' => ''
				),
				$keyFields
			);
		} else {
			if (is_array($value)) foreach ($value as $locale => $localeValue) {
				$this->update('DELETE FROM site_settings WHERE setting_name = ? AND locale = ?', array($name, $locale));
				if (empty($localeValue)) continue;
				$type = null;
				$this->update('INSERT INTO site_settings
					(setting_name, setting_value, setting_type, locale)
					VALUES (?, ?, ?, ?)',
					array(
						$name, $this->convertToDB($localeValue, $type), $type, $locale
					)
				);
			}
		}
	}

	/**
	 * Delete a site setting.
	 * @param $name string
	 */
	function deleteSetting($name, $locale = null) {
		$cache =& $this->_getCache();
		$cache->setCache($name, null);

		$params = array($name);
		$sql = 'DELETE FROM site_settings WHERE setting_name = ?';
		if ($locale !== null) {
			$params[] = $locale;
			$sql .= ' AND locale = ?';
		}

		return $this->update($sql, $params);
	}
}

?>
