<?php

/**
 * @file classes/plugins/PluginSettingsDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PluginSettingsDAO
 * @ingroup plugins
 * @see Plugin
 *
 * @brief Operations for retrieving and modifying plugin settings.
 */

// $Id$


class PluginSettingsDAO extends DAO {
	function &_getCache($journalId, $pluginName) {
		static $settingCache;
		if (!isset($settingCache)) {
			$settingCache = array();
		}
		if (!isset($this->settingCache[$journalId])) {
			$this->settingCache[$journalId] = array();
		}
		if (!isset($this->settingCache[$journalId][$pluginName])) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$this->settingCache[$journalId][$pluginName] = $cacheManager->getCache(
				'pluginSettings-' . $journalId, $pluginName,
				array($this, '_cacheMiss')
			);
		}
		return $this->settingCache[$journalId][$pluginName];
	}

	/**
	 * Retrieve a plugin setting value.
	 * @param $pluginName string
	 * @param $name
	 * @return mixed
	 */
	function getSetting($journalId, $pluginName, $name) {
		$cache =& $this->_getCache($journalId, $pluginName);
		return $cache->get($name);
	}

	function _cacheMiss(&$cache, $id) {
		$contextParts = explode('-', $cache->getContext());
		$journalId = array_pop($contextParts);
		$settings =& $this->getPluginSettings($journalId, $cache->getCacheId());
		if (!isset($settings[$id])) {
			return null;
		}
		return $settings[$id];
	}

	/**
	 * Retrieve and cache all settings for a plugin.
	 * @param $journalId int
	 * @param $pluginName string
	 * @return array
	 */
	function &getPluginSettings($journalId, $pluginName) {
		$pluginSettings = array();

		$result = &$this->retrieve(
			'SELECT setting_name, setting_value, setting_type FROM plugin_settings WHERE plugin_name = ? AND journal_id = ?', array($pluginName, $journalId)
		);

		while (!$result->EOF) {
			$row = &$result->getRowAssoc(false);
			$pluginSettings[$row['setting_name']] = $this->convertFromDB($row['setting_value'], $row['setting_type']);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		$cache =& $this->_getCache($journalId, $pluginName);
		$cache->setEntireCache($pluginSettings);

		return $pluginSettings;
	}

	/**
	 * Add/update a plugin setting.
	 * @param $journalId int
	 * @param $pluginName string
	 * @param $name string
	 * @param $value mixed
	 * @param $type string data type of the setting. If omitted, type will be guessed
	 */
	function updateSetting($journalId, $pluginName, $name, $value, $type = null) {
		$cache =& $this->_getCache($journalId, $pluginName);
		$cache->setCache($name, $value);

		$result = $this->retrieve(
			'SELECT COUNT(*) FROM plugin_settings WHERE plugin_name = ? AND setting_name = ? AND journal_id = ?',
			array($pluginName, $name, $journalId)
		);

		$value = $this->convertToDB($value, $type);
		if ($result->fields[0] == 0) {
			$returner = $this->update(
				'INSERT INTO plugin_settings
					(plugin_name, journal_id, setting_name, setting_value, setting_type)
					VALUES
					(?, ?, ?, ?, ?)',
				array($pluginName, $journalId, $name, $value, $type)
			);
		} else {
			$returner = $this->update(
				'UPDATE plugin_settings SET
					setting_value = ?,
					setting_type = ?
					WHERE plugin_name = ? AND setting_name = ? AND journal_id = ?',
				array($value, $type, $pluginName, $name, $journalId)
			);
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Delete a plugin setting.
	 * @param $journalId int
	 * @param $pluginName int
	 * @param $name string
	 */
	function deleteSetting($journalId, $pluginName, $name) {
		$cache =& $this->_getCache($journalId, $pluginName);
		$cache->setCache($name, null);

		return $this->update(
			'DELETE FROM plugin_settings WHERE plugin_name = ? AND setting_name = ? AND journal_id = ?',
			array($pluginName, $name, $journalId)
		);
	}

	/**
	 * Delete all settings for a plugin.
	 * @param $journalId int
	 * @param $pluginName string
	 */
	function deleteSettingsByPlugin($journalId, $pluginName) {
		$cache =& $this->_getCache($journalId, $pluginName);
		$cache->flush();

		return $this->update(
			'DELETE FROM plugin_settings WHERE journal_id = ? AND plugin_name = ?', 
			array($journalId, $pluginName)
		);
	}

	/**
	 * Delete all settings for a journal.
	 * @param $journalId int
	 */
	function deleteSettingsByJournalId($journalId) {
		return $this->update(
			'DELETE FROM plugin_settings WHERE journal_id = ?', $journalId
		);
	}

	/**
	 * Used internally by installSettings to perform variable and translation replacements.
	 * @param $rawInput string contains text including variable and/or translate replacements.
	 * @param $paramArray array contains variables for replacement
	 * @returns string
	 */
	function _performReplacement($rawInput, $paramArray = array()) {
		$value = preg_replace_callback('{{translate key="([^"]+)"}}', '_installer_plugin_regexp_callback', $rawInput);
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
	 * Install plugin settings from an XML file.
	 * @param $pluginName name of plugin for settings to apply to
	 * @param $filename string Name of XML file to parse and install
	 * @param $paramArray array Optional parameters for variable replacement in settings
	 */
	function installSettings($journalId, $pluginName, $filename, $paramArray = array()) {
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
				$name = &$nameNode->getValue();

				if ($type == 'object') {
					$arrayNode = &$valueNode->getChildByName('array');
					$value = $this->_buildObject($arrayNode, $paramArray);
				} else {
					$value = $this->_performReplacement($valueNode->getValue(), $paramArray);
				}

				// Replace translate calls with translated content
				$this->updateSetting($journalId, $pluginName, $name, $value, $type);
			}
		}

		$xmlParser->destroy();

	}
}

/**
 * Used internally by plugin setting installation code to perform translation function.
 */
function _installer_plugin_regexp_callback($matches) {
	return Locale::translate($matches[1]);
}

?>
