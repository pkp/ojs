<?php

/**
 * @file classes/context/ContextDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContextDAO
 * @ingroup core
 * @see DAO
 *
 * @brief Operations for retrieving and modifying context objects.
 */

abstract class ContextDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Retrieve a context by context ID.
	 * @param $contextId int
	 * @return Context
	 */
	function getById($contextId) {
		$result = $this->retrieve(
			'SELECT * FROM ' . $this->_getTableName() . ' WHERE ' . $this->_getPrimaryKeyColumn() . ' = ?',
			(int) $contextId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve the IDs and names of all contexts in an associative array.
	 * @param $enabledOnly true iff only enabled contexts are to be included
	 * @return array
	 */
	function getNames($enabledOnly = false) {
		$contexts = array();
		$iterator = $this->getAll($enabledOnly);
		while ($context = $iterator->next()) {
			$contexts[$context->getId()] = $context->getLocalizedName();
		}
		return $contexts;
	}

	/**
	 * Get a list of localized settings.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('name', 'description');
	}

	/**
	 * Internal function to return a Context object from a row.
	 * @param $row array
	 * @return Context
	 */
	function _fromRow($row) {
		$context = $this->newDataObject();
		$context->setId($row[$this->_getPrimaryKeyColumn()]);
		$context->setPath($row['path']);
		$context->setSequence($row['seq']);
		$this->getDataObjectSettings($this->_getSettingsTableName(), $this->_getPrimaryKeyColumn(), $row[$this->_getPrimaryKeyColumn()], $context);
		return $context;
	}

	/**
	 * Insert a new context.
	 * @param $context Context
	 * @return int Inserted context ID
	 */
	function insertObject($context) {
		$this->update(
			'INSERT INTO ' . $this->_getTableName() . '
				(path, seq, enabled, primary_locale)
				VALUES
				(?, ?, ?, ?)',
			array(
				$context->getPath(),
				(int) $context->getSequence(),
				(int) $context->getEnabled(),
				$context->getPrimaryLocale()
			)
		);

		$context->setId($this->getInsertId());
		return $context->getId();
	}

	/**
	 * Update an existing context.
	 * @param $press Press
	 */
	function updateObject($context) {
		return $this->update(
			'UPDATE ' . $this->_getTableName() . '
				SET
					path = ?,
					seq = ?,
					enabled = ?,
					primary_locale = ?
				WHERE ' . $this->_getPrimaryKeyColumn() . ' = ?',
			array(
				$context->getPath(),
				(int) $context->getSequence(),
				(int) $context->getEnabled(),
				$context->getPrimaryLocale(),
				(int) $context->getId()
			)
		);
	}

	/**
	 * Check if a context exists with a specified path.
	 * @param $path string the path for the context
	 * @return boolean
	 */
	function existsByPath($path) {
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM ' . $this->_getTableName() . ' WHERE path = ?',
			(string) $path
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve a context by path.
	 * @param $path string
	 * @return Context
	 */
	function getByPath($path) {
		$result = $this->retrieve(
			'SELECT * FROM ' . $this->_getTableName() . ' WHERE path = ?',
			(string) $path
		);
		if ($result->RecordCount() == 0) return null;

		$returner = $this->_fromRow($result->GetRowAssoc(false));
		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve all contexts.
	 * @param $enabledOnly true iff only enabled contexts should be included
	 * @param $rangeInfo Object optional
	 * @return DAOResultFactory containing matching Contexts
	 */
	function getAll($enabledOnly = false, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM ' . $this->_getTableName() .
			($enabledOnly?' WHERE enabled = 1':'') .
			' ORDER BY seq',
			false,
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Retrieve available contexts.
	 * @param $userId int Optional user ID to find available contexts for
	 * @param $rangeInfo Object optional
	 * @return DAOResultFactory containing matching Contexts
	 */
	function getAvailable($userId = null, $rangeInfo = null) {
		$params = array();
		if ($userId) $params = array_merge(
			$params,
			array((int) $userId, (int) $userId, (int) ROLE_ID_SITE_ADMIN)
		);

		$result = $this->retrieveRange(
			'SELECT c.* FROM ' . $this->_getTableName() . ' c
			WHERE	c.enabled = 1 ' .
				($userId?
					'OR c.' . $this->_getPrimaryKeyColumn() . ' IN (SELECT DISTINCT ug.context_id FROM user_groups ug JOIN user_user_groups uug ON (ug.user_group_id = uug.user_group_id) WHERE uug.user_id = ?)
					OR ? IN (SELECT user_id FROM user_groups ug JOIN user_user_groups uug ON (ug.user_group_id = uug.user_group_id) WHERE ug.role_id = ?) '
				:'') .
			'ORDER BY seq',
			$params,
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Get journals by setting.
	 * @param $settingName string
	 * @param $settingValue mixed
	 * @param $contextId int
	 * @return DAOResultFactory
	 */
	function getBySetting($settingName, $settingValue, $contextId = null) {
		$params = array($settingName, $settingValue);
		if ($contextId) $params[] = $contextId;

		$result = $this->retrieve(
			'SELECT * FROM ' . $this->_getTableName() . ' AS c
			LEFT JOIN ' . $this->_getSettingsTableName() . ' AS cs
			ON c.' . $this->_getPrimaryKeyColumn() . ' = cs.' . $this->_getPrimaryKeyColumn() .
			' WHERE cs.setting_name = ? AND cs.setting_value = ?' .
			($contextId?' AND c.' . $this->_getPrimaryKeyColumn() . ' = ?':''),
			$params
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}


	/**
	 * Get the ID of the last inserted context.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId($this->_getTableName(), $this->_getPrimaryKeyColumn());
	}

	/**
	 * Delete a context by object
	 * @param $context Context
	 */
	function deleteObject($context) {
		$this->deleteById($context->getId());
	}

	/**
	 * Delete a context by ID.
	 * @param $contextId int
	 */
	function deleteById($contextId) {
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroupDao->deleteAssignmentsByContextId($contextId);
		$userGroupDao->deleteByContextId($contextId);

		$genreDao = DAORegistry::getDAO('GenreDAO');
		$genreDao->deleteByContextId($contextId);

		$this->update(
			'DELETE FROM ' . $this->_getTableName() . ' WHERE ' . $this->_getPrimaryKeyColumn() . ' = ?',
			(int) $contextId
		);
	}

	/**
	 * Sequentially renumber each context according to their sequence order.
	 */
	function resequence() {
		$result = $this->retrieve(
			'SELECT ' . $this->_getPrimaryKeyColumn() . ' FROM ' . $this->_getTableName() . ' ORDER BY seq'
		);

		for ($i=1; !$result->EOF; $i+=2) {
			list($contextId) = $result->fields;
			$this->update(
				'UPDATE ' . $this->_getTableName() . ' SET seq = ? WHERE ' . $this->_getPrimaryKeyColumn() . ' = ?',
				array(
					$i,
					$contextId
				)
			);

			$result->MoveNext();
		}
		$result->Close();
	}

	//
	// Protected methods
	//
	/**
	 * Get the table name for this context.
	 * @return string
	 */
	abstract protected function _getTableName();

	/**
	 * Get the table name for this context's settings table.
	 * @return string
	 */
	abstract protected function _getSettingsTableName();

	/**
	 * Get the name of the primary key column for this context.
	 * @return string
	 */
	abstract protected function _getPrimaryKeyColumn();
}

?>
