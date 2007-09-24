<?php

/**
 * @file DAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 * @class DAO
 *
 * Data Access Object base class.
 * Operations for retrieving and modifying objects from a database.
 *
 * $Id$
 */

class DAO {
	/** The database connection object */
	var $_dataSource;

	/**
	 * Constructor.
	 * Initialize the database connection.
	 */
	function DAO($dataSource = null, $callHooks = true) {
		if ($callHooks === true && checkPhpVersion('4.3.0')) {
			$trace = debug_backtrace();
			// Call hooks based on the calling entity, assuming
			// this method is only called by a subclass. Results
			// in hook calls named e.g. "SessionDAO::Constructor"
			if (HookRegistry::call($trace[1]['class'] . '::Constructor', array(&$this, &$dataSource))) {
				return;
			}
		}

		if (!isset($dataSource)) {
			$this->_dataSource = &DBConnection::getConn();
		} else {
			$this->_dataSource = $dataSource;
		}
	}

	/**
	 * Execute a SELECT SQL statement.
	 * @param $sql string the SQL statement
	 * @param $params array parameters for the SQL statement
	 * @return ADORecordSet
	 */
	function &retrieve($sql, $params = false, $callHooks = true) {
		if ($callHooks === true && checkPhpVersion('4.3.0')) {
			$trace = debug_backtrace();
			// Call hooks based on the calling entity, assuming
			// this method is only called by a subclass. Results
			// in hook calls named e.g. "SessionDAO::getSession"
			$value = null;
			if (HookRegistry::call($trace[1]['class'] . '::' . $trace[1]['function'], array(&$sql, &$params, &$value))) {
				return $value;
			}
		}

		$result = &$this->_dataSource->execute($sql, $params !== false && !is_array($params) ? array($params) : $params);
		if ($this->_dataSource->errorNo()) {
			// FIXME Handle errors more elegantly.
			fatalError('DB Error: ' . $this->_dataSource->errorMsg());
		}
		return $result;
	}

	/**
	 * Execute a cached SELECT SQL statement.
	 * @param $sql string the SQL statement
	 * @param $params array parameters for the SQL statement
	 * @return ADORecordSet
	 */
	function &retrieveCached($sql, $params = false, $secsToCache = 3600, $callHooks = true) {
		if ($callHooks === true && checkPhpVersion('4.3.0')) {
			$trace = debug_backtrace();
			// Call hooks based on the calling entity, assuming
			// this method is only called by a subclass. Results
			// in hook calls named e.g. "SessionDAO::getSession"
			$value = null;
			if (HookRegistry::call($trace[1]['class'] . '::' . $trace[1]['function'], array(&$sql, &$params, &$secsToCache, &$value))) {
				return $value;
			}
		}

		$this->setCacheDir();

		$result = &$this->_dataSource->CacheExecute($secsToCache, $sql, $params !== false && !is_array($params) ? array($params) : $params);
		if ($this->_dataSource->errorNo()) {
			// FIXME Handle errors more elegantly.
			fatalError('DB Error: ' . $this->_dataSource->errorMsg());
		}
		return $result;
	}

	/**
	 * Execute a SELECT SQL statement with LIMIT on the rows returned.
	 * @param $sql string the SQL statement
	 * @param $params array parameters for the SQL statement
	 * @param $numRows int maximum number of rows to return in the result set
	 * @param $offset int row offset in the result set
	 * @return ADORecordSet
	 */
	function &retrieveLimit($sql, $params = false, $numRows = false, $offset = false, $callHooks = true) {
		if ($callHooks === true && checkPhpVersion('4.3.0')) {
			$trace = debug_backtrace();
			// Call hooks based on the calling entity, assuming
			// this method is only called by a subclass. Results
			// in hook calls named e.g. "SessionDAO::getSession"
			$value = null;
			if (HookRegistry::call($trace[1]['class'] . '::' . $trace[1]['function'], array(&$sql, &$params, &$numRows, &$offset, &$value))) {
				return $value;
			}
		}

		$result = &$this->_dataSource->selectLimit($sql, $numRows === false ? -1 : $numRows, $offset === false ? -1 : $offset, $params !== false && !is_array($params) ? array($params) : $params);
		if ($this->_dataSource->errorNo()) {
			fatalError('DB Error: ' . $this->_dataSource->errorMsg());
		}
		return $result;
	}

