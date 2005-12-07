<?php

/**
 * AuthSourceDAO.inc.php
 *
 * Copyright (c) 2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package security
 *
 * Class for AuthSource DAO.
 * Operations for retrieving and modifying AuthSource objects.
 *
 * $Id$
 */

import('security.AuthSource');

class AuthSourceDAO extends DAO {

	var $plugins;

	/**
	 * Constructor.
	 */
	function AuthSourceDAO() {
		parent::DAO();
		$this->plugins = &PluginRegistry::loadCategory(AUTH_PLUGIN_CATEGORY);
	}

	/**
	 * Get plugin instance corresponding to the ID.
	 * @param $authId int
	 * @return AuthPlugin
	 */
	function &getPlugin($authId) {
		$plugin = null;
		$auth = &$this->getSource($authId);
		if ($auth != null) {
			$plugin =& $auth->getPluginClass();
			if ($plugin != null) {
				$plugin = &$plugin->getInstance($auth->getSettings(), $auth->getAuthId());
			}
		}
		return $plugin;
	}

	/**
	 * Get plugin instance for the default authentication source.
	 * @return AuthPlugin
	 */
	function &getDefaultPlugin() {
		$plugin = null;
		$auth = &$this->getDefaultSource();
		if ($auth != null) {
			$plugin =& $auth->getPluginClass();
			if ($plugin != null) {
				$plugin = &$plugin->getInstance($auth->getSettings(), $auth->getAuthId());
			}
		}
		return $plugin;
	}
	
	/**
	 * Retrieve a source.
	 * @param $authId int
	 * @return AuthSource
	 */
	function &getSource($authId) {
		$result = &$this->retrieve(
			'SELECT * FROM auth_sources WHERE auth_id = ?', $authId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnAuthSourceFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Retrieve the default authentication source.
	 * @return AuthSource
	 */
	function &getDefaultSource() {
		$result = &$this->retrieve(
			'SELECT * FROM auth_sources WHERE auth_default = 1'
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnAuthSourceFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Internal function to return an AuthSource object from a row.
	 * @param $row array
	 * @return AuthSource
	 */
	function &_returnAuthSourceFromRow(&$row) {
		$auth = &new AuthSource();
		$auth->setAuthId($row['auth_id']);
		$auth->setTitle($row['title']);
		$auth->setPlugin($row['plugin']);
		$auth->setPluginClass(@$this->plugins[$row['plugin']]);
		$auth->setDefault($row['auth_default']);
		$auth->setSettings(unserialize($row['settings']));
		return $auth;
	}
	
	/**
	 * Insert a new source.
	 * @param $auth AuthSource
	 */
	function insertSource(&$auth) {
		if (!isset($this->plugins[$auth->getPlugin()])) return false;
		if (!$auth->getTitle()) $auth->setTitle($this->plugins[$auth->getPlugin()]->getDisplayName());
		$this->update(
			'INSERT INTO auth_sources
				(title, plugin, settings)
				VALUES
				(?, ?, ?)',
			array(
				$auth->getTitle(),
				$auth->getPlugin(),
				serialize($auth->getSettings() ? $auth->getSettings() : array())
			)
		);
		
		$auth->setAuthId($this->getInsertId('auth_sources', 'auth_id'));
		return $auth->getAuthId();
	}
	
	/**
	 * Update a source.
	 * @param $auth AuthSource
	 */
	function updateSource(&$auth) {
		return $this->update(
			'UPDATE auth_sources SET
				title = ?,
				settings = ?
			WHERE auth_id = ?',
			array(
				$auth->getTitle(),
				serialize($auth->getSettings() ? $auth->getSettings() : array()),
				$auth->getAuthId()
			)
		);
	}
	
	/**
	 * Delete a source.
	 * @param $authId int
	 */
	function deleteSource($authId) {
		return $this->update(
			'DELETE FROM auth_sources WHERE auth_id = ?', $authId
		);
	}
	
	/**
	 * Set the default authentication source.
	 * @param $authId int
	 */
	function setDefault($authId) {
		$this->update(
			'UPDATE auth_sources SET auth_default = 0'
		);
		$this->update(
			'UPDATE auth_sources SET auth_default = 1 WHERE auth_id = ?', $authId
		);
	}
	
	/**
	 * Retrieve a list of all auth sources for the site.
	 * @return array AuthSource
	 */
	function &getSources($rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM auth_sources ORDER BY auth_id',
			false, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnAuthSourceFromRow');
		return $returner;
	}
	
}

?>
