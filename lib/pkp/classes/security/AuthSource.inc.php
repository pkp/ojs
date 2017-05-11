<?php

/**
 * @file classes/security/AuthSource.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthSource
 * @ingroup security
 * @see AuthSourceDAO
 *
 * @brief Describes an authentication source.
 */


import('classes.plugins.AuthPlugin');

class AuthSource extends DataObject {
	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Get/set methods
	//

	/**
	 * Get ID of this source.
	 * @return int
	 */
	function getAuthId() {
		return $this->getData('authId');
	}

	/**
	 * Set ID of this source.
	 * @param $authId int
	 */
	function setAuthId($authId) {
		$this->setData('authId', $authId);
	}

	/**
	 * Get user-specified title of this source.
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}

	/**
	 * Set user-specified title of this source.
	 * @param $title string
	 */
	function setTitle($title) {
		$this->setData('title', $title);
	}

	/**
	 * Get the authentication plugin associated with this source.
	 * @return string
	 */
	function getPlugin() {
		return $this->getData('plugin');
	}

	/**
	 * Set the authentication plugin associated with this source.
	 * @param $plugin string
	 */
	function setPlugin($plugin) {
		$this->setData('plugin', $plugin);
	}

	/**
	 * Get flag indicating this is the default authentication source.
	 * @return boolean
	 */
	function getDefault() {
		return $this->getData('authDefault');
	}

	/**
	 * Set flag indicating this is the default authentication source.
	 * @param $authDefault boolean
	 */
	function setDefault($authDefault) {
		$this->setData('authDefault', $authDefault);
	}

	/**
	 * Get array of plugin-specific settings for this source.
	 * @return array
	 */
	function getSettings() {
		return $this->getData('settings');
	}

	/**
	 * Set array of plugin-specific settings for this source.
	 * @param $settings array
	 */
	function setSettings($settings) {
		$this->setData('settings', $settings);
	}

	/**
	 * Get the authentication plugin object associated with this source.
	 * @return AuthPlugin
	 */
	function &getPluginClass() {
		$returner =& $this->getData('authPlugin');
		return $returner;
	}

	/**
	 * Set authentication plugin object associated with this source.
	 * @param $authPlugin AuthPlugin
	 */
	function setPluginClass($authPlugin) {
		$this->setData('authPlugin', $authPlugin);
	}
}

?>