	/**
	 * Execute a SELECT SQL statment, returning rows in the range supplied.
	 * @param $sql string the SQL statement
	 * @param $params array parameters for the SQL statement
	 * @param $dbResultRange object the DBResultRange object describing the desired range
	 */
	function &retrieveRange($sql, $params = false, $dbResultRange = null, $callHooks = true) {
		if ($callHooks === true && checkPhpVersion('4.3.0')) {
			$trace = debug_backtrace();
			// Call hooks based on the calling entity, assuming
			// this method is only called by a subclass. Results
			// in hook calls named e.g. "SessionDAO::getSession"
			$value = null;
			if (HookRegistry::call($trace[1]['class'] . '::' . $trace[1]['function'], array(&$sql, &$params, &$dbResultRange, &$value))) {
				return $value;
			}
		}

		if (isset($dbResultRange) && $dbResultRange->isValid()) {
			$result = &$this->_dataSource->PageExecute($sql, $dbResultRange->getCount(), $dbResultRange->getPage(), $params);
			if ($this->_dataSource->errorNo()) {
				fatalError('DB Error: ' . $this->_dataSource->errorMsg());
			}
		}
		else {
			$result = &$this->retrieve($sql, $params, false);
		}
		return $result;
	}

	/**
	 * Execute an INSERT, UPDATE, or DELETE SQL statement.
	 * @param $sql the SQL statement the execute
	 * @param $params an array of parameters for the SQL statement
	 * @param $callHooks boolean Whether or not to call hooks
	 * @param $dieOnError boolean Whether or not to die if an error occurs
	 * @return boolean
	 */
	function update($sql, $params = false, $callHooks = true, $dieOnError = true) {
		if ($callHooks === true && checkPhpVersion('4.3.0')) {
			$trace = debug_backtrace();
			// Call hooks based on the calling entity, assuming
			// this method is only called by a subclass. Results
			// in hook calls named e.g. "SessionDAO::updateSession"
			$value = null;
			if (HookRegistry::call($trace[1]['class'] . '::' . $trace[1]['function'], array(&$sql, &$params, &$value))) {
				return $value;
			}
		}

		$this->_dataSource->execute($sql, $params !== false && !is_array($params) ? array($params) : $params);
		if ($dieOnError && $this->_dataSource->errorNo()) {
			fatalError('DB Error: ' . $this->_dataSource->errorMsg());
		}
		return $this->_dataSource->errorNo() == 0 ? true : false;
	}

	/**
	 * Insert a row in a table, replacing an existing row if necessary.
	 * @param $table string
	 * @param $arrFields array Associative array of colName => value
	 * @param $keyCols array Array of column names that are keys
	 */
	function replace($table, $arrFields, $keyCols) {
		$this->_dataSource->Replace($table, $arrFields, $keyCols, true);
	}

	/**
	 * Return the last ID inserted in an autonumbered field.
	 * @param $table string table name
	 * @param $id string the ID/key column in the table
	 * @return int
	 */
	function getInsertId($table = '', $id = '', $callHooks = true) {
		return $this->_dataSource->po_insert_id($table, $id);
	}

	/**
	 * Configure the caching directory for database results
	 * NOTE: This is implemented as a GLOBAL setting and cannot
	 * be set on a per-connection basis.
	 */
	function setCacheDir() {
		static $cacheDir;
		if (!isset($cacheDir)) {
			global $ADODB_CACHE_DIR;

			import('cache.CacheManager');
			$cacheDir = CacheManager::getFileCachePath() . '/_db';

			$ADODB_CACHE_DIR = $cacheDir;
		}
	}

