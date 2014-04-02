<?php

/**
 * @file classes/journal/JournalSettingsDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSettingsDAO
 * @ingroup journal
 *
 * @brief Operations for retrieving and modifying journal settings.
 */

class JournalSettingsDAO extends DAO {
	function &_getCache($journalId) {
		static $settingCache;
		if (!isset($settingCache)) {
			$settingCache = array();
		}
		if (!isset($settingCache[$journalId])) {
			$cacheManager =& CacheManager::getManager();
			$settingCache[$journalId] =& $cacheManager->getFileCache(
				'journalSettings', $journalId,
				array($this, '_cacheMiss')
			);
		}
		return $settingCache[$journalId];
	}

	/**
	 * Retrieve a journal setting value.
	 * @param $journalId int
	 * @param $name string
	 * @param $locale string optional
	 * @return mixed
	 */
	function &getSetting($journalId, $name, $locale = null) {
		$cache =& $this->_getCache($journalId);
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
		$settings =& $this->getJournalSettings($cache->getCacheId());
		if (!isset($settings[$id])) {
			$cache->setCache($id, null);
			return null;
		}
		return $settings[$id];
	}

	/**
	 * Retrieve and cache all settings for a journal.
	 * @param $journalId int
	 * @return array
	 */
	function &getJournalSettings($journalId) {
		$journalSettings = array();

		$result =& $this->retrieve(
			'SELECT setting_name, setting_value, setting_type, locale FROM journal_settings WHERE journal_id = ?', $journalId
		);

		while (!$result->EOF) {
			$row =& $result->getRowAssoc(false);
			$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
			if ($row['locale'] == '') {
				$journalSettings[$row['setting_name']] = $value;
			} else {
				if (!isset($journalSettings[$row['setting_name']])) {
					$journalSettings[$row['setting_name']] = array();
				}
				$journalSettings[$row['setting_name']][$row['locale']] = $value;
			}
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		$cache =& $this->_getCache($journalId);
		$cache->setEntireCache($journalSettings);

		return $journalSettings;
	}

	/**
	 * Add/update a journal setting.
	 * @param $journalId int
	 * @param $name string
	 * @param $value mixed
	 * @param $type string data type of the setting. If omitted, type will be guessed
	 * @param $isLocalized boolean
	 */
	function updateSetting($journalId, $name, $value, $type = null, $isLocalized = false) {
		$cache =& $this->_getCache($journalId);
		$cache->setCache($name, $value);

		$keyFields = array('setting_name', 'locale', 'journal_id');

		if (!$isLocalized) {
			$value = $this->convertToDB($value, $type);
			$this->replace('journal_settings',
				array(
					'journal_id' => $journalId,
					'setting_name' => $name,
					'setting_value' => $value,
					'setting_type' => $type,
					'locale' => ''
				),
				$keyFields
			);
		} else {
			if (is_array($value)) foreach ($value as $locale => $localeValue) {
				$this->update('DELETE FROM journal_settings WHERE journal_id = ? AND setting_name = ? AND locale = ?', array($journalId, $name, $locale));
				if (empty($localeValue)) continue;
				$type = null;
				$this->update('INSERT INTO journal_settings
					(journal_id, setting_name, setting_value, setting_type, locale)
					VALUES (?, ?, ?, ?, ?)',
					array(
						$journalId, $name, $this->convertToDB($localeValue, $type), $type, $locale
					)
				);
			}
		}
	}

	/**
	 * Delete a journal setting.
	 * @param $journalId int
	 * @param $name string
	 */
	function deleteSetting($journalId, $name, $locale = null) {
		$cache =& $this->_getCache($journalId);
		$cache->setCache($name, null);

		$params = array($journalId, $name);
		$sql = 'DELETE FROM journal_settings WHERE journal_id = ? AND setting_name = ?';
		if ($locale !== null) {
			$params[] = $locale;
			$sql .= ' AND locale = ?';
		}

		return $this->update($sql, $params);
	}

	/**
	 * Delete all settings for a journal.
	 * @param $journalId int
	 */
	function deleteSettingsByJournal($journalId) {
		$cache =& $this->_getCache($journalId);
		$cache->flush();

		return $this->update(
				'DELETE FROM journal_settings WHERE journal_id = ?', $journalId
		);
	}

	/**
	 * Used internally by installSettings to perform variable and translation replacements.
	 * @param $rawInput string contains text including variable and/or translate replacements.
	 * @param $paramArray array contains variables for replacement
	 * @returns string
	 */
	function _performReplacement($rawInput, $paramArray = array()) {
		$value = preg_replace_callback('{{translate key="([^"]+)"}}', array(&$this, '_installer_regexp_callback'), $rawInput);
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
	 * Install journal settings from an XML file.
	 * @param $journalId int ID of journal for settings to apply to
	 * @param $filename string Name of XML file to parse and install
	 * @param $paramArray array Optional parameters for variable replacement in settings
	 */
	function installSettings($journalId, $filename, $paramArray = array()) {
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
					$journalId,
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
	 * Used internally by reloadLocalizedSettingDefaults to perform variable and translation replacements.
	 * @param $rawInput string contains text including variable and/or translate replacements.
	 * @param $paramArray array contains variables for replacement
	 * @param $locale string contains the name of the locale that should be used for the translation
	 * @returns string
	 */
	function _performLocalizedReplacement($rawInput, $paramArray = array(), $locale = null) {
		preg_match('{{translate key="([^"]+)"}}', $rawInput, $matches);
		if ( isset($matches[1]) ) {
			AppLocale::requireComponents(LOCALE_COMPONENT_OJS_DEFAULT, LOCALE_COMPONENT_OJS_MANAGER, $locale);
			return __($matches[1], $paramArray, $locale);
		}

		return $rawInput;
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
			$childArray =& $element->getChildByName('array');
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
	 * Install locale field Only journal settings from an XML file.
	 * @param $journalId int ID of journal for settings to apply to
	 * @param $filename string Name of XML file to parse and install
	 * @param $paramArray array Optional parameters for variable replacement in settings
	 * @param $locale string locale id for which settings will be loaded
	 */
	function reloadLocalizedDefaultSettings($journalId, $filename, $paramArray, $locale) {
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

				//skip all settings that are not locale fields
				if (!$isLocaleField) continue;

				if ($type == 'object') {
					$arrayNode =& $valueNode->getChildByName('array');
					$value = $this->_buildLocalizedObject($arrayNode, $paramArray, $locale);
				} else {
					$value = $this->_performLocalizedReplacement($valueNode->getValue(), $paramArray, $locale);
				}

				// Replace translate calls with translated content
				$this->updateSetting(
					$journalId,
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
	 * Used internally by journal setting installation code to perform translation function.
	 */
	function _installer_regexp_callback($matches) {
		return __($matches[1]);
	}
}

?>
