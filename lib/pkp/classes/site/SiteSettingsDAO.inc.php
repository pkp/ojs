<?php

/**
 * @file classes/site/SiteSettingsDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package site
 * @class SiteSettingsDAO
 *
 * Class for Site Settings DAO.
 * Operations for retrieving and modifying site settings.
 */

class SiteSettingsDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	function _getCache() {
		$settingCache =& Registry::get('siteSettingCache', true, null);
		if ($settingCache === null) {
			$cacheManager = CacheManager::getManager();
			$settingCache = $cacheManager->getFileCache(
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
		$cache = $this->_getCache();
		$returner = $cache->get($name);
		if ($locale !== null) {
			if (!isset($returner[$locale]) || !is_array($returner)) {
				$returner = null;
				return $returner;
			}
			return $returner[$locale];
		}
		return $returner;
	}

	function _cacheMiss($cache, $id) {
		$settings = $this->getSiteSettings();
		if (!isset($settings[$id])) {
			$cache->setCache($id, null);
			return null;
		}
		return $settings[$id];
	}

	/**
	 * Retrieve and cache all settings for a site.
	 * @return array
	 */
	function &getSiteSettings() {
		$siteSettings = array();

		$result = $this->retrieve(
			'SELECT setting_name, setting_value, setting_type, locale FROM site_settings'
		);

		if ($result->RecordCount() == 0) {
			$returner = null;
			$result->Close();
			return $returner;

		} else {
			while (!$result->EOF) {
				$row = $result->getRowAssoc(false);
				$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
				if ($row['locale'] == '') $siteSettings[$row['setting_name']] = $value;
				else $siteSettings[$row['setting_name']][$row['locale']] = $value;
				$result->MoveNext();
			}
			$result->Close();

			$cache = $this->_getCache();
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
	 * @return boolean
	 */
	function updateSetting($name, $value, $type = null, $isLocalized = false) {
		$returner = null;
		$cache = $this->_getCache();
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
			$returner = true;
		} else {
			if (is_array($value)) foreach ($value as $locale => $localeValue) {
				$this->update('DELETE FROM site_settings WHERE setting_name = ? AND locale = ?', array($name, $locale));
				if (empty($localeValue)) continue;
				$type = null;
				$returner = $this->update('INSERT INTO site_settings
					(setting_name, setting_value, setting_type, locale)
					VALUES (?, ?, ?, ?)',
					array(
						$name, $this->convertToDB($localeValue, $type), $type, $locale
					)
				);
			}
		}
		return $returner;
	}

	/**
	 * Delete a site setting.
	 * @param $name string
	 */
	function deleteSetting($name, $locale = null) {
		$cache = $this->_getCache();
		$cache->setCache($name, null);

		$params = array($name);
		$sql = 'DELETE FROM site_settings WHERE setting_name = ?';
		if ($locale !== null) {
			$params[] = $locale;
			$sql .= ' AND locale = ?';
		}

		return $this->update($sql, $params);
	}

	/**
	 * Used internally by installSettings to perform variable and translation replacements.
	 * @param $rawInput string contains text including variable and/or translate replacements.
	 * @param $paramArray array contains variables for replacement
	 * @return string
	 */
	function _performReplacement($rawInput, $paramArray = array()) {
		$value = preg_replace_callback('{{translate key="([^"]+)"}}', array($this, '_installer_regexp_callback'), $rawInput);
		foreach ($paramArray as $pKey => $pValue) {
			$value = str_replace('{$' . $pKey . '}', $pValue, $value);
		}
		return $value;
	}

	/**
	 * Used internally by installSettings to recursively build nested arrays.
	 * Deals with translation and variable replacement calls.
	 * @param $node object XMLNode <array> tag
	 * @param $paramArray array Parameters to be replaced in key/value contents
	 */
	function &_buildObject (&$node, $paramArray = array()) {
		$value = array();
		foreach ($node->getChildren() as $element) {
			$key = $element->getAttribute('key');
			$childArray =& $element->getChildByName('array');
			if (isset($childArray)) {
				$content = $this->_buildObject($childArray, $paramArray);
			} else {
				$content = $this->_performReplacement($element->getValue(), $paramArray);
			}
			if (!empty($key)) {
				$key = $this->_performReplacement($key, $paramArray);
				$value[$key] = $content;
			} else $value[] = $content;
		}
		return $value;
	}

	/**
	 * Install site settings from an XML file.
	 * @param $filename string Name of XML file to parse and install
	 * @param $paramArray array Optional parameters for variable replacement in settings
	 */
	function installSettings($filename, $paramArray = array()) {
		$xmlParser = new XMLParser();
		$tree = $xmlParser->parse($filename);

		if (!$tree) {
			$xmlParser->destroy();
			return false;
		}

		foreach ($tree->getChildren() as $setting) {
			$nameNode =& $setting->getChildByName('name');
			$valueNode =& $setting->getChildByName('value');

			if (isset($nameNode) && isset($valueNode)) {
				$type = $setting->getAttribute('type');
				$isLocaleField = $setting->getAttribute('locale');
				$name =& $nameNode->getValue();

				if ($type == 'object') {
					$arrayNode =& $valueNode->getChildByName('array');
					$value = $this->_buildObject($arrayNode, $paramArray);
				} else {
					$value = $this->_performReplacement($valueNode->getValue(), $paramArray);
				}

				// Replace translate calls with translated content
				$this->updateSetting(
					$name,
					$isLocaleField?array(AppLocale::getLocale() => $value):$value,
					$type,
					$isLocaleField
				);
			}
		}

		$xmlParser->destroy();

	}

	/**
	 * Used internally by site setting installation code to perform translation function.
	 */
	function _installer_regexp_callback($matches) {
		return __($matches[1]);
	}
}

?>
