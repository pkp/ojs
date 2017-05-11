<?php

/**
 * @file classes/plugins/PluginSettingsDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PluginSettingsDAO
 * @ingroup plugins
 * @see Plugin
 *
 * @brief Operations for retrieving and modifying plugin settings.
 */

class PluginSettingsDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Get the cache for plugin settings.
	 * @param $contextId int Context ID
	 * @param $pluginName string Plugin symbolic name
	 * @return Cache
	 */
	function _getCache($contextId, $pluginName) {
		static $settingCache;

		if (!isset($settingCache)) {
			$settingCache = array();
		}
		if (!isset($settingCache[$contextId])) {
			$settingCache[$contextId] = array();
		}
		if (!isset($settingCache[$contextId][$pluginName])) {
			$cacheManager = CacheManager::getManager();
			$settingCache[$contextId][$pluginName] = $cacheManager->getCache(
				'pluginSettings-' . $contextId, $pluginName,
				array($this, '_cacheMiss')
			);
		}
		return $settingCache[$contextId][$pluginName];
	}

	/**
	 * Retrieve a plugin setting value.
	 * @param $contextId int Context ID
	 * @param $pluginName string Plugin symbolic name
	 * @param $name Setting name
	 * @return mixed
	 */
	function getSetting($contextId, $pluginName, $name) {
		// Normalize the plug-in name to lower case.
		$pluginName = strtolower_codesafe($pluginName);

		// Retrieve the setting.
		$cache = $this->_getCache($contextId, $pluginName);
		return $cache->get($name);
	}

	/**
	 * Callback for a cache miss.
	 * @param $cache Cache object
	 * @param $id string Identifier to look up in cache
	 * @return mixed
	 */
	function _cacheMiss($cache, $id) {
		$contextParts = explode('-', $cache->getContext());
		$contextId = array_pop($contextParts);
		$settings = $this->getPluginSettings($contextId, $cache->getCacheId());
		if (!isset($settings[$id])) {
			// Make sure that even null values are cached
			$cache->setCache($id, null);
			return null;
		}
		return $settings[$id];
	}

	/**
	 * Retrieve and cache all settings for a plugin.
	 * @param $contextId int Context ID
	 * @param $pluginName string Plugin symbolic name
	 * @return array
	 */
	function getPluginSettings($contextId, $pluginName) {
		// Normalize plug-in name to lower case.
		$pluginName = strtolower_codesafe($pluginName);

		$contextColumn = Application::getPluginSettingsContextColumnName();
		$result = $this->retrieve(
			'SELECT setting_name, setting_value, setting_type FROM plugin_settings WHERE plugin_name = ? AND ' . $contextColumn . ' = ?', array($pluginName, (int) $contextId)
		);

		$pluginSettings = array();
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$pluginSettings[$row['setting_name']] = $this->convertFromDB($row['setting_value'], $row['setting_type']);
			$result->MoveNext();
		}
		$result->Close();

		$cache = $this->_getCache($contextId, $pluginName);
		$cache->setEntireCache($pluginSettings);

		return $pluginSettings;
	}

	/**
	 * Add/update a plugin setting.
	 * @param $contextId int Context ID
	 * @param $pluginName string Symbolic plugin name
	 * @param $name string Setting name
	 * @param $value mixed Setting value
	 * @param $type string data type of the setting. If omitted, type will be guessed
	 * @return int Return value from ADODB's replace() function.
	 */
	function updateSetting($contextId, $pluginName, $name, $value, $type = null) {
		// Normalize the plug-in name to lower case.
		$pluginName = strtolower_codesafe($pluginName);

		$cache = $this->_getCache($contextId, $pluginName);
		$cache->setCache($name, $value);

		$value = $this->convertToDB($value, $type);

		return $this->replace(
			'plugin_settings',
			array(
				'context_id' => (int) $contextId,
				'plugin_name' => $pluginName,
				'setting_name' => $name,
				'setting_value' => $value,
				'setting_type' => $type,
			),
			array('context_id', 'plugin_name', 'setting_name')
		);
	}

	/**
	 * Delete a plugin setting.
	 * @param $contextId int
	 * @param $pluginName int
	 * @param $name string
	 */
	function deleteSetting($contextId, $pluginName, $name) {
		// Normalize the plug-in name to lower case.
		$pluginName = strtolower_codesafe($pluginName);

		$cache = $this->_getCache($contextId, $pluginName);
		$cache->setCache($name, null);

		return $this->update(
			'DELETE FROM plugin_settings WHERE plugin_name = ? AND setting_name = ? AND context_id = ?',
			array($pluginName, $name, (int) $contextId)
		);
	}

	/**
	 * Delete all settings for a plugin.
	 * @param $contextId int
	 * @param $pluginName string
	 */
	function deleteSettingsByPlugin($contextId, $pluginName) {
		// Normalize the plug-in name to lower case.
		$pluginName = strtolower_codesafe($pluginName);

		$cache = $this->_getCache($contextId, $pluginName);
		$cache->flush();

		return $this->update(
			'DELETE FROM plugin_settings WHERE context_id = ? AND plugin_name = ?',
			array((int) $contextId, $pluginName)
		);
	}

	/**
	 * Delete all settings for a context.
	 * @param $contextId int
	 */
	function deleteByContextId($contextId) {
		return $this->update(
			'DELETE FROM plugin_settings WHERE context_id = ?', (int) $contextId
		);
	}

	/**
	 * Used internally by installSettings to perform variable and translation replacements.
	 * @param $rawInput string contains text including variable and/or translate replacements.
	 * @param $paramArray array contains variables for replacement
	 * @return string
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
	function _buildObject ($node, $paramArray = array()) {
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
	 * Install plugin settings from an XML file.
	 * @param $pluginName name of plugin for settings to apply to
	 * @param $filename string Name of XML file to parse and install
	 * @param $paramArray array Optional parameters for variable replacement in settings
	 */
	function installSettings($contextId, $pluginName, $filename, $paramArray = array()) {
		$xmlParser = new XMLParser();
		$tree = $xmlParser->parse($filename);

		if (!$tree) {
			$xmlParser->destroy();
			return false;
		}

		// Check for existing settings and leave them if they are already in place.
		$currentSettings = $this->getPluginSettings($contextId, $pluginName);

		foreach ($tree->getChildren() as $setting) {
			$nameNode = $setting->getChildByName('name');
			$valueNode = $setting->getChildByName('value');

			if (isset($nameNode) && isset($valueNode)) {
				$type = $setting->getAttribute('type');
				$name = $nameNode->getValue();

				// If the setting already exists, respect it.
				if (isset($currentSettings[$name])) continue;

				if ($type == 'object') {
					$arrayNode = $valueNode->getChildByName('array');
					$value = $this->_buildObject($arrayNode, $paramArray);
				} else {
					$value = $this->_performReplacement($valueNode->getValue(), $paramArray);
				}

				// Replace translate calls with translated content
				$this->updateSetting($contextId, $pluginName, $name, $value, $type);
			}
		}

		$xmlParser->destroy();
	}
}

/**
 * Used internally by plugin setting installation code to perform translation
 * function.
 * @param $matches array
 * @return string
 */
function _installer_plugin_regexp_callback($matches) {
	return __($matches[1]);
}

?>
