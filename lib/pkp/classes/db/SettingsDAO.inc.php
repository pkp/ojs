<?php

/**
 * @file classes/db/SettingsDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SettingsDAO
 * @ingroup db
 *
 * @brief Operations for retrieving and modifying settings.
 */

class SettingsDAO extends DAO {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Retrieve (and newly cache) all settings.
	 * @param $id int
	 * @return array Associative array of settings.
	 */
	function loadSettings($id) {
		$settings = array();

		$result = $this->retrieve(
			'SELECT setting_name, setting_value, setting_type, locale FROM ' . $this->_getTableName() . ' WHERE ' . $this->_getPrimaryKeyColumn() . ' = ?',
			(int) $id
		);

		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
			if ($row['locale'] == '') $settings[$row['setting_name']] = $value;
			else $settings[$row['setting_name']][$row['locale']] = $value;
			$result->MoveNext();
		}
		$result->Close();

		$cache = $this->_getCache($id);
		if ($cache) $cache->setEntireCache($settings);

		return $settings;
	}

	/**
	 * Retrieve cached settings, using the cache if available.
	 * @param $id int
	 * @return array Associative array of settings.
	 */
	function &getSettings($id) {
		$cache = $this->_getCache($id);
		if ($cache) return $cache->getContents();
		return $this->loadSettings($id);
	}

	/**
	 * Retrieve a setting value.
	 * @param $id int
	 * @param $name string
	 * @param $locale string optional
	 * @return mixed
	 */
	function &getSetting($id, $name, $locale = null) {
		if ($cache = $this->_getCache($id)) {
			$returner = $cache->get($name);
		} else {
			$settings = $this->loadSettings($id);
			if (isset($settings[$name])) $returner = $settings[$name];
			else $returner = null;
		}
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

	/**
	 * Callback for a cache miss.
	 * @param $cache Cache
	 * @param $id string
	 * @return mixed
	 */
	function _cacheMiss($cache, $id) {
		$settings = $this->loadSettings($cache->getCacheId());
		if (!isset($settings[$id])) {
			$cache->setCache($id, null);
			return null;
		}
		return $settings[$id];
	}

	/**
	 * Used internally by installSettings to perform variable and translation replacements.
	 * @param $rawInput string contains text including variable and/or translate replacements.
	 * @param $paramArray array contains variables for replacement
	 * @returns string
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
	function _buildObject (&$node, $paramArray = array()) {
		$value = array();
		foreach ($node->getChildren() as $element) {
			$key = $element->getAttribute('key');
			$childArray = $element->getChildByName('array');
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
	 * Install conference settings from an XML file.
	 * @param $id int ID of scheduled conference/conference for settings to apply to
	 * @param $filename string Name of XML file to parse and install
	 * @param $paramArray array Optional parameters for variable replacement in settings
	 */
	function installSettings($id, $filename, $paramArray = array()) {
		$xmlParser = new XMLParser();
		$tree = $xmlParser->parse($filename);

		if (!$tree) {
			$xmlParser->destroy();
			return false;
		}

		foreach ($tree->getChildren() as $setting) {
			$nameNode = $setting->getChildByName('name');
			$valueNode = $setting->getChildByName('value');

			if (isset($nameNode) && isset($valueNode)) {
				$type = $setting->getAttribute('type');
				$isLocaleField = $setting->getAttribute('locale');
				$name = $nameNode->getValue();

				if ($type == 'object') {
					$arrayNode = $valueNode->getChildByName('array');
					$value = $this->_buildObject($arrayNode, $paramArray);
				} else {
					$value = $this->_performReplacement($valueNode->getValue(), $paramArray);
				}

				// Replace translate calls with translated content
				$this->updateSetting(
					$id,
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
	 * Used internally by conference setting installation code to perform translation function.
	 */
	function _installer_regexp_callback($matches) {
		return __($matches[1]);
	}

	/**
	 * Add/update a setting.
	 * @param $id int
	 * @param $name string
	 * @param $value mixed
	 * @param $type string data type of the setting. If omitted, type will be guessed
	 * @param $isLocalized boolean
	 */
	function updateSetting($id, $name, $value, $type = null, $isLocalized = false) {
		$cache = $this->_getCache($id);
		if ($cache) $cache->setCache($name, $value);

		$keyFields = array('setting_name', 'locale', $this->_getPrimaryKeyColumn());

		if (!$isLocalized) {
			$value = $this->convertToDB($value, $type);
			$this->replace($this->_getTableName(),
				array(
					$this->_getPrimaryKeyColumn() => $id,
					'setting_name' => $name,
					'setting_value' => $value,
					'setting_type' => $type,
					'locale' => ''
				),
				$keyFields
			);
		} else {
			if (is_array($value)) foreach ($value as $locale => $localeValue) {
				$this->update('DELETE FROM ' . $this->_getTableName() . ' WHERE ' . $this->_getPrimaryKeyColumn() . ' = ? AND setting_name = ? AND locale = ?', array($id, $name, $locale));
				if (empty($localeValue)) continue;
				$type = null;
				$this->update('INSERT INTO ' . $this->_getTableName() . '
					(' . $this->_getPrimaryKeyColumn() . ', setting_name, setting_value, setting_type, locale)
					VALUES (?, ?, ?, ?, ?)',
					array(
						$id, $name, $this->convertToDB($localeValue, $type), $type, $locale
					)
				);
			}
		}
	}

	/**
	 * Delete a setting.
	 * @param $id int
	 * @param $name string
	 */
	function deleteSetting($id, $name, $locale = null) {
		$cache = $this->_getCache($id);
		if ($cache) $cache->setCache($name, null);

		$params = array($id, $name);
		$sql = 'DELETE FROM ' . $this->_getTableName() . ' WHERE ' . $this->_getPrimaryKeyColumn() . ' = ? AND setting_name = ?';
		if ($locale !== null) {
			$params[] = $locale;
			$sql .= ' AND locale = ?';
		}

		return $this->update($sql, $params);
	}

	/**
	 * Delete all settings for an ID.
	 * @param $id int
	 */
	function deleteById($id) {
		$cache = $this->_getCache($id);
		if ($cache) $cache->flush();

		return $this->update(
			'DELETE FROM ' . $this->_getTableName() . ' WHERE ' . $this->_getPrimaryKeyColumn() . ' = ?',
			(int) $id
		);
	}

	/**
	 * Used internally by reloadLocalizedSettingDefaults to perform variable and translation replacements.
	 * @param $rawInput string contains text including variable and/or translate replacements.
	 * @param $paramArray array contains variables for replacement
	 * @param $locale string contains the name of the locale that should be used for the translation
	 * @return string
	 */
	function _performLocalizedReplacement($rawInput, $paramArray = array(), $locale = null) {
		$value = preg_replace_callback(
			'{{translate key="([^"]+)"}}',
			// this only translates from mail locale file
			create_function(
				'$matches',
				'$locale = "' . $locale . '";'.'$localeFileName = AppLocale::getMainLocaleFilename($locale);' .
				'$localeFile = new LocaleFile($locale, $localeFileName);'.'return $localeFile->translate($matches[1]);'
			),
			$rawInput
		);

		foreach ($paramArray as $pKey => $pValue) {
			$value = str_replace('{$' . $pKey . '}', $pValue, $value);
		}
		return $value;
	}

	/**
	 * Used internally by reloadLocalizedSettingDefaults to recursively build nested arrays.
	 * Deals with translation and variable replacement calls.
	 * @param $node object XMLNode <array> tag
	 * @param $paramArray array Parameters to be replaced in key/value contents
	 * @param $locale string contains the name of the locale that should be used for the translation
	 */
	function &_buildLocalizedObject (&$node, $paramArray = array(), $locale = null) {
		$value = array();
		foreach ($node->getChildren() as $element) {
			$key = $element->getAttribute('key');
			$childArray = $element->getChildByName('array');
			if (isset($childArray)) {
				$content = $this->_buildLocalizedObject($childArray, $paramArray, $locale);
			} else {
				$content = $this->_performLocalizedReplacement($element->getValue(), $paramArray, $locale);
			}
			if (!empty($key)) {
				$key = $this->_performLocalizedReplacement($key, $paramArray, $locale);
				$value[$key] = $content;
			} else $value[] = $content;
		}
		return $value;
	}

	/**
	 * Reload a default setting from an XML file.
	 * @param $id int ID for settings to apply to
	 * @param $filename string Name of XML file to parse and install
	 * @param $settingName string Name of the setting that is to be reloaded
	 * @param $paramArray array Optional parameters for variable replacement in settings
	 */
	function reloadDefaultSetting($id, $filename, $settingName, $paramArray) {
		$xmlParser = new XMLParser();
		$tree = $xmlParser->parse($filename);

		if (!$tree) {
			$xmlParser->destroy();
			return false;
		}

		foreach ($tree->getChildren() as $setting) {
			$nameNode = $setting->getChildByName('name');
			$valueNode = $setting->getChildByName('value');

			if (isset($nameNode) && isset($valueNode)) {

				if ($nameNode->getValue() == $settingName) {
					$type = $setting->getAttribute('type');
					$isLocaleField = $setting->getAttribute('locale');
					$name = $nameNode->getValue();

					if ($type == 'object') {
						$arrayNode = $valueNode->getChildByName('array');
						$value = $this->_buildObject($arrayNode, $paramArray);
					} else {
						$value = $this->_performReplacement($valueNode->getValue(), $paramArray);
					}

					$this->updateSetting(
						$id,
						$name,
						$isLocaleField?array(AppLocale::getLocale() => $value):$value,
						$type,
						$isLocaleField
					);

					$xmlParser->destroy();
					return true;
				}
			}
		}

		$xmlParser->destroy();

	}

	/**
	 * Install locale field only settings from an XML file.
	 * @param $id int ID for settings to apply to
	 * @param $filename string Name of XML file to parse and install
	 * @param $paramArray array Optional parameters for variable replacement in settings
	 * @param $locale string locale id for which settings will be loaded
	 */
	function reloadLocalizedDefaultSettings($id, $filename, $paramArray, $locale) {
		$xmlParser = new XMLParser();
		$tree = $xmlParser->parse($filename);

		if (!$tree) {
			$xmlParser->destroy();
			return false;
		}

		foreach ($tree->getChildren() as $setting) {
			$nameNode = $setting->getChildByName('name');
			$valueNode = $setting->getChildByName('value');

			if (isset($nameNode) && isset($valueNode)) {
				$type = $setting->getAttribute('type');
				$isLocaleField = $setting->getAttribute('locale');
				$name = $nameNode->getValue();

				//skip all settings that are not locale fields
				if (!$isLocaleField) continue;

				if ($type == 'object') {
					$arrayNode = $valueNode->getChildByName('array');
					$value = $this->_buildLocalizedObject($arrayNode, $paramArray, $locale);
				} else {
					$value = $this->_performLocalizedReplacement($valueNode->getValue(), $paramArray, $locale);
				}

				// Replace translate calls with translated content
				$this->updateSetting(
					$id,
					$name,
					array($locale => $value),
					$type,
					true
				);
			}
		}

		$xmlParser->destroy();

	}

	/**
	 * Get the settings cache for a given ID
	 * @param $id
	 * @return array|null (Null indicates caching disabled)
	 */
	function _getCache($id) {
		$cacheName = $this->_getCacheName();
		if ($cacheName === null) return null;

		static $settingCache;
		if (!isset($settingCache)) {
			$settingCache = array();
		}
		if (!isset($settingCache[$id])) {
			$cacheManager = CacheManager::getManager();
			$settingCache[$id] = $cacheManager->getCache(
				$cacheName, $id,
				array($this, '_cacheMiss')
			);
		}
		return $settingCache[$id];
	}

	/**
	 * Get the settings table name.
	 * @return string
	 */
	protected function _getTableName() {
		assert(false); // Must be implemented by subclasses
	}

	/**
	 * Get the primary key column name.
	 */
	protected function _getPrimaryKeyColumn() {
		assert(false); // Must be implemented by subclasses
	}

	/**
	 * Get the cache name.
	 * @return string|null Null disables caching.
	 */
	protected function _getCacheName() {
		return null;
	}
}

?>