	/**
	 * Flush the system cache.
	 */
	function flushCache() {
		$this->setCacheDir();
		$this->_dataSource->CacheFlush();
	}

	/**
	 * Return datetime formatted for DB insertion.
	 * @param $dt int/string *nix timestamp or ISO datetime string
	 * @return string
	 */
	function datetimeToDB($dt) {
		return $this->_dataSource->DBTimeStamp($dt);
	}

	/**
	 * Return date formatted for DB insertion.
	 * @param $d int/string *nix timestamp or ISO date string
	 * @return string
	 */
	function dateToDB($d) {
		return $this->_dataSource->DBDate($d);
	}

	/**
	 * Return datetime from DB as ISO datetime string.
	 * @param $dt string datetime from DB
	 * @return string
	 */
	function datetimeFromDB($dt) {
		if ($dt === null) return null;
		return $this->_dataSource->UserTimeStamp($dt, 'Y-m-d H:i:s');
	}
	/**
	 * Return date from DB as ISO date string.
	 * @param $d string date from DB
	 * @return string
	 */
	function dateFromDB($d) {
		if ($d === null) return null;
		return $this->_dataSource->UserDate($d, 'Y-m-d');
	}

	/**
	 * Convert a stored type from the database
	 * @param $value string Value from DB
	 * @param $type string Type from DB
	 * @return mixed
	 */
	function convertFromDB($value, $type) {
		switch ($type) {
			case 'bool':
				$value = (bool) $value;
				break;
			case 'int':
				$value = (int) $value;
				break;
			case 'float':
				$value = (float) $value;
				break;
			case 'object':
				$value = unserialize($value);
				break;
			case 'string':
			default:
				// Nothing required.
				break;
		}
		return $value;
	}

	/**
	 * Get the type of a value to be stored in the database
	 * @param $value string
	 * @return string
	 */
	function getType($value) {
		switch (gettype($value)) {
			case 'boolean':
			case 'bool':
				return 'bool';
			case 'integer':
			case 'int':
				return 'int';
			case 'double':
			case 'float':
				return 'float';
			case 'array':
			case 'object':
				return 'object';
			case 'string':
			default:
				return 'string';
		}
	}

	/**
	 * Convert a PHP variable into a string to be stored in the DB
	 * @param $value mixed
	 * @param $type string
	 * @return string
	 */
	function convertToDB($value, &$type) {
		if ($type == null) {
			$type = $this->getType($value);
		}

		if ($type == 'object') {
			$value = serialize($value);

		} else if ($type == 'bool') {
			$value = $value ? 1 : 0;
		}

		return $value;
	}

	function getLocaleFieldNames() {
		return array();
	}

	function updateDataObjectSettings($tableName, &$dataObject, $idArray) {
		$idFields = array_keys($idArray);
		$idFields[] = 'locale';
		$idFields[] = 'setting_name';

		foreach ($this->getLocaleFieldNames() as $field) {
			$values = $dataObject->getData($field);
			if (!is_array($values)) continue;

			foreach ($values as $locale => $value) {
				$idArray['setting_type'] = null;
				$idArray['locale'] = $locale;
				$idArray['setting_name'] = $field;
				$idArray['setting_value'] = $this->convertToDB($value, $idArray['setting_type']);

				$this->replace($tableName, $idArray, $idFields);
			}
		}
	}

	function getDataObjectSettings($tableName, $idFieldName, $idFieldValue, &$dataObject) {
		if ($idFieldName !== null) {
			$sql = "SELECT * FROM $tableName WHERE $idFieldName = ?";
			$params = array($idFieldValue);
		} else {
			$sql = "SELECT * FROM $tableName";
			$params = false;
		}
		$result =& $this->retrieve($sql, $params);

		while (!$result->EOF) {
			$row = &$result->getRowAssoc(false);
			$dataObject->setData($row['setting_name'], $this->convertFromDB($row['setting_value'], $row['setting_type']), $row['locale']);
			unset($row);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);
	}
}

?>
