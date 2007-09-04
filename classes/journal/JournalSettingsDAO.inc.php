<?php

/**
 * @file JournalSettingsDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package journal
 * @class JournalSettingsDAO
 *
 * Class for Journal Settings DAO.
 * Operations for retrieving and modifying journal settings.
 *
 * $Id$
 */

class JournalSettingsDAO extends DAO {
	function &_getCache($journalId) {
		static $settingCache;
		if (!isset($settingCache)) {
			$settingCache = array();
		}
		if (!isset($settingCache[$journalId])) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$settingCache[$journalId] =& $cacheManager->getCache(
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
			// Make sure that even null values are cached
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
		
		$result = &$this->retrieve(
			'SELECT setting_name, setting_value, setting_type, locale FROM journal_settings WHERE journal_id = ?', $journalId
		);
		
		if ($result->RecordCount() == 0) {
			$returner = null;
			$result->Close();
			return $returner;
			
		} else {
			while (!$result->EOF) {
				$row = &$result->getRowAssoc(false);
				$value = $this->convertFromDB($row['setting_value'], $row['setting_type']);
				if ($row['locale'] == '') $journalSettings[$row['setting_name']] = $value;
				else $journalSettings[$row['setting_name']][$row['locale']] = $value;
				$result->MoveNext();
			}
			$result->close();
			unset($result);

			$cache =& $this->_getCache($journalId);
			$cache->setEntireCache($journalSettings);

			return $journalSettings;
		}
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
			$this->update('DELETE FROM journal_settings WHERE journal_id = ? AND setting_name = ?', array($journalId, $name));
			if (is_array($value)) foreach ($value as $locale => $localeValue) {
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
		$value = preg_replace_callback('{{translate key="([^"]+)"}}', '_installer_regexp_callback', $rawInput);
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
			$childArray = &$element->getChildByName('array');
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
		$xmlParser = &new XMLParser();
		$tree = $xmlParser->parse($filename);

		if (!$tree) {
			$xmlParser->destroy();
			return false;
		}

		foreach ($tree->getChildren() as $setting) {
			$nameNode = &$setting->getChildByName('name');
			$valueNode = &$setting->getChildByName('value');

			if (isset($nameNode) && isset($valueNode)) {
				$type = $setting->getAttribute('type');
				$isLocaleField = $setting->getAttribute('locale');
				$name = &$nameNode->getValue();

				if ($type == 'object') {
					$arrayNode = &$valueNode->getChildByName('array');
					$value = $this->_buildObject($arrayNode, $paramArray);
				} else {
					$value = $this->_performReplacement($valueNode->getValue(), $paramArray);
				}

				// Replace translate calls with translated content
				$this->updateSetting(
					$journalId,
					$name,
					$isLocaleField?array(Locale::getLocale() => $value):$value,
					$type,
					$isLocaleField
				);
			}
		}

		$xmlParser->destroy();

	}
}

/**
 * Used internally by journal setting installation code to perform translation function.
 */
function _installer_regexp_callback($matches) {
	return Locale::translate($matches[1]);
}

?>
